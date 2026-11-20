<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'studio_id',
        'name',
        'type',
        'slug',
        'description',
        'pricing_type',
        'price',
        'min_hours',
        'max_hours',
        'is_active',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:3',
            'min_hours' => 'integer',
            'max_hours' => 'integer',
            'is_active' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function studio(): BelongsTo
    {
        return $this->belongsTo(Studio::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'package_services', 'package_id', 'service_id')
            ->withTimestamps();
    }

    public function images(): HasMany
    {
        return $this->hasMany(PackageImage::class);
    }

    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function favorites(): MorphMany
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    public const TYPES = [
        'portrait'   => 'Portrait',
        'wedding'    => 'Wedding',
        'graduation' => 'Graduation',
        'family'     => 'Family',
        'product'    => 'Product',
        'fashion'    => 'Fashion',
        'podcast'    => 'Podcast',
        'event'      => 'Event',
        'other'      => 'Other',
    ];

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
    }

    public function coverUrl(): string
    {
        $image = $this->relationLoaded('images') ? $this->images->first() : $this->images()->orderBy('sort_order')->first();

        return $image ? $image->url() : asset('images/placeholder.svg');
    }
}
