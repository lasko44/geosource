<?php

namespace App\Notifications;

use App\Models\TeamInvitation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamInvitationNotification extends Notification
{

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public TeamInvitation $invitation
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $acceptUrl = route('teams.invitations.show', $this->invitation->token);

        return (new MailMessage)
            ->subject("You're invited to join {$this->invitation->team->name}")
            ->greeting('Hello!')
            ->line("{$this->invitation->inviter->name} has invited you to join **{$this->invitation->team->name}** as a **{$this->invitation->role}**.")
            ->action('Accept Invitation', $acceptUrl)
            ->line('This invitation will expire in 7 days.')
            ->line('If you did not expect this invitation, you can ignore this email.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'team_id' => $this->invitation->team_id,
            'team_name' => $this->invitation->team->name,
            'inviter_name' => $this->invitation->inviter->name,
            'role' => $this->invitation->role,
        ];
    }
}
