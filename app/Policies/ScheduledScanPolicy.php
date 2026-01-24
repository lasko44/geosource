<?php

namespace App\Policies;

use App\Models\ScheduledScan;
use App\Models\User;

class ScheduledScanPolicy
{
    /**
     * Determine whether the user can view the scheduled scan.
     */
    public function view(User $user, ScheduledScan $scheduledScan): bool
    {
        // Owner can always view
        if ($scheduledScan->user_id === $user->id) {
            return true;
        }

        // Team members can view team scheduled scans
        if ($scheduledScan->team_id) {
            return $scheduledScan->team->hasMember($user);
        }

        return false;
    }

    /**
     * Determine whether the user can update the scheduled scan.
     */
    public function update(User $user, ScheduledScan $scheduledScan): bool
    {
        // Owner can always update
        if ($scheduledScan->user_id === $user->id) {
            return true;
        }

        // Team admins can update team scheduled scans
        if ($scheduledScan->team_id) {
            return $scheduledScan->team->isAdmin($user);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the scheduled scan.
     */
    public function delete(User $user, ScheduledScan $scheduledScan): bool
    {
        // Owner can always delete
        if ($scheduledScan->user_id === $user->id) {
            return true;
        }

        // Team admins can delete team scheduled scans
        if ($scheduledScan->team_id) {
            return $scheduledScan->team->isAdmin($user);
        }

        return false;
    }
}
