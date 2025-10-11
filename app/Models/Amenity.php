<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Amenity extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'icon',
        'category',
    ];

    // Constants for categories
    public const CATEGORY_GENERAL = 'general';
    public const CATEGORY_BATHROOM = 'bathroom';
    public const CATEGORY_BEDROOM = 'bedroom';
    public const CATEGORY_KITCHEN = 'kitchen';
    public const CATEGORY_ENTERTAINMENT = 'entertainment';
    public const CATEGORY_OUTDOOR = 'outdoor';
    public const CATEGORY_ACCESSIBILITY = 'accessibility';
    public const CATEGORY_SAFETY = 'safety';

    // Relations
    public function accommodations(): BelongsToMany
    {
        return $this->belongsToMany(Accommodation::class, 'accommodation_amenity')
            ->withTimestamps();
    }

    // Scopes
    public function scopeInCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeGeneral($query)
    {
        return $this->scopeInCategory($query, self::CATEGORY_GENERAL);
    }

    public function scopeBathroom($query)
    {
        return $this->scopeInCategory($query, self::CATEGORY_BATHROOM);
    }

    public function scopeBedroom($query)
    {
        return $this->scopeInCategory($query, self::CATEGORY_BEDROOM);
    }

    public function scopeKitchen($query)
    {
        return $this->scopeInCategory($query, self::CATEGORY_KITCHEN);
    }

    public function scopeEntertainment($query)
    {
        return $this->scopeInCategory($query, self::CATEGORY_ENTERTAINMENT);
    }

    public function scopeOutdoor($query)
    {
        return $this->scopeInCategory($query, self::CATEGORY_OUTDOOR);
    }

    public function scopeAccessibility($query)
    {
        return $this->scopeInCategory($query, self::CATEGORY_ACCESSIBILITY);
    }

    public function scopeSafety($query)
    {
        return $this->scopeInCategory($query, self::CATEGORY_SAFETY);
    }

    // Helpers
    public static function getCategories(): array
    {
        return [
            self::CATEGORY_GENERAL => 'Général',
            self::CATEGORY_BATHROOM => 'Salle de bain',
            self::CATEGORY_BEDROOM => 'Chambre',
            self::CATEGORY_KITCHEN => 'Cuisine',
            self::CATEGORY_ENTERTAINMENT => 'Divertissement',
            self::CATEGORY_OUTDOOR => 'Extérieur',
            self::CATEGORY_ACCESSIBILITY => 'Accessibilité',
            self::CATEGORY_SAFETY => 'Sécurité',
        ];
    }

    public function getCategoryNameAttribute(): string
    {
        return self::getCategories()[$this->category] ?? 'Autre';
    }

    public function getIconHtmlAttribute(): string
    {
        if (filter_var($this->icon, FILTER_VALIDATE_URL)) {
            return sprintf('<img src="%s" alt="%s" class="h-6 w-6">', $this->icon, $this->name);
        }

        if (str_starts_with($this->icon, 'fa-')) {
            return sprintf('<i class="fas %s"></i>', $this->icon);
        }

        if (str_starts_with($this->icon, 'icon-')) {
            return sprintf('<i class="%s"></i>', $this->icon);
        }

        return $this->icon ?: '';
    }
}
