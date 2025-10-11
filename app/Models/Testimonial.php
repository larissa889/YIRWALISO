<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Testimonial extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'author_name',
        'author_position',
        'author_company',
        'author_avatar',
        'content',
        'rating',
        'is_featured',
        'is_approved',
        'user_id',
        'booking_id',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_featured' => 'boolean',
        'is_approved' => 'boolean',
    ];

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeWithRating($query, $minRating = 4)
    {
        return $query->where('rating', '>=', $minRating);
    }

    public function scopeRecent($query, $limit = 5)
    {
        return $query->orderBy('created_at', 'desc')->take($limit);
    }

    // Helpers
    public function getAuthorAvatarUrlAttribute(): ?string
    {
        if (!$this->author_avatar) {
            return null;
        }

        if (filter_var($this->author_avatar, FILTER_VALIDATE_URL)) {
            return $this->author_avatar;
        }

        return asset('storage/' . $this->author_avatar);
    }

    public function getRatingStarsAttribute(): string
    {
        $fullStars = str_repeat('★', $this->rating);
        $emptyStars = str_repeat('☆', 5 - $this->rating);
        return $fullStars . $emptyStars;
    }

    public function approve(): void
    {
        $this->update(['is_approved' => true]);
    }

    public function unapprove(): void
    {
        $this->update(['is_approved' => false]);
    }

    public function feature(): void
    {
        $this->update(['is_featured' => true]);
    }

    public function unfeature(): void
    {
        $this->update(['is_featured' => false]);
    }
}
