<?php

namespace App\Services\Admin\Ai;

use App\Models\Booking;
use App\Models\Package;
use App\Models\PageVisit;
use App\Models\ProviderProfile;
use App\Models\Review;
use App\Models\Service;
use App\Models\Studio;
use App\Models\Transaction;
use App\Models\User;

class AdminAiMetrics
{
    public function snapshot(int $days = 30): array
    {
        $days = in_array($days, [7, 30, 90], true) ? $days : 30;
        $start = now()->startOfDay()->subDays($days - 1);
        $end = now();
        $previousStart = $start->copy()->subDays($days);
        $previousEnd = $start->copy();

        $bookings = Booking::query()->whereBetween('created_at', [$start, $end]);
        $previousBookings = Booking::query()->where('created_at', '>=', $previousStart)->where('created_at', '<', $previousEnd);
        $statusCounts = (clone $bookings)->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');
        $bookingCount = (int) $statusCounts->sum();
        $previousBookingCount = (int) $previousBookings->count();

        $transactions = Transaction::query()->whereBetween('created_at', [$start, $end])->where('currency', 'JOD');
        $successfulPayments = (float) (clone $transactions)->where('type', 'payment')->where('status', 'succeeded')->sum('amount');
        $successfulRefunds = (float) (clone $transactions)->where('type', 'refund')->where('status', 'succeeded')->sum('amount');
        $paymentAttempts = (int) (clone $transactions)->where('type', 'payment')->count();
        $paymentSuccesses = (int) (clone $transactions)->where('type', 'payment')->where('status', 'succeeded')->count();

        $reviews = Review::query()->whereBetween('created_at', [$start, $end]);
        $reviewCount = (int) (clone $reviews)->count();
        $ratingAverage = $reviewCount > 0 ? round((float) (clone $reviews)->avg('rating'), 2) : null;

        $pending = [
            'partners' => (int) ProviderProfile::query()->where('status', 'pending')->count(),
            'studios' => (int) Studio::query()->where('status', 'pending')->count(),
            'services' => (int) Service::query()->where('status', 'pending')->count(),
        ];

        $visits = (int) PageVisit::query()
            ->whereBetween('visited_at', [$start, $end])
            ->where('route_name', 'not like', 'admin.%')
            ->count();

        $daily = (clone $bookings)
            ->selectRaw('DATE(created_at) AS day, COUNT(*) AS total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $dailyBookings = collect(range(0, $days - 1))->map(function (int $offset) use ($start, $daily): array {
            $date = $start->copy()->addDays($offset);
            return ['label' => $date->format('d M'), 'total' => (int) ($daily[$date->format('Y-m-d')] ?? 0)];
        })->all();

        $snapshot = [
            'window' => [
                'days' => $days,
                'from' => $start->toDateString(),
                'to' => $end->toDateString(),
            ],
            'bookings' => [
                'total' => $bookingCount,
                'previous_total' => $previousBookingCount,
                'change_pct' => $previousBookingCount > 0 ? round(100 * ($bookingCount - $previousBookingCount) / $previousBookingCount, 1) : null,
                'confirmed' => (int) ($statusCounts['confirmed'] ?? 0),
                'completed' => (int) ($statusCounts['completed'] ?? 0),
                'cancelled' => (int) ($statusCounts['cancelled'] ?? 0),
                'pending_payment' => (int) ($statusCounts['pending_payment'] ?? 0),
                'expired' => (int) ($statusCounts['expired'] ?? 0),
                'cancellation_rate_pct' => $bookingCount > 0 ? round(100 * (int) ($statusCounts['cancelled'] ?? 0) / $bookingCount, 1) : null,
            ],
            'payments' => [
                'currency' => 'JOD',
                'successful_gross' => round($successfulPayments, 3),
                'successful_refunds' => round($successfulRefunds, 3),
                'net' => round($successfulPayments - $successfulRefunds, 3),
                'attempts' => $paymentAttempts,
                'successful_attempts' => $paymentSuccesses,
                'success_rate_pct' => $paymentAttempts > 0 ? round(100 * $paymentSuccesses / $paymentAttempts, 1) : null,
            ],
            'approvals' => $pending + ['total' => array_sum($pending)],
            'catalog' => [
                'approved_active_studios' => (int) Studio::query()->where('status', 'approved')->where('is_active', true)->count(),
                'approved_active_services' => (int) Service::query()->where('status', 'approved')->where('is_active', true)->count(),
                'active_packages' => (int) Package::query()->where('is_active', true)->count(),
                'inactive_packages' => (int) Package::query()->where('is_active', false)->count(),
            ],
            'users' => [
                'customers' => (int) User::query()->where('role', 'customer')->count(),
                'new_customers' => (int) User::query()->where('role', 'customer')->whereBetween('created_at', [$start, $end])->count(),
                'active_providers' => (int) User::query()->where('role', 'provider')->where('status', 'active')->count(),
            ],
            'reviews' => ['count' => $reviewCount, 'average_rating' => $ratingAverage],
            'traffic' => ['recorded_public_page_views' => $visits, 'note' => $visits === 0 ? 'No visits recorded; tracking may be inactive.' : null],
            'daily_bookings' => $dailyBookings,
        ];

