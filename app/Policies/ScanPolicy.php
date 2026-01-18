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
        return $this->belongsToUser($user, $scan);
    }

    /**
     * Determine if the user can update the scan.
     */
    public function update(User $user, Scan $scan): bool
    {
        return $this->belongsToUser($user, $scan);
    }

    /**
     * Determine if the user can delete the scan.
     */
    public function delete(User $user, Scan $scan): bool
    {
        return $this->belongsToUser($user, $scan);
    }

    /**
     * Check if scan belongs to user.
     */
    private function belongsToUser(User $user, Scan $scan): bool
    {
        return $scan->user_id === $user->id;
    }
}
