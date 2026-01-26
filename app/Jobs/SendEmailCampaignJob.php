<?php

namespace App\Jobs;

use App\Mail\MarketingEmail;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignSend;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendEmailCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 3600; // 1 hour for large campaigns

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
        $campaign = $this->campaign;
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
            // Check if already sent to this user (for resumability)
            $existingSend = EmailCampaignSend::where('email_campaign_id', $campaign->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existingSend && $existingSend->status !== 'pending') {
                continue;
            }

            // Create send record if doesn't exist
            $send = $existingSend ?? EmailCampaignSend::create([
                'email_campaign_id' => $campaign->id,
                'user_id' => $user->id,
                'status' => 'pending',
            ]);

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
                Log::error("Failed to send campaign email to {$user->email}: " . $e->getMessage());

                // Update send record with error
                $send->update([
                    'status' => 'failed',
                    'error_message' => substr($e->getMessage(), 0, 255),
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
        Log::error("Campaign job {$this->campaign->id} failed: " . $exception->getMessage());

        // Don't mark as cancelled, keep as sending so it can be retried
    }
}
