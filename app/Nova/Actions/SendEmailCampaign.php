<?php

namespace App\Nova\Actions;

use App\Jobs\SendEmailCampaignJob;
use App\Models\EmailCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Http\Requests\NovaRequest;

class SendEmailCampaign extends Action
{
    use InteractsWithQueue, Queueable;

    /**
     * The displayable name of the action.
     *
     * @var string
     */
    public $name = 'Send Campaign';

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        foreach ($models as $campaign) {
            /** @var EmailCampaign $campaign */

            // Check if campaign can be sent
            if (! $campaign->canBeSent()) {
                return Action::danger("Campaign \"{$campaign->name}\" cannot be sent. Status: {$campaign->status}");
            }

            // Check if template exists
            if (! $campaign->template) {
                return Action::danger("Campaign \"{$campaign->name}\" has no template assigned.");
            }

            // Check if template is active
            if (! $campaign->template->is_active) {
                return Action::danger("Template \"{$campaign->template->name}\" is not active.");
            }

            // Get recipient count
            $recipientCount = $campaign->getRecipientCount();

            if ($recipientCount === 0) {
                return Action::danger("Campaign \"{$campaign->name}\" has no recipients.");
            }

            // Update campaign status
            $campaign->update([
                'status' => 'sending',
                'total_recipients' => $recipientCount,
                'sent_at' => now(),
            ]);

            // Dispatch the job to send emails
            SendEmailCampaignJob::dispatch($campaign);
        }

        $count = $models->count();

        return Action::message($count === 1
            ? 'Campaign is being sent!'
            : "{$count} campaigns are being sent!");
    }

    /**
     * Get the fields available on the action.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [];
    }
}
