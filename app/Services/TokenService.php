<?php

namespace App\Services;

use App\Models\TokenCode;
use App\Models\TokenCodeRedemption;
use App\Models\TokenPackage;
use App\Models\TokenTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Manages token balance, purchases, and spending.
 */
class TokenService
{
    /**
     * Get the token cost for a feature.
     */
    public function getCost(string $feature): int
    {
        return config("tokens.costs.{$feature}", 0);
    }

    /**
     * Get the label for a feature.
     */
    public function getLabel(string $feature): string
    {
        return config("tokens.labels.{$feature}", $feature);
    }

    /**
     * Check if user has enough tokens for a feature.
     */
    public function hasTokensFor(User $user, string $feature): bool
    {
        $cost = $this->getCost($feature);

        // Free features always pass
        if ($cost === 0) {
            return true;
        }

        return $user->token_balance >= $cost;
    }

    /**
     * Get the user's current token balance.
     */
    public function getBalance(User $user): int
    {
        return $user->token_balance ?? 0;
    }

    /**
     * Spend tokens for a feature.
     *
     * @throws \Exception if insufficient balance
     */
    public function spend(User $user, string $feature, array $metadata = []): ?TokenTransaction
    {
        $cost = $this->getCost($feature);

        // Free features don't create transactions
        if ($cost === 0) {
            return null;
        }

        if ($user->token_balance < $cost) {
            throw new \Exception("Insufficient token balance. Required: {$cost}, Available: {$user->token_balance}");
        }

        return DB::transaction(function () use ($user, $feature, $cost, $metadata) {
            // Lock the user row to prevent race conditions
            $user = User::lockForUpdate()->find($user->id);

            $newBalance = $user->token_balance - $cost;

            $user->update(['token_balance' => $newBalance]);

            return TokenTransaction::create([
                'user_id' => $user->id,
                'type' => TokenTransaction::TYPE_SPEND,
                'amount' => -$cost,
                'balance_after' => $newBalance,
                'description' => $this->getLabel($feature),
                'metadata' => array_merge($metadata, ['feature' => $feature]),
            ]);
        });
    }

    /**
     * Spend a specific amount of tokens (not based on feature config).
     */
    public function spendAmount(User $user, int $amount, string $description, array $metadata = []): ?TokenTransaction
    {
        if ($amount <= 0) {
            return null;
        }

        if ($user->token_balance < $amount) {
            throw new \Exception("Insufficient token balance. Required: {$amount}, Available: {$user->token_balance}");
        }

        return DB::transaction(function () use ($user, $amount, $description, $metadata) {
            $user = User::lockForUpdate()->find($user->id);

            $newBalance = $user->token_balance - $amount;

            $user->update(['token_balance' => $newBalance]);

            return TokenTransaction::create([
                'user_id' => $user->id,
                'type' => TokenTransaction::TYPE_SPEND,
                'amount' => -$amount,
                'balance_after' => $newBalance,
                'description' => $description,
                'metadata' => $metadata,
            ]);
        });
    }

