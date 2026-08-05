<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddCommentRequest;
use App\Http\Requests\AssignTicketRequest;
use App\Models\Ticket;
use App\Models\Employee;
use App\Models\TicketStatus;
use App\Models\TicketPriority;
use App\Models\TicketAssignment;
use App\Models\TicketComment;
use App\Models\TicketLog;
use App\Models\Attachment;
use App\Models\Role;
use App\Services\TicketAssignmentService;
use App\Notifications\TicketCreatedNotification;
use App\Notifications\TicketAssignedNotification;
use App\Notifications\TicketStatusChangedNotification;
use App\Notifications\CommentAddedNotification;
use App\Notifications\SLAExtendedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;

class WebTicketController extends Controller
{
    protected $assignmentService;

    public function __construct(TicketAssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
    }

    /**
     * Show the ticket detail and conversation thread.
     */
    public function show($id)
    {
        $ticket = Ticket::with(['customer', 'department', 'priority', 'status', 'assignedTo', 'comments.commenter', 'comments.attachments', 'attachments', 'logs'])
            ->findOrFail($id);

        $user = Auth::guard('employee')->user() ?: Auth::guard('customer')->user();
        if (!Gate::forUser($user)->allows('view', $ticket)) {
            abort(403, 'Unauthorized access to this ticket.');
        }

        // Fetch support status and priority options
        $statuses = TicketStatus::all();
        $priorities = TicketPriority::all();

        // Fetch eligible agents in the same department for manual assignment dropdown
        $eligibleEmployees = Employee::where('department_id', $ticket->department_id)->get();

        return view('dashboard.ticket_show', compact('ticket', 'statuses', 'priorities', 'eligibleEmployees'));
    }

    /**
     * Store customer support ticket from Web dashboard.
     */
    public function store(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            return redirect()->route('login');
        }

        // Manually trigger request validation rules to avoid duplicate class resolution conflicts
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'department_id' => ['required', 'exists:departments,id'],
            'ticket_priority_id' => ['required', 'exists:ticket_priorities,id'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,png,jpg,jpeg,zip,txt,doc,docx'],
        ];

        // Duplicate ticket prevention
        $duplicateExists = Ticket::where('customer_id', $customer->id)
            ->where('title', $request->title)
            ->where('description', $request->description)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->exists();

        if ($duplicateExists) {
            return back()->withErrors(['title' => 'Duplicate ticket detected! You raised a ticket with the exact same title and description in the past 5 minutes.'])->withInput();
        }

        $validated = $request->validate($rules);

        $priority = TicketPriority::findOrFail($request->ticket_priority_id);
        $openStatus = TicketStatus::where('slug', 'open')->firstOrFail();
        $estimatedResolution = Carbon::now()->addHours($priority->resolution_hours);

