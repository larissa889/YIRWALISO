<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PortfolioItem extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'designer_profile_id',
        'title',
        'description',
        'project_date',
        'client_name',
        'project_url',
        'technologies',
        'is_featured',
        'views_count',
    ];

    protected $casts = [
        'project_date' => 'date',
        'technologies' => 'array',
        'is_featured' => 'boolean',
        'views_count' => 'integer',
    ];

    /**
     * Register the media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('portfolio_images')
             ->useDisk('public')
             ->singleFile();
             
        $this->addMediaCollection('portfolio_files')
             ->useDisk('public');
    }

    /**
     * Register media conversions.
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
             ->width(368)
             ->height(232)
             ->sharpen(10);
             
        $this->addMediaConversion('large')
             ->width(1024)
             ->height(768);
    }

    /**
     * Get the designer profile that owns the portfolio item.
     */
    public function designerProfile(): BelongsTo
    {
        return $this->belongsTo(DesignerProfile::class);
    }

    /**
     * Get the main image of the portfolio item.
     */
    public function getMainImageAttribute()
    {
        return $this->getFirstMediaUrl('portfolio_images', 'large') ?: 
               asset('images/default-portfolio.jpg');
    }

    /**
     * Get the thumbnail image of the portfolio item.
     */
    public function getThumbnailAttribute()
    {
        return $this->getFirstMediaUrl('portfolio_images', 'thumb') ?: 
               asset('images/default-thumbnail.jpg');
    }

    /**
     * Get all gallery images of the portfolio item.
     */
    public function getGalleryImagesAttribute()
    {
        return $this->getMedia('portfolio_images');
    }

    /**
     * Get all files associated with the portfolio item.
     */
    public function getFilesAttribute()
    {
        return $this->getMedia('portfolio_files');
    }

    /**
     * Increment the view count of the portfolio item.
     */
    public function incrementViewCount()
    {
        $this->increment('views_count');
    }

    /**
     * Scope a query to only include featured portfolio items.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to include only items for a specific client.
     */
    public function scopeForClient($query, $clientName)
    {
        return $query->where('client_name', $clientName);
    }

    /**
     * Scope a query to include only items using specific technologies.
     */
    public function scopeWithTechnologies($query, array $technologies)
    {
        return $query->whereJsonContains('technologies', $technologies);
    }
}
