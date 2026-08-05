<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddCommentRequest;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketLog;
use App\Models\TicketStatus;
use App\Models\Attachment;
use App\Http\Resources\CommentResource;
use App\Notifications\CommentAddedNotification;
use App\Notifications\TicketStatusChangedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ApiCommentController extends Controller
{
    use HandlesApiResponses;

    /**
     * Retrieve replies for a ticket.
     */
    public function index($ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);

        $user = Auth::guard('employee')->user() ?: Auth::guard('customer')->user() ?: Auth::user();
        if (!Gate::forUser($user)->allows('view', $ticket)) {
            return $this->errorResponse('Forbidden: You do not have permission to view comments on this ticket.', [], 403);
        }

        $comments = $ticket->comments()->with(['commenter', 'attachments'])->orderBy('created_at', 'asc')->get();

        return $this->successResponse(CommentResource::collection($comments), 'Comments retrieved.');
    }

    /**
     * Post a reply comment.
     */
    public function store(AddCommentRequest $request, $ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);

        // Resolve who is commenting
        $commenter = Auth::guard('employee')->user() ?: Auth::guard('customer')->user() ?: Auth::user();

        if (!$commenter) {
            return $this->errorResponse('Unauthenticated.', [], 401);
        }

        if (!Gate::forUser($commenter)->allows('comment', $ticket)) {
            return $this->errorResponse('Forbidden: You cannot post replies to this ticket.', [], 403);
        }

        $comment = DB::transaction(function () use ($request, $ticket, $commenter) {
            // Create comment
            $newComment = TicketComment::create([
                'ticket_id' => $ticket->id,
                'commenter_type' => get_class($commenter),
                'commenter_id' => $commenter->id,
                'comment' => $request->comment,
            ]);

            // Handle file uploads
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('attachments', 'public');
                    Attachment::create([
                        'ticket_id' => $ticket->id,
                        'ticket_comment_id' => $newComment->id,
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            // Audit Log
            TicketLog::create([
                'ticket_id' => $ticket->id,
                'performed_by_type' => get_class($commenter),
                'performed_by_id' => $commenter->id,
                'action' => 'comment_added',
                'details' => [
                    'comment_id' => $newComment->id,
                    'excerpt' => substr($newComment->comment, 0, 50) . (strlen($newComment->comment) > 50 ? '...' : ''),
                ],
            ]);

            // Automatic Reopen / Status Toggle
            // If the commenter is a Customer and the ticket is Resolved or Closed:
            if ($commenter instanceof \App\Models\Customer && ($ticket->isResolved() || $ticket->isClosed())) {
                $oldStatus = $ticket->status;
                $reopenedStatus = TicketStatus::where('slug', 'reopened')->first();
                if ($reopenedStatus) {
                    $ticket->ticket_status_id = $reopenedStatus->id;
                    $ticket->actual_resolution_at = null; // Reset resolution timestamp
                    $ticket->save();

                    // Log status change
                    TicketLog::create([
                        'ticket_id' => $ticket->id,
                        'performed_by_type' => get_class($commenter),
                        'performed_by_id' => $commenter->id,
                        'action' => 'status_changed',
                        'details' => [
                            'old_status' => $oldStatus ? $oldStatus->name : 'Resolved/Closed',
                            'new_status' => $reopenedStatus->name,
                            'reason' => 'Customer reply on resolved/closed ticket',
                        ],
                    ]);

                    // Notify assigned agent about reopen
                    if ($ticket->assignedTo) {
                        $ticket->assignedTo->notify(new TicketStatusChangedNotification(
                            $ticket,
                            $oldStatus ? $oldStatus->name : 'Resolved/Closed',
                            $reopenedStatus->name
                        ));
                    }
                }
            }

            return $newComment;
        });

        // Notify other party
        $commenterName = $commenter->name;
        if ($commenter instanceof \App\Models\Customer) {
            // Customer commented -> Notify Assigned Employee
            if ($ticket->assignedTo) {
                $ticket->assignedTo->notify(new CommentAddedNotification($ticket, $comment, $commenterName));
            }
        } elseif ($commenter instanceof \App\Models\Employee) {
            // Employee commented -> Notify Customer
            if ($ticket->customer) {
                $ticket->customer->notify(new CommentAddedNotification($ticket, $comment, $commenterName));
            }
        }

        $comment->load(['commenter', 'attachments']);

        return $this->successResponse(new CommentResource($comment), 'Reply posted successfully.', 211); // 201 Created
    }
}
