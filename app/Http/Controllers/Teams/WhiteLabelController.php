<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\UpdateWhiteLabelRequest;
use App\Http\Requests\Teams\UploadLogoRequest;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages white label branding settings for teams.
 */
class WhiteLabelController extends Controller
{
    /**
     * Show the white label settings page.
     */
    public function edit(Team $team): Response
    {
        $user = auth()->user();

        // White label is only available to team owners
        if (! $team->isOwner($user)) {
            abort(403, 'Only team owners can manage white label settings.');
        }

        // Check if user has white label feature
        if (! $user->hasFeature('white_label')) {
            abort(403, 'White label reports are not available on your current plan.');
        }

        return Inertia::render('teams/WhiteLabel', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'company_name' => $team->company_name,
                'logo_path' => $team->logo_path,
                'logo_url' => $team->getLogoUrl(),
                'primary_color' => $team->primary_color ?? '#3b82f6',
                'secondary_color' => $team->secondary_color ?? '#1e40af',
                'report_footer' => $team->report_footer,
                'contact_email' => $team->contact_email,
                'website_url' => $team->website_url,
            ],
        ]);
    }

    /**
     * Update white label settings.
     */
    public function update(UpdateWhiteLabelRequest $request, Team $team): RedirectResponse
    {
        $user = auth()->user();

        // White label is only available to team owners
        if (! $team->isOwner($user)) {
            abort(403, 'Only team owners can manage white label settings.');
        }

        // Check if user has white label feature
        if (! $user->hasFeature('white_label')) {
            abort(403, 'White label reports are not available on your current plan.');
        }

        $team->update($request->getValidatedSettings());

        return back()->with('success', 'White label settings updated successfully.');
    }

    /**
     * Upload logo for white label reports.
     */
    public function uploadLogo(UploadLogoRequest $request, Team $team): RedirectResponse
    {
        $user = auth()->user();

        // White label is only available to team owners
        if (! $team->isOwner($user)) {
            abort(403, 'Only team owners can manage white label settings.');
        }

        // Check if user has white label feature
        if (! $user->hasFeature('white_label')) {
            abort(403, 'White label reports are not available on your current plan.');
        }

        // Delete old logo if exists
        if ($team->logo_path) {
            Storage::disk('public')->delete($team->logo_path);
        }

        // Store new logo
        $path = $request->getLogo()->store('team-logos', 'public');

        $team->update(['logo_path' => $path]);

        return back()->with('success', 'Logo uploaded successfully.');
    }

    /**
     * Remove logo from white label settings.
     */
    public function removeLogo(Team $team): RedirectResponse
    {
        $user = auth()->user();

        // White label is only available to team owners
        if (! $team->isOwner($user)) {
            abort(403, 'Only team owners can manage white label settings.');
        }

        // Check if user has white label feature
        if (! $user->hasFeature('white_label')) {
            abort(403, 'White label reports are not available on your current plan.');
        }

        if ($team->logo_path) {
            Storage::disk('public')->delete($team->logo_path);
            $team->update(['logo_path' => null]);
        }

        return back()->with('success', 'Logo removed successfully.');
    }
}
