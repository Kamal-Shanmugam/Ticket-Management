<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $ticket;
    protected $oldStatusName;
    protected $newStatusName;

    public function __construct(Ticket $ticket, string $oldStatusName, string $newStatusName)
    {
        $this->ticket = $ticket;
        $this->oldStatusName = $oldStatusName;
        $this->newStatusName = $newStatusName;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Ticket Status Updated: #' . $this->ticket->id)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('The status of the ticket has been updated.')
            ->line('Ticket ID: #' . $this->ticket->id)
            ->line('Title: ' . $this->ticket->title)
            ->line('Old Status: ' . $this->oldStatusName)
            ->line('New Status: ' . $this->newStatusName)
            ->action('View Ticket Details', url('/tickets/' . $this->ticket->id))
            ->line('Thank you!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'title' => $this->ticket->title,
            'message' => 'Ticket #' . $this->ticket->id . ' status changed from "' . $this->oldStatusName . '" to "' . $this->newStatusName . '".',
            'action_url' => '/tickets/' . $this->ticket->id,
        ];
    }
}
