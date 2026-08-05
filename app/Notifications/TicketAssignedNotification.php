<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $ticket;
    protected $isToCustomer;

    public function __construct(Ticket $ticket, bool $isToCustomer = false)
    {
        $this->ticket = $ticket;
        $this->isToCustomer = $isToCustomer;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = new MailMessage;
        $message->subject('Ticket Assigned: #' . $this->ticket->id . ' - ' . $this->ticket->title);

        if ($this->isToCustomer) {
            $message->greeting('Hello ' . $notifiable->name . ',')
                ->line('Your support ticket has been assigned to an agent.')
                ->line('Assigned Agent: ' . ($this->ticket->assignedTo ? $this->ticket->assignedTo->name : 'Unassigned'))
                ->line('We are working to resolve it within the estimated SLA.');
        } else {
            $message->greeting('Hello ' . $notifiable->name . ',')
                ->line('A support ticket has been assigned to you.')
                ->line('Ticket ID: #' . $this->ticket->id)
                ->line('Title: ' . $this->ticket->title)
                ->line('Priority: ' . $this->ticket->priority->name)
                ->line('Estimated SLA Resolution: ' . ($this->ticket->estimated_resolution_at ? $this->ticket->estimated_resolution_at->format('Y-m-d H:i:s') : 'N/A'));
        }

        return $message->action('View Ticket Details', url('/tickets/' . $this->ticket->id))
            ->line('Thank you for your cooperation.');
    }

    public function toArray(object $notifiable): array
    {
        $messageText = $this->isToCustomer
            ? 'Your ticket #' . $this->ticket->id . ' has been assigned to ' . ($this->ticket->assignedTo ? $this->ticket->assignedTo->name : 'an agent') . '.'
            : 'Ticket #' . $this->ticket->id . ' has been assigned to you.';

        return [
            'ticket_id' => $this->ticket->id,
            'title' => $this->ticket->title,
            'message' => $messageText,
            'action_url' => '/tickets/' . $this->ticket->id,
        ];
    }
}
