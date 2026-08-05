<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SLABreachWarningNotification extends Notification implements ShouldQueue
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
            ->subject('SLA Warning: Ticket #' . $this->ticket->id . ' is approaching its due time')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('The ticket is approaching its SLA estimated resolution limit.')
            ->line('Ticket ID: #' . $this->ticket->id)
            ->line('Title: ' . $this->ticket->title)
            ->line('SLA Due Time: ' . ($this->ticket->estimated_resolution_at ? $this->ticket->estimated_resolution_at->format('Y-m-d H:i:s') : 'N/A'))
            ->action('View Ticket Details', url('/tickets/' . $this->ticket->id))
            ->line('Please review and resolve as soon as possible.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'title' => $this->ticket->title,
            'message' => 'SLA Warning: Ticket #' . $this->ticket->id . ' is approaching due date.',
            'action_url' => '/tickets/' . $this->ticket->id,
        ];
    }
}
