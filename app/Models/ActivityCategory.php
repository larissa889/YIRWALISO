<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ActivityCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'is_active',
        'parent_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relations
    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class, 'activity_activity_category')
            ->withTimestamps();
    }

    public function parent()
    {
        return $this->belongsTo(ActivityCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ActivityCategory::class, 'parent_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeParentCategories($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeChildCategories($query)
    {
        return $query->whereNotNull('parent_id');
    }

    // Helpers
    public function isParent(): bool
    {
        return is_null($this->parent_id);
    }

    public function isChild(): bool
    {
        return !is_null($this->parent_id);
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

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [];
        $current = $this;

        while ($current) {
            array_unshift($breadcrumbs, $current);
            $current = $current->parent;
        }

        return $breadcrumbs;
    }

    public function getFullPath(string $separator = ' > '): string
    {
        $breadcrumbs = $this->getBreadcrumbs();
        return implode($separator, array_map(fn($cat) => $cat->name, $breadcrumbs));
    }
}
