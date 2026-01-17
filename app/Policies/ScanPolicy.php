<?php

namespace App\Policies;

use App\Models\Scan;
use App\Models\User;

class ScanPolicy
{
    /**
     * Determine if the user can view the scan.
     */
    public function view(User $user, Scan $scan): bool
    {
        return $this->belongsToUserTeam($user, $scan);
    }

    /**
     * Determine if the user can update the scan.
     */
    public function update(User $user, Scan $scan): bool
    {
        return $this->belongsToUserTeam($user, $scan);
    }

    /**
     * Determine if the user can delete the scan.
     */
    public function delete(User $user, Scan $scan): bool
    {
        return $this->belongsToUserTeam($user, $scan);
    }

    /**
     * Check if scan belongs to user's team.
     */
    private function belongsToUserTeam(User $user, Scan $scan): bool
    {
        if ($scan->team_id === null) {
            return true;
        }

        $userTeamIds = $user->allTeams()->pluck('id')->toArray();

        return in_array($scan->team_id, $userTeamIds);
    }
}
