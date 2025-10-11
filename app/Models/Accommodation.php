<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Accommodation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_per_night',
        'max_occupancy',
        'bedrooms',
        'bathrooms',
        'has_kitchen',
        'has_wifi',
        'has_parking',
        'is_available',
        'main_image',
        'gallery_images',
        'accommodation_type_id',
    ];

    protected $casts = [
        'has_kitchen' => 'boolean',
        'has_wifi' => 'boolean',
        'has_parking' => 'boolean',
        'is_available' => 'boolean',
        'price_per_night' => 'decimal:2',
        'gallery_images' => 'array',
    ];

    // Relations
    public function type(): BelongsTo
    {
        return $this->belongsTo(AccommodationType::class, 'accommodation_type_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'accommodation_amenity')
            ->withTimestamps();
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeWithKitchen($query)
    {
        return $query->where('has_kitchen', true);
    }

    public function scopeWithWifi($query)
    {
        return $query->where('has_wifi', true);
    }

    public function scopeWithParking($query)
    {
        return $query->where('has_parking', true);
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

    public function isAvailableForDates($checkIn, $checkOut): bool
    {
        return !$this->bookings()
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->whereBetween('check_in_date', [$checkIn, $checkOut])
                    ->orWhereBetween('check_out_date', [$checkIn, $checkOut])
                    ->orWhere(function ($q) use ($checkIn, $checkOut) {
                        $q->where('check_in_date', '<=', $checkIn)
                            ->where('check_out_date', '>=', $checkOut);
                    });
            })
            ->exists();
    }
}
