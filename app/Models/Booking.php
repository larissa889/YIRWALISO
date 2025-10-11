<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'booking_number',
        'user_id',
        'accommodation_id',
        'activity_id',
        'check_in_date',
        'check_out_date',
        'activity_date',
        'number_of_guests',
        'total_amount',
        'paid_amount',
        'payment_status',
        'booking_status',
        'special_requests',
        'cancellation_reason',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'activity_date' => 'datetime',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'number_of_guests' => 'integer',
    ];

    // Status Constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';

    public const PAYMENT_STATUS_PENDING = 'pending';
    public const PAYMENT_STATUS_PARTIAL = 'partial';
    public const PAYMENT_STATUS_PAID = 'paid';
    public const PAYMENT_STATUS_REFUNDED = 'refunded';
    public const PAYMENT_STATUS_FAILED = 'failed';

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function accommodation(): BelongsTo
    {
        return $this->belongsTo(Accommodation::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // Scopes
    public function scopeUpcoming($query)
    {
        return $query->where('check_in_date', '>=', now())
            ->orWhere('activity_date', '>=', now())
            ->where('booking_status', self::STATUS_CONFIRMED);
    }

    public function scopePending($query)
    {
        return $query->where('booking_status', self::STATUS_PENDING);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('booking_status', self::STATUS_CONFIRMED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('booking_status', self::STATUS_CANCELLED);
    }

    // Helpers
    public function calculateTotalNights(): int
    {
        if (!$this->check_in_date || !$this->check_out_date) {
            return 0;
        }
        
        return $this->check_in_date->diffInDays($this->check_out_date);
    }

    public function calculateTotalAmount(): float
    {
        if ($this->accommodation_id) {
            $nights = $this->calculateTotalNights();
            return $this->accommodation->price_per_night * $nights;
        }
        
        if ($this->activity_id) {
            return $this->activity->price_per_person * $this->number_of_guests;
        }
        
        return 0;
    }

    public function calculateBalance(): float
    {
        return $this->total_amount - $this->paid_amount;
    }

    public function isFullyPaid(): bool
    {
        return $this->paid_amount >= $this->total_amount;
    }

    public function cancel(string $reason = null): void
    {
        $this->update([
            'booking_status' => self::STATUS_CANCELLED,
            'cancellation_reason' => $reason,
        ]);
    }

    public function confirm(): void
    {
        $this->update(['booking_status' => self::STATUS_CONFIRMED]);
    }

    public function addPayment(float $amount, string $method, string $transactionId = null, string $notes = null): Payment
    {
        $payment = $this->payments()->create([
            'amount' => $amount,
            'payment_method' => $method,
            'transaction_id' => $transactionId,
            'notes' => $notes,
            'status' => 'completed',
            'payment_date' => now(),
        ]);

        $this->increment('paid_amount', $amount);
        
        // Update payment status if fully paid
        if ($this->paid_amount >= $this->total_amount) {
            $this->update(['payment_status' => self::PAYMENT_STATUS_PAID]);
        } elseif ($this->paid_amount > 0) {
            $this->update(['payment_status' => self::PAYMENT_STATUS_PARTIAL]);
        }

        return $payment;
    }
}
