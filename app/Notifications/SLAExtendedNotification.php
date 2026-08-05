<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SLAExtendedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $ticket;
    protected $oldTime;
    protected $newTime;

    public function __construct(Ticket $ticket, $oldTime, $newTime)
    {
        $this->ticket = $ticket;
        $this->oldTime = $oldTime;
        $this->newTime = $newTime;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $formattedOld = $this->oldTime ? $this->oldTime->format('Y-m-d H:i:s') : 'N/A';
        $formattedNew = $this->newTime ? $this->newTime->format('Y-m-d H:i:s') : 'N/A';

        return (new MailMessage)
            ->subject('Ticket SLA Extended: #' . $this->ticket->id)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('The SLA estimated resolution time for ticket #' . $this->ticket->id . ' has been extended.')
            ->line('Old Target: ' . $formattedOld)
            ->line('New Target: ' . $formattedNew)
            ->action('View Ticket Details', url('/tickets/' . $this->ticket->id))
            ->line('Thank you!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'title' => $this->ticket->title,
            'message' => 'SLA extended for ticket #' . $this->ticket->id . ' to ' . ($this->newTime ? $this->newTime->format('Y-m-d H:i:s') : 'N/A') . '.',
            'action_url' => '/tickets/' . $this->ticket->id,
        ];
    }
}