        $snapshot['recommendations'] = $this->recommendations($snapshot);

        return $snapshot;
    }

    public function recommendations(array $data): array
    {
        $items = [];
        $approvals = $data['approvals'];
        $bookings = $data['bookings'];
        $payments = $data['payments'];
        $reviews = $data['reviews'];
        $traffic = $data['traffic'];

        if ($approvals['total'] > 0) {
            $items[] = [
                'priority' => $approvals['total'] >= 5 ? 'high' : 'medium',
                'title' => 'Review the approval queue',
                'evidence' => $approvals['total'] . ' pending partner, studio or service requests.',
                'action' => 'Verify documentation and review each request manually.',
                'route' => 'admin.approvals.index',
            ];
        }
        if ($bookings['total'] >= 5 && $bookings['cancellation_rate_pct'] >= 15) {
            $items[] = [
                'priority' => 'high',
                'title' => 'Investigate booking cancellations',
                'evidence' => $bookings['cancelled'] . ' cancelled of ' . $bookings['total'] . ' bookings (' . $bookings['cancellation_rate_pct'] . '%).',
                'action' => 'Review cancellation reasons and scheduling conflicts before changing policies.',
                'route' => 'admin.operations.bookings',
            ];
        }
        if ($payments['attempts'] >= 5 && $payments['success_rate_pct'] < 90) {
            $items[] = [
                'priority' => 'high',
                'title' => 'Review unsuccessful payment attempts',
                'evidence' => $payments['successful_attempts'] . ' successful of ' . $payments['attempts'] . ' payment attempts (' . $payments['success_rate_pct'] . '%).',
                'action' => 'Check Stripe errors and transaction statuses; do not retry charges automatically.',
                'route' => 'admin.operations.transactions',
            ];
        }
        if ($reviews['count'] >= 3 && $reviews['average_rating'] !== null && $reviews['average_rating'] < 3.5) {
            $items[] = [
                'priority' => 'medium',
                'title' => 'Review lower customer ratings',
                'evidence' => $reviews['average_rating'] . '/5 average across ' . $reviews['count'] . ' new reviews.',
                'action' => 'Inspect review themes and follow up through the normal support process.',
                'route' => 'admin.operations.reviews',
            ];
        }
        if ($bookings['previous_total'] >= 5 && $bookings['change_pct'] !== null && $bookings['change_pct'] <= -20) {
            $items[] = [
                'priority' => 'medium',
                'title' => 'Investigate a decline in new bookings',
                'evidence' => $bookings['total'] . ' this period vs ' . $bookings['previous_total'] . ' in the preceding period (' . $bookings['change_pct'] . '%).',
                'action' => 'Compare listing availability, traffic tracking, and booking-funnel changes.',
                'route' => 'admin.analytics.index',
            ];
        }
        if ($traffic['recorded_public_page_views'] === 0) {
            $items[] = [
                'priority' => 'info',
                'title' => 'Verify public traffic tracking',
                'evidence' => 'No public page views recorded in the selected window.',
                'action' => 'Confirm that the page-visit middleware is attached to public routes.',
                'route' => 'admin.analytics.index',
            ];
        }
        if (!$items) {
            $items[] = [
                'priority' => 'info',
                'title' => 'No threshold-based alerts in this period',
                'evidence' => 'The selected metrics did not cross the predefined alert thresholds.',
                'action' => 'Continue monitoring bookings, payment attempts, reviews and pending approvals.',
                'route' => 'admin.dashboard',
            ];
        }

        return $items;
    }

    public function promptFacts(array $snapshot): array
    {
        unset($snapshot['daily_bookings']);
        return $snapshot;
    }
}
