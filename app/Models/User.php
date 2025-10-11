<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'city',
        'country',
        'postal_code',
        'avatar',
        'role_id',
        'is_active',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    // Relations
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function testimonials()
    {
        return $this->hasMany(Testimonial::class);
    }

    /**
     * Get the designer profile associated with the user.
     */
    public function designerProfile()
    {
        return $this->hasOne(DesignerProfile::class);
    }

    // Helpers
    public function hasRole($roleName)
    {
        return $this->role && $this->role->name === $roleName;
    }

    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    public function isStaff()
    {
        return $this->hasRole('staff');
    }

    public function isClient()
    {
        return $this->hasRole('client');
    }

    /**
     * Check if the user is a designer.
     */
    public function isDesigner(): bool
    {
        return $this->hasRole('designer') || $this->designerProfile()->exists();
    }

    public function hasPermission($permissionName)
    {
        if (!$this->role) return false;
        
        return $this->role->permissions()->where('name', $permissionName)->exists();
    }
}
