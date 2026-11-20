<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Package;
use App\Models\Service;
use App\Models\Studio;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FavoriteController extends Controller
{
    private const TYPES = [
        'studio' => Studio::class,
        'service' => Service::class,
        'package' => Package::class,
    ];

    public function index()
    {
        $customer = $this->activeCustomer();

        $favorites = $customer->favorites()
            ->whereHasMorph(
                'favoritable',
                array_values(self::TYPES),
                function (Builder $query, string $model) {
                    $this->availableTarget($query, $model);
                }
            )
            ->with(['favoritable' => function (MorphTo $relation) {
                $relation->morphWith([
                    Studio::class => ['images', 'type'],
                    Package::class => ['images', 'studio.images'],
                    Service::class => ['studio'],
                ]);
            }])
            ->latest('id')
            ->get();

        $favoritesByType = $favorites->groupBy('favoritable_type');

        $packages = $favoritesByType->get((new Package())->getMorphClass(), collect());
        $this->preparePackageCards($packages);

        return view('customer.favorites.index', [
            'favorites' => $favorites,
            'spaces' => $favoritesByType->get((new Studio())->getMorphClass(), collect()),
            'packages' => $packages,
            'services' => $favoritesByType->get((new Service())->getMorphClass(), collect()),
        ]);
    }

    public function store(Request $request)
    {
        $customer = $this->activeCustomer();

        $validated = $request->validate([
            'type' => 'required|in:studio,service,package',
            'id' => 'required|integer|min:1',
        ]);

        $model = self::TYPES[$validated['type']];
        $target = $this->availableTarget($model::query(), $model)
            ->findOrFail($validated['id']);

        $favorite = $customer->favorites()
            ->withTrashed()
            ->firstOrCreate([
                'favoritable_type' => $target->getMorphClass(),
                'favoritable_id' => $target->getKey(),
            ]);

        if ($favorite->trashed()) {
            $favorite->restore();
        }

        return back()->with('success', 'Item saved to your favorites!');
    }

    public function destroy(Favorite $favorite)
    {
        $customer = $this->activeCustomer();

        abort_unless((int) $favorite->user_id === (int) $customer->id, 404);

        $favorite->delete();

        return back()->with('success', 'Item removed from your favorites!');
    }

    private function preparePackageCards(Collection $favorites): void
    {
        if ($favorites->isEmpty()) {
            return;
        }

        $ratings = DB::table('booking_items as bi')
            ->join('bookings as b', 'b.id', '=', 'bi.booking_id')
            ->join('reviews as r', 'r.booking_id', '=', 'b.id')
            ->whereIn('bi.package_id', $favorites->pluck('favoritable_id'))
            ->whereNull('bi.deleted_at')
            ->whereNull('b.deleted_at')
            ->whereNull('r.deleted_at')
            ->selectRaw('bi.package_id, AVG(r.rating) as average, COUNT(DISTINCT r.booking_id) as total')
            ->groupBy('bi.package_id')
            ->get()
            ->keyBy('package_id');

        foreach ($favorites as $favorite) {
            $package = $favorite->favoritable;
            $rating = $ratings->get($package->id);

            $package->cover_url = $package->images->sortBy('sort_order')->first()?->url()
                ?? $package->studio?->coverUrl()
                ?? asset('images/placeholder.svg');

            $package->rating = round((float) ($rating->average ?? 0), 1);
            $package->reviews_total = (int) ($rating->total ?? 0);
        }
    }

    private function activeCustomer(): User
    {
        $user = Auth::user();

        abort_unless(
            $user instanceof User
                && $user->role === 'customer'
                && $user->status === 'active',
            403
        );

        return $user;
    }

    private function availableTarget(Builder $query, string $model): Builder
    {
        if ($model === Studio::class) {
            return $this->availableStudio($query);
        }

        $query->where('is_active', true);

        if ($model === Service::class) {
            $query->where('status', 'approved');
        }

        return $query->whereHas('studio', function (Builder $studio) {
            $this->availableStudio($studio);
        });
    }

    private function availableStudio(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('status', 'approved')
            ->whereHas('provider', function (Builder $provider) {
                $provider->where('role', 'provider')
                    ->where('status', 'active')
                    ->whereHas('providerProfile', function (Builder $profile) {
                        $profile->where('status', 'approved');
                    });
            });
    }
}