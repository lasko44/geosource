<?php

namespace App\Policies;

use App\Models\Scan;
use App\Models\User;

class ScanPolicy
{
    /**
     * Determine if the user can view any scans.
     */
    public function viewAny(User $user): bool
    {
        return true; // Authenticated users can view their own scan list
    }

    /**
     * Determine if the user can view the scan.
     */
    public function view(User $user, Scan $scan): bool
    {
        return $this->canAccessScan($user, $scan);
    }

    /**
     * Determine if the user can update the scan.
     */
    public function update(User $user, Scan $scan): bool
    {
        return $this->canAccessScan($user, $scan);
    }

    /**
     * Determine if the user can delete the scan.
     */
    public function delete(User $user, Scan $scan): bool
    {
        // Only the scan creator or team owner/admin can delete
        if ($scan->user_id === $user->id) {
            return true;
        }

        // For team scans, check if user is team owner or admin
        if ($scan->team_id) {
            $team = $scan->team;
            if ($team && ($team->owner_id === $user->id || $team->getUserRole($user) === 'admin')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user can access the scan (view/update).
     * User can access if they created the scan OR are a member of the scan's team.
     */
    private function canAccessScan(User $user, Scan $scan): bool
    {
        // Admins can access everything
        if ($user->is_admin) {
            return true;
        }

        // User created this scan
        if ($scan->user_id === $user->id) {
            return true;
        }

        // For team scans, check if user is a member of the team
        if ($scan->team_id) {
            return $user->allTeams()->contains('id', $scan->team_id);
        }

        return false;
    }
}
