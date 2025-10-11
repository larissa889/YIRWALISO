<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Activity extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'highlights',
        'location',
        'price_per_person',
        'duration_hours',
        'min_people',
        'max_people',
        'is_active',
        'main_image',
        'gallery_images',
        'start_time',
        'end_time',
        'included',
        'not_included',
        'requirements',
        'difficulty_level',
    ];

    protected $casts = [
        'price_per_person' => 'decimal:2',
        'duration_hours' => 'float',
        'min_people' => 'integer',
        'max_people' => 'integer',
        'is_active' => 'boolean',
        'gallery_images' => 'array',
        'included' => 'array',
        'not_included' => 'array',
        'requirements' => 'array',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    // Relations
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ActivityCategory::class, 'activity_category')
            ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_time', '>=', now());
    }

    public function scopeForGroupSize($query, $groupSize)
    {
        return $query->where('min_people', '<=', $groupSize)
            ->where('max_people', '>=', $groupSize);
    }

    // Helpers
    public function getMainImageUrlAttribute(): ?string
    {
        return $this->main_image ? asset('storage/' . $this->main_image) : null;
    }

    public function getGalleryImagesUrlsAttribute(): array
    {
        if (empty($this->gallery_images)) {
            return [];
        }

        return array_map(function ($image) {
            return asset('storage/' . $image);
        }, $this->gallery_images);
    }

    public function isAvailableForGroupSize($groupSize): bool
    {
        return $groupSize >= $this->min_people && $groupSize <= $this->max_people;
    }

    public function getFormattedDurationAttribute(): string
    {
        $hours = floor($this->duration_hours);
        $minutes = ($this->duration_hours - $hours) * 60;
        
        $parts = [];
        if ($hours > 0) {
            $parts[] = $hours . ' ' . ($hours === 1 ? 'heure' : 'heures');
        }
        if ($minutes > 0) {
            $parts[] = $minutes . ' minutes';
        }
        
        return implode(' et ', $parts);
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price_per_person, 2, ',', ' ') . ' FCFA';
    }
}
