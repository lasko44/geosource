<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MarketingUnsubscribe extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'user_id',
        'reason',
        'unsubscribe_token',
        'unsubscribed_at',
    ];

    protected $casts = [
        'unsubscribed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($unsubscribe) {
            if (empty($unsubscribe->unsubscribe_token)) {
                $unsubscribe->unsubscribe_token = Str::random(64);
            }
            if (empty($unsubscribe->unsubscribed_at)) {
                $unsubscribe->unsubscribed_at = now();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if an email is unsubscribed.
     */
    public static function isUnsubscribed(string $email): bool
    {
        return static::where('email', $email)->exists();
    }

    /**
     * Generate an unsubscribe token for a user.
     */
    public static function generateTokenForUser(User $user): string
    {
        $token = Str::random(64);

        // Store the token temporarily (or we can use signed URLs instead)
        cache()->put("unsubscribe_token_{$token}", $user->email, now()->addDays(30));

        return $token;
    }
}
