<?php

namespace Tests\Unit;

use App\Services\Admin\Ai\AdminAiMetrics;
use PHPUnit\Framework\TestCase;

class AdminAiRecommendationsTest extends TestCase
{
    public function test_high_cancellation_and_approval_backlog_trigger_manual_recommendations(): void
    {
        $metrics = new AdminAiMetrics();
        $recs = $metrics->recommendations([
            'approvals' => ['total' => 6],
            'bookings' => ['total' => 20, 'cancelled' => 5, 'cancellation_rate_pct' => 25.0, 'previous_total' => 10, 'change_pct' => 100.0],
            'payments' => ['attempts' => 20, 'successful_attempts' => 15, 'success_rate_pct' => 75.0],
            'reviews' => ['count' => 4, 'average_rating' => 3.0],
            'traffic' => ['recorded_public_page_views' => 0],
        ]);

        $this->assertGreaterThanOrEqual(5, count($recs));
        $this->assertSame('high', $recs[0]['priority']);
        $this->assertSame('admin.approvals.index', $recs[0]['route']);
        $this->assertSame('high', $recs[1]['priority']);
        $this->assertSame('admin.operations.bookings', $recs[1]['route']);
        $this->assertStringContainsString('manual', strtolower($recs[0]['action']));
    }
}
