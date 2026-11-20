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
use Illuminate\Support\Facades\Auth;

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
                    Package::class => ['images', 'studio'],
                    Service::class => ['studio'],
                ]);
            }])
            ->latest('id')
            ->get();

        $favoritesByType = $favorites->groupBy('favoritable_type');

        return view('customer.favorites.index', [
            'favorites' => $favorites,
            'spaces' => $favoritesByType->get((new Studio())->getMorphClass(), collect()),
            'packages' => $favoritesByType->get((new Package())->getMorphClass(), collect()),
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