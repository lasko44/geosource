<?php

namespace App\Policies;

use App\Models\CitationQuery;
use App\Models\User;
use App\Services\Citation\CitationService;

class CitationQueryPolicy
{
    public function __construct(
        private CitationService $citationService,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->citationService->canAccessCitations($user);
    }

    public function view(User $user, CitationQuery $query): bool
    {
        return $this->canAccess($user, $query);
    }

    public function create(User $user): bool
    {
        return $this->citationService->canAccessCitations($user);
    }

    public function update(User $user, CitationQuery $query): bool
    {
        return $query->user_id === $user->id;
    }

    public function delete(User $user, CitationQuery $query): bool
    {
        return $query->user_id === $user->id;
    }

    public function runCheck(User $user, CitationQuery $query): bool
    {
        return $this->canAccess($user, $query);
    }

    private function canAccess(User $user, CitationQuery $query): bool
    {
        if ($user->is_admin) {
            return true;
        }

        if ($query->user_id === $user->id) {
            return true;
        }

        if ($query->team_id && $user->allTeams()->contains('id', $query->team_id)) {
            return true;
        }

        return false;
    }
}
