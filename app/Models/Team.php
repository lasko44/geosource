<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;

class Team extends Model
{
    use Billable, HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'description',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'stripe_id',
        'pm_type',
        'pm_last_four',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Team $team) {
            if (empty($team->slug)) {
                $team->slug = Str::slug($team->name);
            }
        });
    }

    /**
     * Get the owner of the team.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get all members of the team (including owner).
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Check if a user is the owner of the team.
     */
    public function isOwner(User $user): bool
    {
        return $this->owner_id === $user->id;
    }

    /**
     * Check if a user is an admin of the team.
     */
    public function isAdmin(User $user): bool
    {
        return $this->isOwner($user) ||
            $this->members()
                ->where('user_id', $user->id)
                ->wherePivot('role', 'admin')
                ->exists();
    }

    /**
     * Check if a user is a member of the team.
     */
    public function hasMember(User $user): bool
    {
        return $this->isOwner($user) ||
            $this->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Get the role of a user in the team.
     */
    public function getUserRole(User $user): ?string
    {
        if ($this->isOwner($user)) {
            return 'owner';
        }

        $member = $this->members()->where('user_id', $user->id)->first();

        return $member?->pivot->role;
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the team's Stripe customer portal URL.
     */
    public function billingPortalUrl(string $returnUrl = null): string
    {
        return $this->billingPortalSession($returnUrl)->url;
    }
}
