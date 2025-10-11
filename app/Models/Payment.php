<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use SoftDeletes;

    // Payment methods
    public const METHOD_CASH = 'cash';
    public const METHOD_CREDIT_CARD = 'credit_card';
    public const METHOD_BANK_TRANSFER = 'bank_transfer';
    public const METHOD_MOBILE_MONEY = 'mobile_money';
    public const METHOD_PAYPAL = 'paypal';
    public const METHOD_STRIPE = 'stripe';
    public const METHOD_OTHER = 'other';

    // Payment statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'booking_id',
        'amount',
        'payment_method',
        'transaction_id',
        'payment_date',
        'status',
        'notes',
        'receipt_url',
        'refunded_amount',
        'refund_reason',
        'processed_by',
        'payment_details',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'refunded_amount' => 'decimal:2',
        'payment_details' => 'array',
    ];

    // Relations
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeRefunded($query)
    {
        return $query->whereIn('status', [self::STATUS_REFUNDED, self::STATUS_PARTIALLY_REFUNDED]);
    }

    public function scopeForMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeBetweenDates($query, $startDate, $endDate = null)
    {
        $endDate = $endDate ?: now();
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    // Helpers
    public static function getPaymentMethods(): array
    {
        return [
            self::METHOD_CASH => 'Espèces',
            self::METHOD_CREDIT_CARD => 'Carte de crédit',
            self::METHOD_BANK_TRANSFER => 'Virement bancaire',
            self::METHOD_MOBILE_MONEY => 'Mobile Money',
            self::METHOD_PAYPAL => 'PayPal',
            self::METHOD_STRIPE => 'Stripe',
            self::METHOD_OTHER => 'Autre',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'En attente',
            self::STATUS_COMPLETED => 'Complété',
            self::STATUS_FAILED => 'Échoué',
            self::STATUS_REFUNDED => 'Remboursé',
            self::STATUS_PARTIALLY_REFUNDED => 'Partiellement remboursé',
            self::STATUS_CANCELLED => 'Annulé',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }

    public function getMethodLabelAttribute(): string
    {
        return self::getPaymentMethods()[$this->payment_method] ?? $this->payment_method;
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2, ',', ' ') . ' FCFA';
    }

    public function getFormattedRefundedAmountAttribute(): ?string
    {
        return $this->refunded_amount ? number_format($this->refunded_amount, 2, ',', ' ') . ' FCFA' : null;
    }

    public function markAsCompleted(): void
    {
        $this->update(['status' => self::STATUS_COMPLETED]);
    }

    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'notes' => $reason ? ($this->notes . "\nÉchec: " . $reason) : $this->notes,
        ]);
    }

    public function refund(float $amount = null, string $reason = null): bool
    {
        $refundAmount = $amount ?? $this->amount;
        
        if ($refundAmount <= 0 || $refundAmount > $this->amount) {
            return false;
        }

        $isFullRefund = $refundAmount === $this->amount;
        
        $this->update([
            'status' => $isFullRefund ? self::STATUS_REFUNDED : self::STATUS_PARTIALLY_REFUNDED,
            'refunded_amount' => $refundAmount,
            'refund_reason' => $reason,
            'notes' => $this->notes . "\nRemboursement: " . ($isFullRefund ? 'Complet' : 'Partiel') . 
                      " (" . number_format($refundAmount, 2, ',', ' ') . " FCFA)" . 
                      ($reason ? ". Raison: $reason" : ''),
        ]);

        return true;
    }
}
