<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleTeamContext
{
    /**
     * Handle the team context switching before other middleware runs.
     * This ensures teamBranding is calculated with the correct team.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Check if a team switch is being requested
        $requestedTeamId = $request->input('team');

        if ($requestedTeamId !== null) {
            if ($requestedTeamId === 'personal') {
                session(['current_team_id' => 'personal']);
            } else {
                // Verify user has access to this team before setting
                $team = $user->allTeams()->firstWhere('id', $requestedTeamId);
                if ($team) {
                    session(['current_team_id' => (int) $requestedTeamId]);
                }
            }
        }

        return $next($request);
    }
}
