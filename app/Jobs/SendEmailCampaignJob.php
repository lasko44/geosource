<?php

namespace App\Jobs;

use App\Mail\MarketingEmail;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignSend;
use App\Support\ErrorSanitizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sends marketing emails to all recipients in a campaign.
 */
class SendEmailCampaignJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 3600; // 1 hour for large campaigns

    /**
     * The unique ID of the job (prevents duplicate jobs).
     */
    public function uniqueId(): string
    {
        return 'email-campaign-'.$this->campaign->id;
    }

    /**
     * The number of seconds after which the unique lock will be released.
     */
    public int $uniqueFor = 7200; // 2 hours

    /**
     * Create a new job instance.
     */
    public function __construct(
        public EmailCampaign $campaign
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $campaign = $this->campaign->fresh();

        // Double-check campaign is in sending status (not already completed)
        if ($campaign->status === 'sent') {
            Log::info("Campaign {$campaign->id} already sent, skipping");

            return;
        }

        if ($campaign->status !== 'sending') {
            Log::warning("Campaign {$campaign->id} has unexpected status: {$campaign->status}");

            return;
        }

        $template = $campaign->template;

        if (! $template) {
            Log::error("Campaign {$campaign->id} has no template");
            $campaign->update(['status' => 'cancelled']);

            return;
        }

        // Get all recipients
        $recipients = $campaign->getAudienceQuery()
            ->select(['id', 'name', 'email'])
            ->cursor();

        foreach ($recipients as $user) {
            // Use firstOrCreate to atomically check/create send record
            $send = EmailCampaignSend::firstOrCreate(
                [
                    'email_campaign_id' => $campaign->id,
                    'user_id' => $user->id,
                ],
                [
                    'status' => 'pending',
                ]
            );

            // Skip if already processed
            if ($send->status !== 'pending') {
                continue;
            }

            try {
                // Send the email
                Mail::to($user->email)
                    ->send(new MarketingEmail($template, $user, $campaign, $send));

                // Update send record
                $send->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);

                // Increment sent count
                $campaign->increment('sent_count');

            } catch (\Exception $e) {
                Log::error("Failed to send campaign email to {$user->email}: ".$e->getMessage());

                // Update send record with error
                $send->update([
                    'status' => 'failed',
                    'error_message' => substr(ErrorSanitizer::sanitize($e), 0, 255),
                ]);

                // Increment failed count
                $campaign->increment('failed_count');
            }

            // Small delay to avoid rate limiting
            usleep(100000); // 100ms
        }

        // Update campaign status to sent
        $campaign->update(['status' => 'sent']);

        Log::info("Campaign {$campaign->id} completed. Sent: {$campaign->sent_count}, Failed: {$campaign->failed_count}");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Campaign job {$this->campaign->id} failed: ".$exception->getMessage());

        // Mark as failed so admin can investigate
        $this->campaign->update(['status' => 'cancelled']);
    }
}
