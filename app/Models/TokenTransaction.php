<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TokenTransaction extends Model
{
    use HasFactory;

    const TYPE_PURCHASE = 'purchase';
    const TYPE_SPEND = 'spend';
    const TYPE_REFUND = 'refund';
    const TYPE_BONUS = 'bonus';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'user_id',
        'type',
        'amount',
        'balance_after',
        'description',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance_after' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Get the user that owns the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get purchases.
     */
    public function scopePurchases($query)
    {
        return $query->where('type', self::TYPE_PURCHASE);
    }

    /**
     * Scope to get spending transactions.
     */
    public function scopeSpending($query)
    {
        return $query->where('type', self::TYPE_SPEND);
    }

    /**
     * Check if this is a credit (positive amount).
     */
    public function isCredit(): bool
    {
        return $this->amount > 0;
    }

    /**
     * Check if this is a debit (negative amount).
     */
    public function isDebit(): bool
    {
        return $this->amount < 0;
    }

    /**
     * Get the absolute amount.
     */
    public function getAbsoluteAmountAttribute(): int
    {
        return abs($this->amount);
    }

    /**
     * Get formatted amount with sign.
     */
    public function getFormattedAmountAttribute(): string
    {
        return ($this->amount >= 0 ? '+' : '') . $this->amount;
    }
}
