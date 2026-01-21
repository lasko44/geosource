<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Team extends Model
{
    use HasFactory, SoftDeletes;

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
        'company_name',
        'logo_path',
        'primary_color',
        'secondary_color',
        'report_footer',
        'contact_email',
        'website_url',
    ];

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
     * Get all documents belonging to the team.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
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
     * Get all invitations for the team.
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }

    /**
     * Get pending invitations for the team.
     */
    public function pendingInvitations(): HasMany
    {
        return $this->invitations()->pending();
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
     * Get the maximum number of team seats allowed based on owner's plan.
     */
    public function getMaxSeats(): int
    {
        return $this->owner->getLimit('team_members') ?? 1;
    }

    /**
     * Get the current number of seats used (members + pending invitations).
     */
    public function getUsedSeats(): int
    {
        // Count all members (excluding owner who doesn't take a seat)
        $membersCount = $this->members()->where('user_id', '!=', $this->owner_id)->count();

        // Count pending invitations
        $pendingCount = $this->pendingInvitations()->count();

        return $membersCount + $pendingCount;
    }

    /**
     * Get the number of available seats.
     */
    public function getAvailableSeats(): int
    {
        $maxSeats = $this->getMaxSeats();

        // Unlimited seats
        if ($maxSeats === -1) {
            return -1;
        }

        return max(0, $maxSeats - $this->getUsedSeats());
    }

    /**
     * Check if the team can add more members.
     */
    public function canAddMember(): bool
    {
        $maxSeats = $this->getMaxSeats();

        // Unlimited seats
        if ($maxSeats === -1) {
            return true;
        }

        return $this->getUsedSeats() < $maxSeats;
    }

    /**
     * Check if the team has collaboration features enabled.
     */
    public function hasCollaborationEnabled(): bool
    {
        return $this->getMaxSeats() > 1 || $this->getMaxSeats() === -1;
    }

    /**
     * Check if the team is over its seat limit (e.g., after subscription downgrade).
     */
    public function isOverSeatLimit(): bool
    {
        $maxSeats = $this->getMaxSeats();

        // Unlimited seats
        if ($maxSeats === -1) {
            return false;
        }

        return $this->getUsedSeats() > $maxSeats;
    }

    /**
     * Get the number of seats over the limit.
     */
    public function getSeatsOverLimit(): int
    {
        $maxSeats = $this->getMaxSeats();

        // Unlimited seats
        if ($maxSeats === -1) {
            return 0;
        }

        return max(0, $this->getUsedSeats() - $maxSeats);
    }

    /**
     * Check if team has white label enabled (based on owner's plan).
     */
    public function hasWhiteLabel(): bool
    {
        return $this->owner->hasFeature('white_label');
    }

    /**
     * Get white label settings for reports.
     */
    public function getWhiteLabelSettings(): array
    {
        if (! $this->hasWhiteLabel()) {
            return [
                'enabled' => false,
                'company_name' => config('app.name'),
                'logo_url' => null,
                'primary_color' => '#3b82f6',
                'secondary_color' => '#1e40af',
                'report_footer' => null,
                'contact_email' => null,
                'website_url' => config('app.url'),
            ];
        }

        return [
            'enabled' => true,
            'company_name' => $this->company_name ?: $this->name,
            'logo_url' => $this->logo_path ? asset('storage/'.$this->logo_path) : null,
            'primary_color' => $this->primary_color ?: '#3b82f6',
            'secondary_color' => $this->secondary_color ?: '#1e40af',
            'report_footer' => $this->report_footer,
            'contact_email' => $this->contact_email ?: $this->owner->email,
            'website_url' => $this->website_url,
        ];
    }

    /**
     * Get the logo URL for reports.
     */
    public function getLogoUrl(): ?string
    {
        if ($this->logo_path) {
            return asset('storage/'.$this->logo_path);
        }

        return null;
    }
}
