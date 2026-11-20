<?php

namespace App\Services\Admin;

use App\Models\Package;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\Studio;

class HeaderAlertsService
{
    public function summary(): array
    {
        $groups = [
            'partners' => [ProviderProfile::class, 'status', 'pending', 'company_name', 'bi-buildings', 'Partner awaiting review'],
            'studios' => [Studio::class, 'status', 'pending', 'name', 'bi-camera', 'Studio awaiting review'],
            'services' => [Service::class, 'status', 'pending', 'title', 'bi-tools', 'Service awaiting review'],
            'packages' => [Package::class, 'is_active', false, 'name', 'bi-box-seam', 'Inactive package to inspect'],
        ];

        $counts = [];
        $items = collect();

        foreach ($groups as $type => [$model, $column, $value, $nameColumn, $icon, $description]) {
            $counts[$type] = $model::query()->where($column, $value)->count();

            if ($counts[$type] === 0) {
                continue;
            }

            $records = $model::query()
                ->where($column, $value)
                ->latest('created_at')
                ->limit(3)
                ->get(['id', $nameColumn, 'created_at']);

            foreach ($records as $record) {
                $items->push([
                    'type' => $type,
                    'title' => (string) ($record->{$nameColumn} ?: ucfirst($type) . ' #' . $record->id),
                    'description' => $description,
                    'icon' => $icon,
                    'date' => $record->created_at?->diffForHumans() ?? 'Recently',
                    'created_at' => $record->created_at,
                ]);
            }
        }

        return [
            'total' => array_sum($counts),
            'counts' => $counts,
            'items' => $items->sortByDesc('created_at')->take(9)->values()->all(),
        ];
    }
}
