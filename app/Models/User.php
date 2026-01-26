<?php

namespace App\Models;

use App\Services\SubscriptionService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use Billable, HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'timezone',
        'google_id',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'remember_token',
        'stripe_id',
        'pm_type',
        'pm_last_four',
        'trial_ends_at',
        'email_verified_at',
        'is_admin',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Get all scans by this user.
     */
    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class);
    }

    /**
     * Get the teams owned by the user.
     */
    public function ownedTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'owner_id');
    }

    /**
     * Get all teams the user belongs to.
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Get all teams (owned and member of).
     */
    public function allTeams()
    {
        return $this->ownedTeams->merge($this->teams);
    }

    /**
     * Check if the user belongs to a team.
     */
    public function belongsToTeam(Team $team): bool
    {
        return $team->hasMember($this);
    }

    /**
     * Get the subscription service instance.
     */
    protected function subscriptionService(): SubscriptionService
    {
        return app(SubscriptionService::class);
    }

    /**
     * Get the user's current plan key.
     */
    public function getPlanKey(): string
    {
        return $this->subscriptionService()->getPlanKey($this);
    }

    /**
     * Get the user's plan configuration.
     */
    public function getPlan(): array
    {
        return $this->subscriptionService()->getPlan($this);
    }

    /**
     * Check if user can perform a scan.
     */
    public function canScan(): bool
    {
        return $this->subscriptionService()->canScan($this);
    }

    /**
     * Get remaining scans for this month.
     */
    public function getScansRemaining(): int
    {
        return $this->subscriptionService()->getScansRemaining($this);
    }

    /**
     * Check if user has a specific feature.
     */
    public function hasFeature(string $feature): bool
    {
        return $this->subscriptionService()->hasFeature($this, $feature);
    }

    /**
     * Get a specific limit for the user.
     */
    public function getLimit(string $limit): mixed
    {
        return $this->subscriptionService()->getLimit($this, $limit);
    }

    /**
     * Get usage summary for the user.
     */
    public function getUsageSummary(): array
    {
        return $this->subscriptionService()->getUsageSummary($this);
    }

    /**
     * Check if user is on free tier.
     */
    public function isFreeTier(): bool
    {
        return $this->subscriptionService()->isFreeTier($this);
    }

    /**
     * Check if user has a paid subscription.
     */
    public function hasPaidSubscription(): bool
    {
        return $this->subscriptionService()->hasPaidSubscription($this);
    }

    /**
     * Check if user should see upgrade prompt.
     */
    public function shouldShowUpgradePrompt(): bool
    {
        return $this->subscriptionService()->shouldShowUpgradePrompt($this);
    }

    /**
     * Check if user has unlimited access (admin or agency).
     */
    public function hasUnlimitedAccess(): bool
    {
        return $this->is_admin || $this->subscriptionService()->isAgencyTier($this);
    }

    /**
     * Get the marketing unsubscribe record for this user.
     */
    public function marketingUnsubscribe(): HasOne
    {
        return $this->hasOne(MarketingUnsubscribe::class);
    }

    /**
     * Check if user is unsubscribed from marketing emails.
     */
    public function isUnsubscribedFromMarketing(): bool
    {
        return $this->marketingUnsubscribe()->exists();
    }
}
