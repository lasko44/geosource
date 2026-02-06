<?php

namespace App\Nova\Actions;

use App\Jobs\SendEmailCampaignJob;
use App\Models\EmailCampaign;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Http\Requests\NovaRequest;

class SendEmailCampaign extends Action
{
    /**
     * The displayable name of the action.
     *
     * @var string
     */
    public $name = 'Send Campaign';

    /**
     * Perform the action on the given models.
     *
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        foreach ($models as $campaign) {
            /** @var EmailCampaign $campaign */

            // Use database transaction with lock to prevent race conditions
            try {
                $result = DB::transaction(function () use ($campaign) {
                    // Lock the campaign row and refresh
                    $lockedCampaign = EmailCampaign::where('id', $campaign->id)
                        ->lockForUpdate()
                        ->first();

                    // Check if campaign can be sent (with fresh data)
                    if (! $lockedCampaign->canBeSent()) {
                        return ['error' => "Campaign \"{$lockedCampaign->name}\" cannot be sent. Status: {$lockedCampaign->status}"];
                    }

                    // Check if template exists
                    if (! $lockedCampaign->template) {
                        return ['error' => "Campaign \"{$lockedCampaign->name}\" has no template assigned."];
                    }

                    // Check if template is active
                    if (! $lockedCampaign->template->is_active) {
                        return ['error' => "Template \"{$lockedCampaign->template->name}\" is not active."];
                    }

                    // Get recipient count
                    $recipientCount = $lockedCampaign->getRecipientCount();

                    if ($recipientCount === 0) {
                        return ['error' => "Campaign \"{$lockedCampaign->name}\" has no recipients."];
                    }

                    // Update campaign status atomically
                    $lockedCampaign->update([
                        'status' => 'sending',
                        'total_recipients' => $recipientCount,
                        'sent_at' => now(),
                    ]);

                    return ['success' => true, 'campaign' => $lockedCampaign];
                });

                if (isset($result['error'])) {
                    return Action::danger($result['error']);
                }

                // Dispatch the job outside the transaction
                SendEmailCampaignJob::dispatch($result['campaign']);

            } catch (\Exception $e) {
                return Action::danger('Failed to send campaign: '.$e->getMessage());
            }
        }

        $count = $models->count();

        return Action::message($count === 1
            ? 'Campaign is being sent!'
            : "{$count} campaigns are being sent!");
    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [];
    }
}
