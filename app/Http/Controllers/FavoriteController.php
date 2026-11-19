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
            ->with(['favoritable' => function (MorphTo $relation) {
                $constraints = [];

                foreach (self::TYPES as $model) {
                    $constraints[$model] = function (Builder $query) use ($model) {
                        $this->availableTarget($query, $model);

                        $nameColumn = $model === Service::class ? 'title' : 'name';
                        $query->select('id', $nameColumn);
                    };
                }

                $relation->constrain($constraints);
            }])
            ->latest('id')
            ->paginate(15);

        return view('customer.favorites.index', compact('favorites'));
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

        return redirect()->route('favorites.index')
            ->with('success', 'Item saved to your favorites!');
    }

    public function destroy(Favorite $favorite)
    {
        $customer = $this->activeCustomer();

        abort_unless((int) $favorite->user_id === (int) $customer->id, 404);

        $favorite->delete();

        return redirect()->route('favorites.index')
            ->with('success', 'Item removed from your favorites!');
    }

    private function activeCustomer(): User
    {
        $user = Auth::user();

        if (!($user instanceof User) || $user->role !== 'customer' || $user->status !== 'active') {
            abort(403);
        }

        return $user;
    }

    private function availableTarget(Builder $query, string $model): Builder
    {
        $query->where('is_active', true);

        if ($model === Studio::class || $model === Service::class) {
            $query->where('status', 'approved');
        }

        if ($model !== Studio::class) {
            $query->whereHas('studio', function (Builder $studio) {
                $studio->where('status', 'approved')
                    ->where('is_active', true);
            });
        }

        return $query;
    }
}