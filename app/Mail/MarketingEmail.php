<?php

namespace App\Mail;

use App\Models\EmailCampaign;
use App\Models\EmailCampaignSend;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class MarketingEmail extends Mailable
{
    use Queueable, SerializesModels;

    public string $renderedContent;
    public string $unsubscribeUrl;
    public string $trackingPixelUrl;

    public function __construct(
        public EmailTemplate $template,
        public User $user,
        public EmailCampaign $campaign,
        public EmailCampaignSend $send,
    ) {
        $this->prepareContent();
    }

    public function envelope(): Envelope
    {
        // Render subject with variables
        $subject = $this->renderVariables($this->template->subject);

        return new Envelope(
            subject: $subject,
            tags: ['marketing', 'campaign-'.$this->campaign->id],
            metadata: [
                'campaign_id' => $this->campaign->id,
                'send_id' => $this->send->id,
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.marketing',
            with: [
                'content' => $this->renderedContent,
                'previewText' => $this->template->preview_text,
                'unsubscribeUrl' => $this->unsubscribeUrl,
                'trackingPixelUrl' => $this->trackingPixelUrl,
                'user' => $this->user,
                'campaign' => $this->campaign,
            ],
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    private function prepareContent(): void
    {
        // Generate signed unsubscribe URL
        $this->unsubscribeUrl = URL::signedRoute('marketing.unsubscribe', [
            'email' => $this->user->email,
            'campaign' => $this->campaign->id,
        ]);

        // Generate tracking pixel URL for open tracking
        $this->trackingPixelUrl = URL::signedRoute('marketing.track-open', [
            'send' => $this->send->id,
        ]);

        // Prepare variables for template rendering
        $variables = [
            'user_name' => $this->user->name,
            'user_email' => $this->user->email,
            'user_first_name' => explode(' ', $this->user->name)[0] ?? $this->user->name,
            'unsubscribe_url' => $this->unsubscribeUrl,
        ];

        $this->renderedContent = $this->template->render($variables);
    }

    private function renderVariables(string $text): string
    {
        $variables = [
            'user_name' => $this->user->name,
            'user_first_name' => explode(' ', $this->user->name)[0] ?? $this->user->name,
            'app_name' => config('app.name'),
        ];

        foreach ($variables as $key => $value) {
            $text = str_replace('{{' . $key . '}}', $value, $text);
            $text = str_replace('{{ ' . $key . ' }}', $value, $text);
        }

        return $text;
    }
}
