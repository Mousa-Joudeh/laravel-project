<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProviderProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'company_name',
        'slug',
        'tagline',
        'bio',
        'city',
        'logo',
        'cover',
        'instagram',
        'status',
        'admin_note',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function initials(): string
    {
        return \Illuminate\Support\Str::of($this->company_name)
            ->explode(' ')->filter()->take(2)
            ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    }

    public function logoUrl(): ?string
    {
        if (! $this->logo) {
            return null;
        }

        return str_starts_with($this->logo, 'http') ? $this->logo : asset('storage/' . ltrim($this->logo, '/'));
    }

    public function coverUrl(): ?string
    {
        if (! $this->cover) {
            return null;
        }

        return str_starts_with($this->cover, 'http') ? $this->cover : asset('storage/' . ltrim($this->cover, '/'));
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
