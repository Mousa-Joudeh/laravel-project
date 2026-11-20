<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\PageVisit;
use App\Models\ProviderProfile;
use App\Models\Package;
use App\Models\Review;
use App\Models\Service;
use App\Models\Studio;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $days = (int) $request->query('period', 30);
        $days = in_array($days, [7, 30, 90], true) ? $days : 30;
        $end = now()->addDay()->startOfDay();
        $start = $end->copy()->subDays($days);
        $previousStart = $start->copy()->subDays($days);

        $period = fn ($query, $from, $to, $column = 'created_at') => $query
            ->where($column, '>=', $from)
            ->where($column, '<', $to);

        $paymentTotal = function ($from, $to) use ($period): float {
            $transactions = $period(Transaction::query(), $from, $to)
                ->where('currency', 'JOD')
                ->where('status', 'succeeded')
                ->selectRaw('type, SUM(amount) AS total')
                ->groupBy('type')
                ->pluck('total', 'type');

            return round((float) ($transactions['payment'] ?? 0) - (float) ($transactions['refund'] ?? 0), 3);
        };

        $partnerCount = fn ($from, $to) => $period(ProviderProfile::query(), $from, $to)
            ->where('status', 'approved')
            ->whereHas('user', fn ($query) => $query->where('status', 'active'))
            ->count();

        $visitsCount = fn ($from, $to) => $period(PageVisit::query(), $from, $to, 'visited_at')
            ->where('route_name', 'not like', 'admin.%')
            ->count();

        $current = [
            'payments' => $paymentTotal($start, $end),
            'bookings' => $period(Booking::query(), $start, $end)->count(),
            'partners' => $partnerCount($start, $end),
            'visits' => $visitsCount($start, $end),
            'customers' => $period(User::query(), $start, $end)->where('role', 'customer')->count(),
        ];

        $previous = [
            'payments' => $paymentTotal($previousStart, $start),
            'bookings' => $period(Booking::query(), $previousStart, $start)->count(),
            'partners' => $partnerCount($previousStart, $start),
            'visits' => $visitsCount($previousStart, $start),
            'customers' => $period(User::query(), $previousStart, $start)->where('role', 'customer')->count(),
        ];

        $bookingStatusCounts = $period(Booking::query(), $start, $end)
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $previousCancelled = $period(Booking::query(), $previousStart, $start)
            ->where('status', 'cancelled')->count();
        $currentCancelled = (int) ($bookingStatusCounts['cancelled'] ?? 0);

        $paymentAttempts = fn ($from, $to) => $period(Transaction::query(), $from, $to)
            ->where('type', 'payment')->count();
        $succeededPayments = fn ($from, $to) => $period(Transaction::query(), $from, $to)
            ->where('type', 'payment')->where('status', 'succeeded')->count();

        $percentage = fn ($part, $total) => $total > 0 ? round($part / $total * 100, 1) : 0;
        $rates = [
            'cancellation' => $percentage($currentCancelled, $current['bookings']),
            'payment_success' => $percentage($succeededPayments($start, $end), $paymentAttempts($start, $end)),
        ];
        $previousRates = [
            'cancellation' => $percentage($previousCancelled, $previous['bookings']),
            'payment_success' => $percentage($succeededPayments($previousStart, $start), $paymentAttempts($previousStart, $start)),
        ];

        $change = function (float $value, float $before, bool $inverse = false): array {
            if ($before == 0.0) {
                return ['text' => $value == 0.0 ? 'No change' : 'New', 'direction' => $value == 0.0 ? 'flat' : ($inverse ? 'negative' : 'positive'), 'icon' => $value == 0.0 ? 'dash' : 'arrow-up-right'];
            }

            $difference = round(($value - $before) / abs($before) * 100, 1);
            $direction = $difference == 0.0 ? 'flat' : (($difference > 0) !== $inverse ? 'positive' : 'negative');

            return [
                'text' => ($difference > 0 ? '+' : '') . number_format($difference, 1) . '%',
                'direction' => $direction,
                'icon' => $difference > 0 ? 'arrow-up-right' : ($difference < 0 ? 'arrow-down-right' : 'dash'),
            ];
        };

        $kpis = [
            ['key' => 'payments', 'label' => 'Net processed payments', 'value' => number_format($current['payments'], 2) . ' JOD', 'note' => 'Succeeded payments less refunds', 'icon' => 'wallet2'],
            ['key' => 'bookings', 'label' => 'Bookings', 'value' => number_format($current['bookings']), 'note' => 'Reservations created', 'icon' => 'calendar-check'],
            ['key' => 'partners', 'label' => 'New approved partners', 'value' => number_format($current['partners']), 'note' => 'Approved profiles created', 'icon' => 'buildings'],
            ['key' => 'visits', 'label' => 'Tracked page views', 'value' => number_format($current['visits']), 'note' => 'Excludes admin routes', 'icon' => 'graph-up-arrow'],
        ];

        foreach ($kpis as &$kpi) {
            $key = $kpi['key'];
            $kpi['change'] = $change((float) $current[$key], (float) $previous[$key]);
        }
        unset($kpi);

        $secondaryKpis = [
            ['label' => 'New customers', 'value' => number_format($current['customers']), 'note' => 'Customer registrations', 'change' => $change($current['customers'], $previous['customers']), 'icon' => 'person-plus'],
            ['label' => 'Cancellation rate', 'value' => number_format($rates['cancellation'], 1) . '%', 'note' => 'Cancelled / bookings created', 'change' => $change($rates['cancellation'], $previousRates['cancellation'], true), 'icon' => 'calendar-x'],
            ['label' => 'Payment success rate', 'value' => number_format($rates['payment_success'], 1) . '%', 'note' => 'Succeeded / payment attempts', 'change' => $change($rates['payment_success'], $previousRates['payment_success']), 'icon' => 'credit-card-2-front'],
        ];

        $bookingsByDay = $period(Booking::query(), $start, $end)
            ->selectRaw('DATE(created_at) AS day, COUNT(*) AS total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $transactionsByDay = $period(Transaction::query(), $start, $end)
            ->where('currency', 'JOD')
            ->where('status', 'succeeded')
            ->selectRaw('DATE(created_at) AS day, type, SUM(amount) AS total')
            ->groupBy('day', 'type')
            ->get()
            ->groupBy('day');

        $dailyActivity = collect(range(0, $days - 1))->map(function (int $offset) use ($start, $bookingsByDay, $transactionsByDay): array {
            $date = $start->copy()->addDays($offset);
            $day = $date->toDateString();
            $transactions = $transactionsByDay->get($day, collect());
            $payments = (float) $transactions->where('type', 'payment')->sum('total');
            $refunds = (float) $transactions->where('type', 'refund')->sum('total');

            return [
                'label' => $date->format('d M'),
                'bookings' => (int) ($bookingsByDay[$day] ?? 0),
                'payments' => round($payments - $refunds, 3),
            ];
        });

        $activity = $dailyActivity->chunk($days === 90 ? 7 : 1)
            ->map(fn ($days) => [
                'label' => $days->first()['label'],
                'bookings' => $days->sum('bookings'),
                'payments' => round($days->sum('payments'), 3),
            ])
            ->values();

        $bookingStatuses = collect([
            ['key' => 'confirmed', 'label' => 'Confirmed', 'color' => '#0a9c79'],
            ['key' => 'completed', 'label' => 'Completed', 'color' => '#426a78'],
            ['key' => 'pending_payment', 'label' => 'Pending payment', 'color' => '#e2ad4f'],
            ['key' => 'cancelled', 'label' => 'Cancelled', 'color' => '#e37967'],
            ['key' => 'expired', 'label' => 'Expired', 'color' => '#bcc7cf'],
        ])->map(function ($item) use ($bookingStatusCounts, $current) {
            $item['count'] = (int) ($bookingStatusCounts[$item['key']] ?? 0);
            $item['percentage'] = $current['bookings'] > 0 ? $item['count'] / $current['bookings'] * 100 : 0;
            return $item;
        });

        $approvals = [
            ['label' => 'Partner profiles', 'count' => ProviderProfile::where('status', 'pending')->count(), 'icon' => 'person-check'],
            ['label' => 'Studios', 'count' => Studio::where('status', 'pending')->count(), 'icon' => 'camera-video'],
            ['label' => 'Services', 'count' => Service::where('status', 'pending')->count(), 'icon' => 'layers'],
            ['label' => 'Packages', 'count' => Package::where('is_active', false)->count(), 'icon' => 'box-seam'],
        ];
        $pendingCount = array_sum(array_column($approvals, 'count'));

        $topPartners = User::query()
            ->where('role', 'provider')
            ->where('status', 'active')
            ->whereHas('providerProfile', fn ($query) => $query->where('status', 'approved'))
            ->with('providerProfile:user_id,company_name,status')
            ->withCount(['providerBookings as period_bookings_count' => fn ($query) => $period($query, $start, $end, 'bookings.created_at')])
            ->orderByDesc('period_bookings_count')
            ->limit(5)
            ->get();

        $topPages = $period(PageVisit::query(), $start, $end, 'visited_at')
            ->where('route_name', 'not like', 'admin.%')
            ->selectRaw('route_name, COUNT(*) AS total')
            ->groupBy('route_name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $bookingSearch = trim((string) $request->query('booking_search', ''));
        $bookingStatus = (string) $request->query('booking_status', '');
        $allowedStatuses = ['pending_payment', 'confirmed', 'completed', 'cancelled', 'expired'];
        $bookingStatus = in_array($bookingStatus, $allowedStatuses, true) ? $bookingStatus : '';

        $recentBookingsQuery = Booking::with(['customer:id,name', 'studio:id,name'])
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end);

        if ($bookingStatus !== '') {
            $recentBookingsQuery->where('status', $bookingStatus);
        }

        if ($bookingSearch !== '') {
            $recentBookingsQuery->where(function ($query) use ($bookingSearch) {
                $query->where('reference', 'like', '%' . $bookingSearch . '%')
                    ->orWhereHas('customer', fn ($user) => $user->where('name', 'like', '%' . $bookingSearch . '%'))
                    ->orWhereHas('studio', fn ($studio) => $studio->where('name', 'like', '%' . $bookingSearch . '%'));
            });
        }

        $recentBookings = $recentBookingsQuery->latest('id')
            ->paginate(5, ['*'], 'booking_page')
            ->withQueryString();

        $platformStats = [
            'approved_partners' => ProviderProfile::where('status', 'approved')->whereHas('user', fn ($query) => $query->where('status', 'active'))->count(),
            'approved_studios' => Studio::where('status', 'approved')->where('is_active', true)->count(),
            'reviews' => Review::count(),
        ];

        return view('admin.dashboard', compact(
            'days', 'kpis', 'secondaryKpis', 'activity', 'bookingStatuses',
            'approvals', 'pendingCount', 'topPartners', 'topPages',
            'recentBookings', 'bookingSearch', 'bookingStatus', 'current', 'platformStats'
        ));
    }
}
