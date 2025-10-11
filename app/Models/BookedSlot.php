<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookedSlot extends Model
{
    protected $fillable = [
        'designer_profile_id',
        'title',
        'description',
        'start_time',
        'end_time',
        'is_recurring',
        'recurrence_pattern',
        'status',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_recurring' => 'boolean',
    ];

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';

    /**
     * Recurrence pattern constants
     */
    public const RECURRENCE_NONE = 'none';
    public const RECURRENCE_DAILY = 'daily';
    public const RECURRENCE_WEEKLY = 'weekly';
    public const RECURRENCE_MONTHLY = 'monthly';

    /**
     * Get the designer profile that owns the booked slot.
     */
    public function designerProfile(): BelongsTo
    {
        return $this->belongsTo(DesignerProfile::class);
    }

    /**
     * Scope a query to only include upcoming slots.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_time', '>', now())
                    ->where('status', self::STATUS_CONFIRMED);
    }

    /**
     * Check if the slot is in the past.
     */
    public function isPast(): bool
    {
        return $this->end_time->isPast();
    }

    /**
     * Check if the slot is currently ongoing.
     */
    public function isOngoing(): bool
    {
        $now = now();
        return $now->between($this->start_time, $this->end_time);
    }

    /**
     * Get the duration of the slot in minutes.
     */
    public function getDurationInMinutes(): int
    {
        return $this->start_time->diffInMinutes($this->end_time);
    }

    /**
     * Cancel the booked slot.
     */
    public function cancel(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancellation_reason' => $reason,
        ]);
    }

    /**
     * Mark the slot as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update(['status' => self::STATUS_COMPLETED]);
    }
}
