<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gallery extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'image_path',
        'image_alt',
        'image_caption',
        'type',
        'category',
        'is_featured',
        'display_order',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'display_order' => 'integer',
    ];

    // Constants
    public const TYPE_IMAGE = 'image';
    public const TYPE_VIDEO = 'video';

    // Scopes
    public function scopeImages($query)
    {
        return $query->where('type', self::TYPE_IMAGE);
    }

    public function scopeVideos($query)
    {
        return $query->where('type', self::TYPE_VIDEO);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('created_at', 'desc');
    }

    // Helpers
    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }

    public function isImage(): bool
    {
        return $this->type === self::TYPE_IMAGE;
    }

    public function isVideo(): bool
    {
        return $this->type === self::TYPE_VIDEO;
    }

    public function getEmbedUrlAttribute(): ?string
    {
        if (!$this->isVideo()) {
            return null;
        }

        // Handle YouTube URLs
        if (str_contains($this->image_path, 'youtube.com') || str_contains($this->image_path, 'youtu.be')) {
            $videoId = '';
            
            // Handle youtu.be/ID format
            if (str_contains($this->image_path, 'youtu.be/')) {
                $parts = parse_url($this->image_path);
                $videoId = trim($parts['path'], '/');
            } 
            // Handle youtube.com/watch?v=ID format
            elseif (str_contains($this->image_path, 'youtube.com/watch')) {
                parse_str(parse_url($this->image_path, PHP_URL_QUERY), $params);
                $videoId = $params['v'] ?? '';
            }
            
            return $videoId ? "https://www.youtube.com/embed/{$videoId}" : null;
        }

        // Handle Vimeo URLs
        if (str_contains($this->image_path, 'vimeo.com')) {
            $videoId = '';
            
            // Extract video ID from URL
            $pattern = '/(https?:\/\/)?(www\.)?(player\.)?vimeo\.com\/([a-z]*\/)*([0-9]{6,11})[?]?.*/';
            if (preg_match($pattern, $this->image_path, $matches)) {
                $videoId = $matches[5] ?? '';
            }
            
            return $videoId ? "https://player.vimeo.com/video/{$videoId}" : null;
        }

        return $this->image_path;
    }
}
