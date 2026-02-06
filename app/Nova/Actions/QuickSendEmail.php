<?php

namespace App\Nova\Actions;

use App\Jobs\SendEmailCampaignJob;
use App\Models\EmailCampaign;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class QuickSendEmail extends Action
{
    /**
     * The displayable name of the action.
     */
    public $name = 'Send Now';

    /**
     * Perform the action on the given models.
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        foreach ($models as $template) {
            /** @var EmailTemplate $template */

            // Check if template is active
            if (! $template->is_active) {
                return Action::danger("Template \"{$template->name}\" is not active.");
            }

            // Handle test send to specific email
            if ($fields->audience === 'test') {
                if (empty($fields->test_email)) {
                    return Action::danger('Please enter an email address for test send.');
                }

                if (! filter_var($fields->test_email, FILTER_VALIDATE_EMAIL)) {
                    return Action::danger('Please enter a valid email address.');
                }

                return $this->sendTestEmail($template, $fields->test_email);
            }

            // Get recipient count for selected audience
            $recipientCount = $this->getRecipientCount($fields->audience);

            if ($recipientCount === 0) {
                return Action::danger("No recipients found for audience: {$fields->audience}");
            }

            try {
                $campaign = DB::transaction(function () use ($template, $fields, $recipientCount) {
                    // Create a campaign for tracking (auto-named)
                    $campaign = EmailCampaign::create([
                        'name' => "Quick Send: {$template->name} - ".now()->format('M j, Y g:ia'),
                        'email_template_id' => $template->id,
                        'status' => 'sending',
                        'audience' => $fields->audience,
                        'total_recipients' => $recipientCount,
                        'sent_at' => now(),
                        'created_by' => auth()->id(),
                    ]);

                    return $campaign;
                });

                // Dispatch the send job
                SendEmailCampaignJob::dispatch($campaign);

                return Action::message("Sending to {$recipientCount} recipients...");

            } catch (\Exception $e) {
                return Action::danger('Failed to send: '.$e->getMessage());
            }
        }
    }

    /**
     * Send a test email to a specific address.
     */
    protected function sendTestEmail(EmailTemplate $template, string $email)
    {
        // Find or create a fake user context for the test
        $user = User::where('email', $email)->first();

        if (! $user) {
            // Use current admin as the user context
            $user = auth()->user();
        }

        try {
            \Illuminate\Support\Facades\Mail::to($email)
                ->send(new \App\Mail\MarketingEmail($template, $user));

            return Action::message("Test email sent to {$email}");

        } catch (\Exception $e) {
            return Action::danger('Failed to send test: '.$e->getMessage());
        }
    }

    /**
     * Get the fields available on the action.
     */
    public function fields(NovaRequest $request): array
    {
        return [
            Select::make('Audience')
                ->options([
                    'test' => 'Test - Send to specific email',
                    'all' => 'All Users ('.$this->getRecipientCount('all').')',
                    'free' => 'Free Users ('.$this->getRecipientCount('free').')',
                    'pro' => 'Pro Users ('.$this->getRecipientCount('pro').')',
                    'agency' => 'Agency Users ('.$this->getRecipientCount('agency').')',
                ])
                ->rules('required')
                ->help('Select audience or send a test email first.'),

            Text::make('Test Email')
                ->help('Enter email address for test send (only used when "Test" audience is selected).')
                ->placeholder('email@example.com'),
        ];
    }

    /**
     * Get the recipient count for an audience.
     */
    protected function getRecipientCount(string $audience): int
    {
        $query = User::query()
            ->whereNotNull('email_verified_at')
            ->whereDoesntHave('marketingUnsubscribe');

        switch ($audience) {
            case 'free':
                $query->whereDoesntHave('subscriptions');
                break;
            case 'pro':
                $query->whereHas('subscriptions', function ($q) {
                    $q->where('stripe_price', 'like', '%pro%');
                });
                break;
            case 'agency':
                $query->whereHas('subscriptions', function ($q) {
                    $q->where('stripe_price', 'like', '%agency%');
                });
                break;
        }

        return $query->count();
    }
}
