<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Review;
use App\Models\Transaction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class OperationsController extends Controller
{
    public function bookings(Request $request): View
    {
        $state = $this->filters($request, ['created_at', 'starts_at', 'total_price', 'status']);
        $query = Booking::query()->with(['customer:id,name,email', 'studio:id,name,provider_id', 'studio.provider:id,name', 'items:id,booking_id,item_name,item_type,unit_price,line_total', 'transactions:id,booking_id,type,amount,currency,status']);
        if ($state['search'] !== '') {
            $term = $state['search'];
            $query->where(fn ($q) => $q->where('reference', 'like', "%{$term}%")
                ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"))
                ->orWhereHas('studio', fn ($s) => $s->where('name', 'like', "%{$term}%")));
        }
        if (in_array($state['status'], ['pending_payment', 'confirmed', 'completed', 'cancelled', 'expired'], true)) {
            $query->where('status', $state['status']);
        }
        $records = $this->paginate($query, $state);
        return view('admin._workspace.operations.index', array_merge($state, compact('records'), ['type' => 'bookings']));
    }

    public function transactions(Request $request): View
    {
        $state = $this->filters($request, ['created_at', 'amount', 'processed_at', 'status']);
        $typeFilter = (string) $request->query('transaction_type', '');
        $typeFilter = in_array($typeFilter, ['payment', 'refund'], true) ? $typeFilter : '';
        $query = Transaction::query()->with(['booking:id,reference,customer_id,studio_id,status', 'booking.customer:id,name,email', 'booking.studio:id,name']);
        if ($state['search'] !== '') {
            $term = $state['search'];
            $query->where(fn ($q) => $q->where('provider_reference', 'like', "%{$term}%")
                ->orWhere('checkout_session_id', 'like', "%{$term}%")
                ->orWhereHas('booking', fn ($b) => $b->where('reference', 'like', "%{$term}%")));
        }
        if (in_array($state['status'], ['pending', 'processing', 'succeeded', 'failed', 'cancelled'], true)) {
            $query->where('status', $state['status']);
        }
        if ($typeFilter !== '') {
            $query->where('type', $typeFilter);
        }
        $records = $this->paginate($query, $state);
        return view('admin._workspace.operations.index', array_merge($state, compact('records', 'typeFilter'), ['type' => 'transactions']));
    }

    public function reviews(Request $request): View
    {
        $state = $this->filters($request, ['created_at', 'rating']);
        $rating = (int) $request->query('rating', 0);
        $rating = in_array($rating, [1, 2, 3, 4, 5], true) ? $rating : 0;
        $query = Review::query()->with(['booking:id,reference,customer_id,studio_id', 'booking.customer:id,name,email', 'booking.studio:id,name']);
        if ($state['search'] !== '') {
            $term = $state['search'];
            $query->where(fn ($q) => $q->where('comment', 'like', "%{$term}%")
                ->orWhereHas('booking', fn ($b) => $b->where('reference', 'like', "%{$term}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('studio', fn ($s) => $s->where('name', 'like', "%{$term}%"))));
        }
        if ($rating) {
            $query->where('rating', $rating);
        }
        $records = $this->paginate($query, $state);
        return view('admin._workspace.operations.index', array_merge($state, compact('records', 'rating'), ['type' => 'reviews']));
    }

    public function removeReview(Request $request, Review $review): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:255']]);
        $review->delete();
        Log::notice('Admin review soft-deleted', [
            'booking_id' => $review->booking_id,
            'admin_id' => $request->user()->id,
            'reason' => $data['reason'],
        ]);
        return response()->json(['message' => 'Review removed from public listings.']);
    }

    private function filters(Request $request, array $sortable): array
    {
        return [
            'search' => trim((string) $request->query('search', '')),
            'status' => (string) $request->query('status', ''),
            'sort' => in_array($request->query('sort'), $sortable, true) ? $request->query('sort') : 'created_at',
            'direction' => $request->query('direction') === 'asc' ? 'asc' : 'desc',
            'perPage' => in_array((int) $request->query('per_page', 5), [5, 10, 25, 50], true) ? (int) $request->query('per_page', 5) : 5,
        ];
    }

    private function paginate($query, array $filters)
    {
        return $query->orderBy($filters['sort'], $filters['direction'])->orderByDesc('created_at')
            ->paginate($filters['perPage'])->withQueryString();
    }
}
