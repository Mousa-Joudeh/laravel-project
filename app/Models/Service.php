<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'studio_id',
        'title',
        'slug',
        'specialty',
        'description',
        'image',
        'kind',
        'delivery_mode',
        'pricing_type',
        'price',
        'min_hours',
        'max_hours',
        'price_includes_studio',
        'resource_group',
        'is_active',
        'status',
        'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:3',
            'min_hours' => 'integer',
            'max_hours' => 'integer',
            'price_includes_studio' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function studio(): BelongsTo
    {
        return $this->belongsTo(Studio::class);
    }

    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class, 'package_services', 'service_id', 'package_id')
            ->withTimestamps();
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(Availability::class);
    }

    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function favorites(): MorphMany
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    public const SPECIALTIES = [
        'photographer'   => 'Photographer',
        'videographer'   => 'Videographer',
        'sound_engineer' => 'Sound engineer',
        'editor'         => 'Editor',
        'lighting'       => 'Lighting technician',
        'makeup_artist'  => 'Makeup artist',
        'stylist'        => 'Stylist',
        'assistant'      => 'Assistant',
        'other'          => 'Other',
    ];

    public function specialtyLabel(): string
    {
        return self::SPECIALTIES[$this->specialty] ?? ucfirst(str_replace('_', ' ', (string) $this->specialty));
    }

    public function deliveryLabel(): string
    {
        return ['at_studio' => 'At the studio', 'at_client' => 'At the client', 'online' => 'Online'][$this->delivery_mode] ?? '';
    }

    public function priceSuffix(): string
    {
        return $this->pricing_type === 'hourly' ? '/ h' : 'fixed';
    }

    public function imageUrl(): string
    {
        return $this->image ? asset('storage/' . ltrim($this->image, '/')) : asset('images/placeholder.svg');
    }
}
