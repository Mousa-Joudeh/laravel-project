<?php

namespace App\Http\Controllers\Provider\Concerns;

use App\Models\Package;
use App\Models\Service;
use App\Models\Studio;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Shared helpers for the provider area: ownership checks, rating lookups
 * and image storage. Every provider controller uses this trait.
 */
trait ScopesToProvider
{
    protected function ownStudio(Studio $studio): Studio
    {
        abort_unless((int) $studio->provider_id === (int) auth()->id(), 404);

        return $studio;
    }

    protected function ownService(Service $service): Service
    {
        abort_unless((int) $service->studio?->provider_id === (int) auth()->id(), 404);

        return $service;
    }

    protected function ownPackage(Package $package): Package
    {
        abort_unless((int) $package->studio?->provider_id === (int) auth()->id(), 404);

        return $package;
    }

    /** Studios that belong to the signed-in provider. */
    protected function myStudios()
    {
        return Studio::where('provider_id', auth()->id());
    }

    /**
     * Average rating and review count for services or packages.
     * Reviews belong to bookings, so we reach them through booking_items.
     *
     * @param  'service_id'|'package_id'  $column
     */
    protected function ratingMap(string $column, iterable $ids): Collection
    {
        $ids = collect($ids)->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return DB::table('booking_items')
            ->join('reviews', 'reviews.booking_id', '=', 'booking_items.booking_id')
            ->whereIn("booking_items.$column", $ids)
            ->whereNull('reviews.deleted_at')
            ->whereNull('booking_items.deleted_at')
            ->groupBy("booking_items.$column")
            ->selectRaw("booking_items.$column as id, AVG(reviews.rating) as avg, COUNT(*) as cnt")
            ->get()
            ->keyBy('id');
    }

    /** Store uploaded files on the public disk and return their paths. */
    protected function storeFiles(array $files, string $directory): array
    {
        return collect($files)
            ->map(fn ($file) => $file->store($directory, 'public'))
            ->all();
    }

    protected function deleteFile(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
