<?php

namespace App\Policies;

use App\Models\CitationResearch;
use App\Models\User;
use App\Services\Citation\CitationResearchService;

/**
 * Authorizes actions on citation research resources.
 */
class CitationResearchPolicy
{
    public function __construct(
        private CitationResearchService $researchService,
    ) {}

    /**
     * Determine if the user can view any research.
     */
    public function viewAny(User $user): bool
    {
        return $this->researchService->canAccessResearch($user);
    }

    /**
     * Determine if the user can view the research.
     */
    public function view(User $user, CitationResearch $research): bool
    {
        return $this->canAccess($user, $research);
    }

    /**
     * Determine if the user can create research.
     */
    public function create(User $user): bool
    {
        return $this->researchService->canAccessResearch($user);
    }

    /**
     * Determine if the user can delete the research.
     */
    public function delete(User $user, CitationResearch $research): bool
    {
        // Only the owner can delete
        return $research->user_id === $user->id;
    }

    /**
     * Check if user can access the research.
     */
    private function canAccess(User $user, CitationResearch $research): bool
    {
        if ($user->is_admin) {
            return true;
        }

        // Owner can always access
        if ($research->user_id === $user->id) {
            return true;
        }

        // Team members can access team research
        if ($research->team_id && $user->allTeams()->contains('id', $research->team_id)) {
            return true;
        }

        return false;
    }
}
