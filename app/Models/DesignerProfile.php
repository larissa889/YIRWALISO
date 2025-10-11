<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DesignerProfile extends Model
{
    protected $fillable = [
        'user_id',
        'specialty',
        'bio',
        'years_of_experience',
        'hourly_rate',
        'is_available',
        'next_available_date',
        'skills',
        'website',
        'social_links',
        'working_hours',
        'unavailable_dates',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'next_available_date' => 'datetime',
        'skills' => 'array',
        'social_links' => 'array',
        'working_hours' => 'array',
        'unavailable_dates' => 'array',
        'hourly_rate' => 'float',
        'years_of_experience' => 'integer',
    ];

    /**
     * Get the user that owns the designer profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the portfolio items for the designer.
     */
    public function portfolioItems(): HasMany
    {
        return $this->hasMany(PortfolioItem::class);
    }

    /**
     * Get the reviews for the designer.
     */
    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * Get the designer's projects.
     */
    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get the designer's booked time slots.
     */
    public function bookedSlots()
    {
        return $this->hasMany(BookedSlot::class);
    }

    /**
     * Get the average rating of the designer.
     */
    public function averageRating(): float
    {
        return (float) $this->reviews()->avg('rating') ?? 0;
    }

    /**
     * Check if the designer is currently available.
     */
    public function isCurrentlyAvailable(): bool
    {
        if (!$this->is_available) {
            return false;
        }

        if ($this->next_available_date && now()->lt($this->next_available_date)) {
            return false;
        }

        return true;
    }

    /**
     * Get the designer's featured portfolio items.
     */
    public function featuredPortfolioItems($limit = 3)
    {
        return $this->portfolioItems()
            ->with('media')
            ->latest()
            ->take($limit)
            ->get();
    }
}
