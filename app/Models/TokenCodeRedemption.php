<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Records when a user redeems a token code.
 */
class TokenCodeRedemption extends Model
{
    use HasFactory;

    protected $fillable = [
        'token_code_id',
        'user_id',
        'tokens_received',
    ];

    protected function casts(): array
    {
        return [
            'tokens_received' => 'integer',
        ];
    }

    /**
     * The token code that was redeemed.
     */
    public function tokenCode(): BelongsTo
    {
        return $this->belongsTo(TokenCode::class);
    }

    /**
     * The user who redeemed the code.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
