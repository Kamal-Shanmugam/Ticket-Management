<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketClosedNotification extends Notification implements ShouldQueue
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
            ->subject('Ticket Closed: #' . $this->ticket->id)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your support ticket has been closed.')
            ->line('Ticket ID: #' . $this->ticket->id)
            ->line('Title: ' . $this->ticket->title)
            ->line('If you feel the issue is not fully resolved, you may reopen the ticket by replying via the customer portal.')
            ->action('View Ticket Details', url('/tickets/' . $this->ticket->id))
            ->line('Thank you!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'title' => $this->ticket->title,
            'message' => 'Ticket #' . $this->ticket->id . ' has been closed.',
            'action_url' => '/tickets/' . $this->ticket->id,
        ];
    }
}
