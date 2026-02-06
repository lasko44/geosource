<?php

namespace App\Policies;

use App\Models\GA4Connection;
use App\Models\User;

class GA4ConnectionPolicy
{
    /**
     * Determine if the user can view any connections.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can view the connection.
     */
    public function view(User $user, GA4Connection $connection): bool
    {
        return $this->canAccess($user, $connection);
    }

    /**
     * Determine if the user can create connections.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can update the connection.
     */
    public function update(User $user, GA4Connection $connection): bool
    {
        return $this->canAccess($user, $connection);
    }

    /**
     * Determine if the user can delete the connection.
     * Only the owner can delete, not team members.
     */
    public function delete(User $user, GA4Connection $connection): bool
    {
        if ($user->is_admin) {
            return true;
        }

        return $connection->user_id === $user->id;
    }

    /**
     * Determine if the user can sync the connection.
     */
    public function sync(User $user, GA4Connection $connection): bool
    {
        return $this->canAccess($user, $connection);
    }

    /**
     * Check if user can access the connection.
     */
    private function canAccess(User $user, GA4Connection $connection): bool
    {
        if ($user->is_admin) {
            return true;
        }

        if ($connection->user_id === $user->id) {
            return true;
        }

        if ($connection->team_id) {
            return $user->allTeams()->contains('id', $connection->team_id);
        }

        return false;
    }
}
