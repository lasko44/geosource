<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TokenPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'tokens',
        'price_cents',
        'stripe_price_id',
        'savings_percent',
        'is_popular',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'tokens' => 'integer',
        'price_cents' => 'integer',
        'savings_percent' => 'integer',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Scope to get only active packages.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get the price in dollars.
     */
    public function getPriceAttribute(): float
    {
        return $this->price_cents / 100;
    }

    /**
     * Get the price per token.
     */
    public function getPricePerTokenAttribute(): float
    {
        return $this->price_cents / $this->tokens / 100;
    }

    /**
     * Get formatted price string.
     */
    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->price, 2);
    }
}
