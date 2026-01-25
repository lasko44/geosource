<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminNewSubscriptionNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $planName,
        public string $planPrice
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Subscription: '.$this->user->name.' subscribed to '.$this->planName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.new-subscription',
            with: [
                'user' => $this->user,
                'planName' => $this->planName,
                'planPrice' => $this->planPrice,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