    /**
     * Credit tokens from a purchase.
     */
    public function creditPurchase(User $user, TokenPackage $package, string $stripeSessionId): TokenTransaction
    {
        return DB::transaction(function () use ($user, $package, $stripeSessionId) {
            // Lock the user row to prevent race conditions
            $user = User::lockForUpdate()->find($user->id);

            $newBalance = $user->token_balance + $package->tokens;

            $user->update(['token_balance' => $newBalance]);

            Log::info('Tokens credited to user', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'tokens' => $package->tokens,
                'new_balance' => $newBalance,
                'stripe_session_id' => $stripeSessionId,
            ]);

            return TokenTransaction::create([
                'user_id' => $user->id,
                'type' => TokenTransaction::TYPE_PURCHASE,
                'amount' => $package->tokens,
                'balance_after' => $newBalance,
                'description' => "Purchased {$package->name}",
                'metadata' => [
                    'package_id' => $package->id,
                    'package_name' => $package->name,
                    'price_cents' => $package->price_cents,
                    'stripe_session_id' => $stripeSessionId,
                ],
            ]);
        });
    }

    /**
     * Add bonus tokens to a user.
     */
    public function creditBonus(User $user, int $amount, string $reason): TokenTransaction
    {
        return DB::transaction(function () use ($user, $amount, $reason) {
            $user = User::lockForUpdate()->find($user->id);

            $newBalance = $user->token_balance + $amount;

            $user->update(['token_balance' => $newBalance]);

            Log::info('Bonus tokens credited to user', [
                'user_id' => $user->id,
                'amount' => $amount,
                'reason' => $reason,
                'new_balance' => $newBalance,
            ]);

            return TokenTransaction::create([
                'user_id' => $user->id,
                'type' => TokenTransaction::TYPE_BONUS,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'description' => $reason,
                'metadata' => ['reason' => $reason],
            ]);
        });
    }

    /**
     * Refund tokens to a user.
     */
    public function refund(User $user, int $amount, string $reason): TokenTransaction
    {
        return DB::transaction(function () use ($user, $amount, $reason) {
            $user = User::lockForUpdate()->find($user->id);

            $newBalance = $user->token_balance + $amount;

            $user->update(['token_balance' => $newBalance]);

            Log::info('Tokens refunded to user', [
                'user_id' => $user->id,
                'amount' => $amount,
                'reason' => $reason,
                'new_balance' => $newBalance,
            ]);

            return TokenTransaction::create([
                'user_id' => $user->id,
                'type' => TokenTransaction::TYPE_REFUND,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'description' => $reason,
                'metadata' => ['reason' => $reason],
            ]);
        });
    }

    /**
     * Get transaction history for a user.
     */
    public function getHistory(User $user, int $limit = 50): Collection
    {
        return TokenTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get all active token packages.
     */
    public function getPackages(): Collection
    {
        return TokenPackage::active()->ordered()->get();
    }

    /**
     * Get the cost for a scan tier.
     */
    public function getScanCost(string $tier): int
    {
        $feature = config("tokens.scan_tiers.{$tier}");

        if (! $feature) {
            return 0;
        }

        return $this->getCost($feature);
    }

    /**
     * Get the cost for a citation provider.
     */
    public function getCitationCost(string $provider): int
    {
        $feature = config("tokens.citation_providers.{$provider}");

        if (! $feature) {
            return 0;
        }

        return $this->getCost($feature);
    }

    /**
     * Check if user can perform a scan at a given tier.
     */
    public function canScan(User $user, string $tier): bool
    {
        $feature = config("tokens.scan_tiers.{$tier}");

        if (! $feature) {
            return true; // Unknown tier, allow by default
        }

        return $this->hasTokensFor($user, $feature);
    }

    /**
     * Spend tokens for a scan.
     */
    public function spendForScan(User $user, string $tier, array $metadata = []): ?TokenTransaction
    {
        $feature = config("tokens.scan_tiers.{$tier}");

        if (! $feature) {
            return null;
        }

        return $this->spend($user, $feature, $metadata);
    }

    /**
     * Spend tokens for a citation.
     */
    public function spendForCitation(User $user, string $provider, array $metadata = []): ?TokenTransaction
    {
        $feature = config("tokens.citation_providers.{$provider}");

        if (! $feature) {
            return null;
        }

        return $this->spend($user, $feature, $metadata);
    }

    /**
     * Get usage statistics for the user.
     */
    public function getUsageStats(User $user): array
    {
        $thisMonth = TokenTransaction::where('user_id', $user->id)
            ->where('type', TokenTransaction::TYPE_SPEND)
            ->where('created_at', '>=', now()->startOfMonth())
            ->selectRaw('SUM(ABS(amount)) as total_spent, COUNT(*) as transaction_count')
            ->first();

        $allTime = TokenTransaction::where('user_id', $user->id)
            ->selectRaw('
                SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as total_credited,
                SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as total_spent
            ')
            ->first();

        return [
            'current_balance' => $user->token_balance,
            'spent_this_month' => (int) ($thisMonth->total_spent ?? 0),
            'transactions_this_month' => (int) ($thisMonth->transaction_count ?? 0),
            'total_credited' => (int) ($allTime->total_credited ?? 0),
            'total_spent' => (int) ($allTime->total_spent ?? 0),
        ];
    }

    /**
     * Redeem a token code.
     * Uses pessimistic locking to prevent race conditions.
     * Implements exponential backoff and IP-based rate limiting for security.
     *
     * @throws \Exception if redemption fails
     */
    public function redeemCode(User $user, string $code): array
    {
        $code = strtoupper(trim($code));

        // User-based rate limiting with exponential backoff
        $userRateLimitKey = "token_code_attempts:{$user->id}";
        $userAttempts = (int) Cache::get($userRateLimitKey, 0);

        // After 5 attempts, implement exponential backoff (2, 4, 8, 16... minutes, max 60)
        $maxAttempts = 5;
        if ($userAttempts >= $maxAttempts) {
            $backoffMinutes = min((int) pow(2, $userAttempts - $maxAttempts), 60);

            throw new \Exception("Too many redemption attempts. Please wait {$backoffMinutes} minute(s) and try again.");
        }

        // IP-based rate limiting (additional layer of protection)
        $ip = request()?->ip() ?? 'unknown';
        $ipRateLimitKey = "token_code_attempts_ip:{$ip}";
        $ipAttempts = (int) Cache::get($ipRateLimitKey, 0);

        if ($ipAttempts >= 10) {
            Log::warning('Possible token code brute force from IP', [
                'ip' => $ip,
                'user_id' => $user->id,
                'attempts' => $ipAttempts,
            ]);

            throw new \Exception('Too many attempts from this location. Please try again later.');
        }

        // Increment attempt counters with longer expiry for repeated failures
        $userExpiry = $userAttempts >= $maxAttempts
            ? now()->addMinutes(min((int) pow(2, $userAttempts - $maxAttempts + 1), 60))
            : now()->addMinutes(5);

        Cache::put($userRateLimitKey, $userAttempts + 1, $userExpiry);
        Cache::put($ipRateLimitKey, $ipAttempts + 1, now()->addMinutes(15));

        return DB::transaction(function () use ($user, $code) {
            // Lock the token code row to prevent race conditions
            $tokenCode = TokenCode::where('code', $code)->lockForUpdate()->first();

            if (! $tokenCode) {
                throw new \Exception('Invalid code. Please check and try again.');
            }

            // Check if code is valid
            if (! $tokenCode->is_active) {
                throw new \Exception('This code is no longer active.');
            }

            if ($tokenCode->expires_at && $tokenCode->expires_at->isPast()) {
                throw new \Exception('This code has expired.');
            }

            if ($tokenCode->max_uses !== null && $tokenCode->uses_count >= $tokenCode->max_uses) {
                throw new \Exception('This code has reached its maximum number of uses.');
            }

            // Check if user already redeemed this code (using database constraint as backup)
            $existingRedemption = TokenCodeRedemption::where('token_code_id', $tokenCode->id)
                ->where('user_id', $user->id)
                ->exists();

            if ($existingRedemption) {
                throw new \Exception('You have already redeemed this code.');
            }

            // Lock the user row to update balance
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            $newBalance = $lockedUser->token_balance + $tokenCode->tokens;
            $lockedUser->update(['token_balance' => $newBalance]);

            // Create redemption record
            TokenCodeRedemption::create([
                'token_code_id' => $tokenCode->id,
                'user_id' => $lockedUser->id,
                'tokens_received' => $tokenCode->tokens,
            ]);

            // Increment uses count
            $tokenCode->increment('uses_count');

            // For single-use codes, deactivate after use
            if ($tokenCode->type === TokenCode::TYPE_SINGLE && $tokenCode->uses_count >= ($tokenCode->max_uses ?? 1)) {
                $tokenCode->update(['is_active' => false]);
            }

            // Create transaction record
            $transaction = TokenTransaction::create([
                'user_id' => $lockedUser->id,
                'type' => TokenTransaction::TYPE_BONUS,
                'amount' => $tokenCode->tokens,
                'balance_after' => $newBalance,
                'description' => "Redeemed code: {$tokenCode->code}",
                'metadata' => [
                    'code_id' => $tokenCode->id,
                    'code' => $tokenCode->code,
                    'code_type' => $tokenCode->type,
                ],
            ]);

            Log::info('Token code redeemed', [
                'user_id' => $lockedUser->id,
                'code' => $tokenCode->code,
                'tokens' => $tokenCode->tokens,
                'new_balance' => $newBalance,
            ]);

            return [
                'success' => true,
                'tokens' => $tokenCode->tokens,
                'new_balance' => $newBalance,
                'message' => "Successfully redeemed {$tokenCode->tokens} tokens!",
            ];
        });
    }

    /**
     * Create a new token code.
     */
    public function createCode(
        int $tokens,
        string $type = TokenCode::TYPE_PROMO,
        ?string $description = null,
        ?int $maxUses = null,
        ?\DateTime $expiresAt = null,
        ?User $createdBy = null,
        ?string $customCode = null
    ): TokenCode {
        $code = $customCode ? strtoupper($customCode) : TokenCode::generateCode();

        // For single-use codes, default max_uses to 1
        if ($type === TokenCode::TYPE_SINGLE && $maxUses === null) {
            $maxUses = 1;
        }

        return TokenCode::create([
            'code' => $code,
            'type' => $type,
            'description' => $description,
            'tokens' => $tokens,
            'max_uses' => $maxUses,
            'expires_at' => $expiresAt,
            'is_active' => true,
            'created_by' => $createdBy?->id,
        ]);
    }

    /**
     * Generate multiple single-use codes.
     */
    public function generateSingleUseCodes(
        int $count,
        int $tokens,
        ?string $description = null,
        ?\DateTime $expiresAt = null,
        ?User $createdBy = null
    ): array {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = $this->createCode(
                $tokens,
                TokenCode::TYPE_SINGLE,
                $description,
                1,
                $expiresAt,
                $createdBy
            );
        }

        return $codes;
    }
}
