<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Package;
use App\Models\PageVisit;
use App\Models\ProviderProfile;
use App\Models\Review;
use App\Models\Service;
use App\Models\Studio;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvToolsController extends Controller
{
    public function export(Request $request, string $section): StreamedResponse
    {
        abort_unless(in_array($section, ['categories', 'partners', 'catalog', 'approvals', 'bookings', 'transactions', 'reviews', 'traffic'], true), 404);
        [$query, $columns] = match ($section) {
            'categories' => $this->categories($request),
            'partners' => $this->partners($request),
            'catalog' => $this->catalog($request),
            'approvals' => $this->approvals($request),
            'bookings' => $this->bookings($request),
            'transactions' => $this->transactions($request),
            'reviews' => $this->reviews($request),
            'traffic' => $this->traffic($request),
        };

        $filename = 'space-' . $section . '-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($query, $columns): void {
            $out = fopen('php://output', 'w');
            if (!$out) {
                return;
            }
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_keys($columns), ',', '"', '');

            foreach ($query->lazy(200) as $record) {
                $line = [];
                foreach ($columns as $getter) {
                    $value = $getter($record);
                    $line[] = $this->safeValue($value);
                }
                fputcsv($out, $line, ',', '"', '');
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function importCategories(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);
        $handle = fopen($request->file('file')->getRealPath(), 'r');
        if (!$handle) {
            return back()->withErrors(['file' => 'Unable to read CSV file.']);
        }
        $rows = [];
        $names = [];
        $error = null;
        try {
            $headers = fgetcsv($handle, 0, ',', '"', '');
            if (!$headers) {
                $error = 'CSV file is empty.';
            } else {
                $headers = array_map(fn ($h) => Str::lower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h))), $headers);
                $idx = array_flip($headers);
                if (!array_key_exists('name', $idx)) {
                    $error = 'CSV needs a name column. Optional: is_active,img.';
                } else {
                    $lineNumber = 1;
                    while (($line = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                        $lineNumber++;
                        $get = fn ($key) => isset($idx[$key]) ? trim((string) ($line[$idx[$key]] ?? '')) : '';
                        $name = $get('name');
                        if ($name === '' && count(array_filter($line, fn ($v) => trim((string)$v) !== '')) === 0) {
                            continue;
                        }
                        $raw = $get('is_active');
                        $active = match (Str::lower($raw)) {
                            '', '1', 'true', 'active', 'yes' => true,
                            '0', 'false', 'inactive', 'no' => false,
                            default => null,
                        };
                        $data = ['name' => $name, 'img' => $get('img') ?: null, 'is_active' => $active];
                        $validator = Validator::make($data, [
                            'name' => ['required', 'string', 'max:100'],
                            'img' => ['nullable', 'string', 'max:500'],
                            'is_active' => ['required', 'boolean'],
                        ]);
                        if ($validator->fails()) {
                            $error = "Row {$lineNumber}: " . $validator->errors()->first();
                            break;
                        }
                        $key = Str::lower($name);
                        if (isset($names[$key]) || Category::withTrashed()->where('name', $name)->exists()) {
                            $error = "Row {$lineNumber}: Category already exists or repeats in this file.";
                            break;
                        }
                        $names[$key] = true;
                        $rows[] = $data;
                        if (count($rows) > 200) {
                            $error = 'Maximum 200 categories per import.';
                            break;
                        }
                    }
                }
            }
        } finally {
            fclose($handle);
        }
        if ($error || !$rows) {
            return back()->withErrors(['file' => $error ?: 'No categories found.']);
        }

        DB::transaction(function () use ($rows): void {
            foreach ($rows as $data) {
                $base = Str::slug($data['name']) ?: 'category';
                $base = Str::limit($base, 100, '');
                do {
                    $slug = $base . '-' . Str::lower(Str::random(7));
                } while (Category::withTrashed()->where('slug', $slug)->exists());
                Category::create($data + ['slug' => $slug]);
            }
        });

        return redirect()->route('admin.categories.index')
            ->with('success', count($rows) . ' categories imported.');
    }

    private function safeValue(mixed $value): string
    {
        $text = (string) ($value ?? '');
        return preg_match('/^\s*[=+\-@]/u', $text) ? "'" . $text : $text;
    }

    private function sort(Builder $query, Request $request, array $allowed, string $default = 'created_at'): Builder
    {
        $sort = (string) $request->query('sort', $default);
        $sort = in_array($sort, $allowed, true) ? $sort : $default;
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';
        return $query->orderBy($sort, $direction)->orderByDesc('id');
    }

    private function search(Request $request): string
    {
        return trim((string) $request->query('search', ''));
    }

    private function categories(Request $request): array
    {
        $query = Category::query()->withCount('studios');
        if ($search = $this->search($request)) {
            $query->where('name', 'like', "%{$search}%");
        }
        $status = $request->query('status');
        if (in_array($status, ['active', 'inactive'], true)) {
            $query->where('is_active', $status === 'active');
        }
        return [
            $this->sort($query, $request, ['name', 'studios_count', 'is_active', 'created_at']),
            ['ID' => fn ($c) => $c->id, 'Name' => fn ($c) => $c->name, 'Slug' => fn ($c) => $c->slug,
             'Status' => fn ($c) => $c->is_active ? 'Active' : 'Inactive', 'Studios' => fn ($c) => $c->studios_count,
             'Image' => fn ($c) => $c->img, 'Created' => fn ($c) => $c->created_at?->format('Y-m-d')],
        ];
    }

    private function partners(Request $request): array
    {
        $query = ProviderProfile::query()->with('user:id,name,email,phone,status,role');
        if ($search = $this->search($request)) {
            $query->where(fn ($q) => $q->where('company_name', 'like', "%{$search}%")
                ->orWhere('city', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")));
        }
        if (in_array($request->query('status'), ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $request->query('status'));
        }
        return [$this->sort($query, $request, ['company_name', 'city', 'status', 'created_at']), [
            'Company' => fn ($p) => $p->company_name, 'Owner' => fn ($p) => $p->user?->name,
            'Email' => fn ($p) => $p->user?->email, 'City' => fn ($p) => $p->city,
            'Status' => fn ($p) => $p->status, 'Joined' => fn ($p) => $p->created_at?->format('Y-m-d'),
        ]];
    }

    private function catalog(Request $request): array
    {
        $type = in_array($request->query('type'), ['studios', 'services', 'packages'], true) ? $request->query('type') : 'studios';
        $class = match ($type) { 'studios' => Studio::class, 'services' => Service::class, default => Package::class };
        $query = $class::query();
        $name = $type === 'services' ? 'title' : 'name';
        if ($search = $this->search($request)) {
            $query->where(fn ($q) => $q->where($name, 'like', "%{$search}%")
                ->orWhereHas($type === 'studios' ? 'provider' : 'studio', fn ($related) => $related->where('name', 'like', "%{$search}%")));
        }
        if ($type === 'studios' && (int) $request->query('category') > 0) {
            $query->where('category_id', (int) $request->query('category'));
        }
        $status = $request->query('status');
        if ($type === 'packages') {
            if (in_array($status, ['active', 'approved', 'inactive', 'pending'], true)) {
                $query->where('is_active', in_array($status, ['active', 'approved'], true));
            }
        } elseif (in_array($status, ['draft', 'pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }
        $allowed = $type === 'studios' ? ['name', 'city', 'hourly_price', 'status', 'created_at']
            : ($type === 'services' ? ['title', 'price', 'status', 'created_at'] : ['name', 'price', 'is_active', 'created_at']);
        return [$this->sort($query, $request, $allowed), [
            'ID' => fn ($r) => $r->id, 'Name' => fn ($r) => $r->{$name},
            'Studio/City' => fn ($r) => $type === 'studios' ? $r->city : $r->studio?->name,
            'Price (JOD)' => fn ($r) => $type === 'studios' ? $r->hourly_price : $r->price,
            'Status' => fn ($r) => $type === 'packages' ? ($r->is_active ? 'Active' : 'Inactive') : $r->status,
            'Created' => fn ($r) => $r->created_at?->format('Y-m-d'),
        ]];
    }

    private function approvals(Request $request): array
    {
        $type = in_array($request->query('type'), ['partners', 'studios', 'services', 'packages'], true) ? $request->query('type') : 'partners';
        [$query, $columns] = $type === 'partners' ? $this->partners($request)
            : $this->catalog(new Request(array_merge($request->query(), ['type' => $type])));
        if ((string) $request->query('status', 'pending') === 'pending' && $type === 'packages') {
            $query->where('is_active', false);
        }
        return [$query, $columns];
    }

    private function bookings(Request $request): array
    {
        $query = Booking::query()->with(['customer:id,name,email', 'studio:id,name']);
        if ($search = $this->search($request)) {
            $query->where(fn ($q) => $q->where('reference', 'like', "%{$search}%")
                ->orWhereHas('customer', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                ->orWhereHas('studio', fn ($s) => $s->where('name', 'like', "%{$search}%")));
        }
        if (in_array($request->query('status'), ['pending_payment', 'confirmed', 'completed', 'cancelled', 'expired'], true)) {
            $query->where('status', $request->query('status'));
        }
        return [$this->sort($query, $request, ['created_at', 'starts_at', 'total_price', 'status']), [
            'Reference' => fn ($b) => $b->reference, 'Customer' => fn ($b) => $b->customer?->name,
            'Studio' => fn ($b) => $b->studio?->name, 'Starts' => fn ($b) => $b->starts_at?->format('Y-m-d H:i'),
            'Ends' => fn ($b) => $b->ends_at?->format('Y-m-d H:i'), 'Total' => fn ($b) => $b->total_price,
            'Currency' => fn ($b) => $b->currency, 'Status' => fn ($b) => $b->status,
        ]];
    }

    private function transactions(Request $request): array
    {
        $query = Transaction::query()->with(['booking:id,reference,customer_id,studio_id']);
        if ($search = $this->search($request)) {
            $query->where(fn ($q) => $q->where('provider_reference', 'like', "%{$search}%")
                ->orWhere('checkout_session_id', 'like', "%{$search}%")
                ->orWhereHas('booking', fn ($b) => $b->where('reference', 'like', "%{$search}%")));
        }
        if (in_array($request->query('status'), ['pending', 'processing', 'succeeded', 'failed', 'cancelled'], true)) {
            $query->where('status', $request->query('status'));
        }
        if (in_array($request->query('transaction_type'), ['payment', 'refund'], true)) {
            $query->where('type', $request->query('transaction_type'));
        }
        return [$this->sort($query, $request, ['created_at', 'amount', 'processed_at', 'status']), [
            'ID' => fn ($t) => $t->id, 'Booking' => fn ($t) => $t->booking?->reference,
            'Type' => fn ($t) => $t->type, 'Provider' => fn ($t) => $t->provider,
            'Amount' => fn ($t) => $t->amount, 'Currency' => fn ($t) => $t->currency,
            'Status' => fn ($t) => $t->status, 'Processed' => fn ($t) => $t->processed_at?->format('Y-m-d H:i'),
        ]];
    }

    private function reviews(Request $request): array
    {
        $query = Review::query()->with(['booking:id,reference,customer_id,studio_id', 'booking.customer:id,name,email', 'booking.studio:id,name']);
        if ($search = $this->search($request)) {
            $query->where(fn ($q) => $q->where('comment', 'like', "%{$search}%")
                ->orWhereHas('booking', fn ($b) => $b->where('reference', 'like', "%{$search}%")));
        }
        if (in_array((int) $request->query('rating'), [1,2,3,4,5], true)) {
            $query->where('rating', (int) $request->query('rating'));
        }
        $sort = in_array($request->query('sort'), ['rating', 'created_at'], true) ? $request->query('sort') : 'created_at';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $direction)->orderByDesc('booking_id');
        return [$query, [
            'Booking' => fn ($r) => $r->booking?->reference, 'Customer' => fn ($r) => $r->booking?->customer?->name,
            'Studio' => fn ($r) => $r->booking?->studio?->name, 'Rating' => fn ($r) => $r->rating,
            'Comment' => fn ($r) => $r->comment, 'Created' => fn ($r) => $r->created_at?->format('Y-m-d'),
        ]];
    }

    private function traffic(Request $request): array
    {
        $days = (int) $request->query('period', 30);
        $days = in_array($days, [7,30,90], true) ? $days : 30;
        $start = now()->startOfDay()->subDays($days - 1);
        $query = PageVisit::query()->with('user:id,name,email')
            ->where('visited_at', '>=', $start)->where('visited_at', '<', now()->addDay()->startOfDay())
            ->where('route_name', 'not like', 'admin.%')->where('path', 'not like', '/admin%')->where('path', 'not like', 'admin/%');
        if ($search = $this->search($request)) {
            $query->where(fn ($q) => $q->where('path', 'like', "%{$search}%")->orWhere('route_name', 'like', "%{$search}%"));
        }
        if ($request->query('visitor') === 'member') {
            $query->whereNotNull('user_id');
        } elseif ($request->query('visitor') === 'guest') {
            $query->whereNull('user_id');
        }
        return [$this->sort($query, $request, ['visited_at','path','route_name'], 'visited_at'), [
            'Path' => fn ($v) => $v->path, 'Route' => fn ($v) => $v->route_name,
            'Visitor' => fn ($v) => $v->user?->name ?: 'Guest', 'Visited' => fn ($v) => $v->visited_at?->format('Y-m-d H:i:s'),
        ]];
    }
}
