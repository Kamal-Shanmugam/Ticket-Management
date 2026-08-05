<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SLABreachedNotification extends Notification implements ShouldQueue
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
            ->subject('SLA BREACH ALERT: Ticket #' . $this->ticket->id)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('ALERT: The ticket has breached its SLA estimated resolution limit.')
            ->line('Ticket ID: #' . $this->ticket->id)
            ->line('Title: ' . $this->ticket->title)
            ->line('Priority: ' . $this->ticket->priority->name)
            ->line('Assigned Agent: ' . ($this->ticket->assignedTo ? $this->ticket->assignedTo->name : 'Unassigned'))
            ->line('SLA Due Time: ' . ($this->ticket->estimated_resolution_at ? $this->ticket->estimated_resolution_at->format('Y-m-d H:i:s') : 'N/A'))
            ->action('View Ticket Details', url('/tickets/' . $this->ticket->id))
            ->line('Immediate action is required to resolve this ticket.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'title' => $this->ticket->title,
            'message' => 'SLA BREACH: Ticket #' . $this->ticket->id . ' has exceeded its resolution limit.',
            'action_url' => '/tickets/' . $this->ticket->id,
        ];
    }
}
