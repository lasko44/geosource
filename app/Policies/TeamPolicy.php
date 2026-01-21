<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    /**
     * Determine whether the user can view the team.
     */
    public function view(User $user, Team $team): bool
    {
        return $team->hasMember($user);
    }

    /**
     * Determine whether the user can update the team.
     */
    public function update(User $user, Team $team): bool
    {
        return $team->isAdmin($user);
    }

    /**
     * Determine whether the user can delete the team.
     */
    public function delete(User $user, Team $team): bool
    {
        return $team->isOwner($user);
    }

    /**
     * Determine whether the user can manage team billing.
     */
    public function manageBilling(User $user, Team $team): bool
    {
        return $team->isOwner($user);
    }

    /**
     * Determine whether the user can manage team members.
     */
    public function manageMembers(User $user, Team $team): bool
    {
        return $team->isAdmin($user);
    }

    /**
     * Determine whether the user can invite new members.
     * Blocked when team is over seat limit due to subscription downgrade.
     */
    public function inviteMembers(User $user, Team $team): bool
    {
        if (! $team->isAdmin($user)) {
            return false;
        }

        // Block invitations when team is over seat limit
        if ($team->isOverSeatLimit()) {
            return false;
        }

        return true;
    }
}
