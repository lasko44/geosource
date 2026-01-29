<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TokenCode extends Model
{
    use HasFactory;

    public const TYPE_PROMO = 'promo';   // Multi-use promotional code
    public const TYPE_SINGLE = 'single'; // Single-use code for individual users

    protected $fillable = [
        'code',
        'type',
        'description',
        'tokens',
        'max_uses',
        'uses_count',
        'expires_at',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tokens' => 'integer',
            'max_uses' => 'integer',
            'uses_count' => 'integer',
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Generate a unique code.
     */
    public static function generateCode(int $length = 8): string
    {
        do {
            $code = strtoupper(Str::random($length));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /**
     * The user who created this code.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Redemptions of this code.
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(TokenCodeRedemption::class);
    }

    /**
     * Check if code is valid for redemption.
     */
    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses !== null && $this->uses_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Check if a user has already redeemed this code.
     */
    public function hasBeenRedeemedBy(User $user): bool
    {
        return $this->redemptions()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if code can be redeemed by user.
     */
    public function canBeRedeemedBy(User $user): array
    {
        if (! $this->isValid()) {
            if (! $this->is_active) {
                return ['valid' => false, 'reason' => 'This code is no longer active.'];
            }
            if ($this->expires_at && $this->expires_at->isPast()) {
                return ['valid' => false, 'reason' => 'This code has expired.'];
            }
            if ($this->max_uses !== null && $this->uses_count >= $this->max_uses) {
                return ['valid' => false, 'reason' => 'This code has reached its maximum number of uses.'];
            }
        }

        if ($this->hasBeenRedeemedBy($user)) {
            return ['valid' => false, 'reason' => 'You have already redeemed this code.'];
        }

        return ['valid' => true, 'reason' => null];
    }

    /**
     * Increment the uses count.
     */
    public function incrementUses(): void
    {
        $this->increment('uses_count');
    }

    /**
     * Scope to get only active codes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only non-expired codes.
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope to get codes with remaining uses.
     */
    public function scopeHasRemainingUses($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('max_uses')
              ->orWhereColumn('uses_count', '<', 'max_uses');
        });
    }

    /**
     * Scope for valid codes.
     */
    public function scopeValid($query)
    {
        return $query->active()->notExpired()->hasRemainingUses();
    }
}
