<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $ticket;

    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Ticket Created: ' . $this->ticket->title)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A new support ticket has been created successfully.')
            ->line('Ticket ID: #' . $this->ticket->id)
            ->line('Title: ' . $this->ticket->title)
            ->line('Priority: ' . $this->ticket->priority->name)
            ->line('Estimated Resolution: ' . ($this->ticket->estimated_resolution_at ? $this->ticket->estimated_resolution_at->format('Y-m-d H:i:s') : 'N/A'))
            ->action('View Ticket Details', url('/tickets/' . $this->ticket->id))
            ->line('Thank you for using our support system!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'title' => $this->ticket->title,
            'message' => 'Support ticket #' . $this->ticket->id . ' has been created.',
            'action_url' => '/tickets/' . $this->ticket->id,
        ];
    }
}
