<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\TicketComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommentAddedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $ticket;
    protected $comment;
    protected $commenterName;

    public function __construct(Ticket $ticket, TicketComment $comment, string $commenterName)
    {
        $this->ticket = $ticket;
        $this->comment = $comment;
        $this->commenterName = $commenterName;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Comment on Ticket #' . $this->ticket->id)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($this->commenterName . ' has added a new reply to the ticket.')
            ->line('"' . substr($this->comment->comment, 0, 100) . (strlen($this->comment->comment) > 100 ? '...' : '') . '"')
            ->action('View Ticket Details', url('/tickets/' . $this->ticket->id))
            ->line('Thank you!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'title' => $this->ticket->title,
            'message' => 'New reply from ' . $this->commenterName . ' on ticket #' . $this->ticket->id . '.',
            'action_url' => '/tickets/' . $this->ticket->id,
        ];
    }
}