        $ticket = DB::transaction(function () use ($request, $customer, $openStatus, $estimatedResolution) {
            $ticket = Ticket::create([
                'customer_id' => $customer->id,
                'department_id' => $request->department_id,
                'ticket_priority_id' => $request->ticket_priority_id,
                'ticket_status_id' => $openStatus->id,
                'title' => $request->title,
                'description' => $request->description,
                'estimated_resolution_at' => $estimatedResolution,
            ]);

            // Log
            TicketLog::create([
                'ticket_id' => $ticket->id,
                'performed_by_type' => get_class($customer),
                'performed_by_id' => $customer->id,
                'action' => 'created',
                'details' => [
                    'title' => $ticket->title,
                    'priority' => $ticket->priority->name,
                    'department' => $ticket->department->name,
                    'estimated_resolution_at' => $ticket->estimated_resolution_at->format('Y-m-d H:i:s'),
                ],
            ]);

            // Handle uploads
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('attachments', 'public');
                    Attachment::create([
                        'ticket_id' => $ticket->id,
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            return $ticket;
        });

        // Notify
        $customer->notify(new TicketCreatedNotification($ticket));

        // Auto assign employee
        $assignedEmployee = $this->assignmentService->assign($ticket);

        if ($assignedEmployee) {
            $assignedEmployee->notify(new TicketAssignedNotification($ticket, false));
            $customer->notify(new TicketAssignedNotification($ticket, true));
        }

        return redirect()->route('customer.dashboard')->with('success', 'Support ticket #' . $ticket->id . ' has been created ' . ($assignedEmployee ? 'and auto-assigned.' : 'and placed in queue.'));
    }

    /**
     * Post a reply comment.
     */
    public function storeComment(AddCommentRequest $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $commenter = Auth::guard('employee')->user() ?: Auth::guard('customer')->user();
        if (!$commenter) {
            return redirect()->route('login');
        }

        if (!Gate::forUser($commenter)->allows('comment', $ticket)) {
            abort(403, 'You do not have permission to comment on this ticket.');
        }

        DB::transaction(function () use ($request, $ticket, $commenter) {
            $comment = TicketComment::create([
                'ticket_id' => $ticket->id,
                'commenter_type' => get_class($commenter),
                'commenter_id' => $commenter->id,
                'comment' => $request->comment,
            ]);

            // Handle uploads
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('attachments', 'public');
                    Attachment::create([
                        'ticket_id' => $ticket->id,
                        'ticket_comment_id' => $comment->id,
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            // Log
            TicketLog::create([
                'ticket_id' => $ticket->id,
                'performed_by_type' => get_class($commenter),
                'performed_by_id' => $commenter->id,
                'action' => 'comment_added',
                'details' => [
                    'comment_id' => $comment->id,
                    'excerpt' => substr($comment->comment, 0, 50) . (strlen($comment->comment) > 50 ? '...' : ''),
                ],
            ]);

            // Reopen if Customer replies on Resolved/Closed
            if ($commenter instanceof \App\Models\Customer && ($ticket->isResolved() || $ticket->isClosed())) {
                $oldStatus = $ticket->status;
                $reopenedStatus = TicketStatus::where('slug', 'reopened')->first();
                if ($reopenedStatus) {
                    $ticket->ticket_status_id = $reopenedStatus->id;
                    $ticket->actual_resolution_at = null;
                    $ticket->save();

                    TicketLog::create([
                        'ticket_id' => $ticket->id,
                        'performed_by_type' => get_class($commenter),
                        'performed_by_id' => $commenter->id,
                        'action' => 'status_changed',
                        'details' => [
                            'old_status' => $oldStatus ? $oldStatus->name : 'Resolved/Closed',
                            'new_status' => $reopenedStatus->name,
                            'reason' => 'Customer reply on thread',
                        ],
                    ]);

                    if ($ticket->assignedTo) {
                        $ticket->assignedTo->notify(new TicketStatusChangedNotification($ticket, $oldStatus ? $oldStatus->name : 'Resolved/Closed', $reopenedStatus->name));
                    }
                }
            }

            // Dispatch notification
            $commenterName = $commenter->name;
            if ($commenter instanceof \App\Models\Customer) {
                if ($ticket->assignedTo) {
                    $ticket->assignedTo->notify(new CommentAddedNotification($ticket, $comment, $commenterName));
                }
            } else {
                if ($ticket->customer) {
                    $ticket->customer->notify(new CommentAddedNotification($ticket, $comment, $commenterName));
                }
            }
        });

        return back()->with('success', 'Reply posted successfully.');
    }

    /**
     * Update status from dropdown.
     */
    public function updateStatus(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $user = Auth::guard('employee')->user() ?: Auth::guard('customer')->user();
        if (!Gate::forUser($user)->allows('update', $ticket)) {
            abort(403);
        }

        $request->validate([
            'ticket_status_id' => ['required', 'exists:ticket_statuses,id'],
        ]);

        $newStatus = TicketStatus::findOrFail($request->ticket_status_id);
        $oldStatus = $ticket->status;

        if ($newStatus->id === $ticket->ticket_status_id) {
            return back();
        }

        $operator = Auth::guard('employee')->user() ?: Auth::guard('customer')->user();

        DB::transaction(function () use ($ticket, $newStatus, $oldStatus, $operator) {
            $ticket->ticket_status_id = $newStatus->id;

            if ($newStatus->slug === 'resolved') {
                $ticket->actual_resolution_at = Carbon::now();
            } elseif ($newStatus->slug === 'closed') {
                if (!$ticket->actual_resolution_at) {
                    $ticket->actual_resolution_at = Carbon::now();
                }
            } else {
                $ticket->actual_resolution_at = null;
            }

            $ticket->save();

            // Log
            TicketLog::create([
                'ticket_id' => $ticket->id,
                'performed_by_type' => get_class($operator),
                'performed_by_id' => $operator->id,
                'action' => 'status_changed',
                'details' => [
                    'old_status' => $oldStatus ? $oldStatus->name : 'Unknown',
                    'new_status' => $newStatus->name,
                ],
            ]);
        });

        // Notify
        $oldName = $oldStatus ? $oldStatus->name : 'Unknown';
        if ($ticket->customer) {
            $ticket->customer->notify(new TicketStatusChangedNotification($ticket, $oldName, $newStatus->name));
        }
        if ($ticket->assignedTo && $ticket->assignedTo->id !== $operator->id) {
            $ticket->assignedTo->notify(new TicketStatusChangedNotification($ticket, $oldName, $newStatus->name));
        }

        return back()->with('success', "Ticket status changed to {$newStatus->name}.");
    }

    /**
     * Manually reassign a ticket (Team Lead / Admin only).
     */
    public function assign(AssignTicketRequest $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $operator = Auth::guard('employee')->user() ?: Auth::guard('customer')->user();
        if (!$operator || !Gate::forUser($operator)->allows('assign', $ticket)) {
            abort(403, 'Unauthorized.');
        }

        $employee = Employee::findOrFail($request->employee_id);
        if ($employee->department_id !== $ticket->department_id) {
            return back()->with('error', 'The agent does not belong to this department.');
        }

        $operator = Auth::guard('employee')->user();

        DB::transaction(function () use ($ticket, $employee, $operator) {
            TicketAssignment::where('ticket_id', $ticket->id)
                ->whereNull('unassigned_at')
                ->update(['unassigned_at' => Carbon::now()]);

            $ticket->assigned_to = $employee->id;
            
            $assignedStatus = TicketStatus::where('slug', 'assigned')->first();
            if ($assignedStatus) {
                $ticket->ticket_status_id = $assignedStatus->id;
            }
            $ticket->save();

            TicketAssignment::create([
                'ticket_id' => $ticket->id,
                'employee_id' => $employee->id,
                'assigned_by' => $operator->id,
                'assigned_at' => Carbon::now(),
            ]);

            $employee->last_assigned_at = Carbon::now();
            $employee->save();

            // Log
            TicketLog::create([
                'ticket_id' => $ticket->id,
                'performed_by_type' => get_class($operator),
                'performed_by_id' => $operator->id,
                'action' => 'reassigned',
                'details' => [
                    'assigned_to_id' => $employee->id,
                    'assigned_to_name' => $employee->name,
                    'method' => 'manual',
                ],
            ]);
        });

        // Notify
        $employee->notify(new TicketAssignedNotification($ticket, false));
        if ($ticket->customer) {
            $ticket->customer->notify(new TicketAssignedNotification($ticket, true));
        }

        return back()->with('success', "Ticket successfully assigned to {$employee->name}.");
    }

    /**
     * Extend SLA estimated resolution time.
     */
    public function extendSLA(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $operator = Auth::guard('employee')->user() ?: Auth::guard('customer')->user();
        if (!$operator || !Gate::forUser($operator)->allows('assign', $ticket)) {
            abort(403);
        }

        $request->validate([
            'estimated_resolution_at' => ['required', 'date', 'after:now'],
        ]);

        $operator = Auth::guard('employee')->user();
        $oldTime = $ticket->estimated_resolution_at;
        $newTime = Carbon::parse($request->estimated_resolution_at);

        DB::transaction(function () use ($ticket, $newTime, $oldTime, $operator) {
            $ticket->estimated_resolution_at = $newTime;
            $ticket->sla_warning_notified = false;
            $ticket->sla_breached_notified = false;
            $ticket->save();

            // Log
            TicketLog::create([
                'ticket_id' => $ticket->id,
                'performed_by_type' => get_class($operator),
                'performed_by_id' => $operator->id,
                'action' => 'sla_updated',
                'details' => [
                    'old_sla' => $oldTime ? $oldTime->format('Y-m-d H:i:s') : null,
                    'new_sla' => $newTime->format('Y-m-d H:i:s'),
                ],
            ]);
        });

        // Notify
        $notification = new SLAExtendedNotification($ticket, $oldTime, $newTime);
        if ($ticket->customer) {
            $ticket->customer->notify($notification);
        }
        if ($ticket->assignedTo) {
            $ticket->assignedTo->notify($notification);
        }

        // Notify TLs
        $tlRole = Role::where('slug', 'team_lead')->first();
        if ($tlRole && $ticket->department_id) {
            $leads = Employee::where('role_id', $tlRole->id)
                ->where('department_id', $ticket->department_id)
                ->where('id', '!=', $operator->id)
                ->get();
            foreach ($leads as $lead) {
                $lead->notify($notification);
            }
        }

        return back()->with('success', 'Ticket SLA resolution target extended.');
    }

    /**
     * Mark a user notification as read.
     */
    public function readNotification(Request $request, $id)
    {
        $user = Auth::guard('employee')->user() ?: Auth::guard('customer')->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $notification = $user->unreadNotifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }

        return back()->with('success', 'Notification marked as read.');
    }
}
