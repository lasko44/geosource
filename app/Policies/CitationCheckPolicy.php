<?php

namespace App\Policies;

use App\Models\CitationCheck;
use App\Models\User;

/**
 * Authorizes actions on citation check resources.
 */
class CitationCheckPolicy
{
    public function view(User $user, CitationCheck $check): bool
    {
        return $this->canAccess($user, $check);
    }

    private function canAccess(User $user, CitationCheck $check): bool
    {
        if ($user->is_admin) {
            return true;
        }

        if ($check->user_id === $user->id) {
            return true;
        }

        if ($check->team_id && $user->allTeams()->contains('id', $check->team_id)) {
            return true;
        }

        return false;
    }
}
