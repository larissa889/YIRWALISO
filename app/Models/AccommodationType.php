<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccommodationType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relations
    public function accommodations(): HasMany
    {
        return $this->hasMany(Accommodation::class, 'accommodation_type_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helpers
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
