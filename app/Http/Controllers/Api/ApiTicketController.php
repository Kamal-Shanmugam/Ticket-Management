<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTicketRequest;
use App\Http\Requests\AssignTicketRequest;
use App\Models\Ticket;
use App\Models\Employee;
use App\Models\TicketStatus;
use App\Models\TicketPriority;
use App\Models\TicketAssignment;
use App\Models\TicketLog;
use App\Models\Attachment;
use App\Http\Resources\TicketResource;
use App\Services\TicketAssignmentService;
use App\Notifications\TicketCreatedNotification;
use App\Notifications\TicketAssignedNotification;
use App\Notifications\TicketStatusChangedNotification;
use App\Notifications\SLAExtendedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ApiTicketController extends Controller
{
    use HandlesApiResponses;

    protected $assignmentService;

    public function __construct(TicketAssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
    }

    /**
     * Display a listing of tickets based on user permissions.
     */
    public function index(Request $request)
    {
        $user = Auth::guard('employee')->user() ?: Auth::guard('customer')->user() ?: Auth::user();

        if (!$user) {
            return $this->errorResponse('Unauthenticated.', [], 401);
        }

        $query = Ticket::with(['customer', 'department', 'priority', 'status', 'assignedTo', 'attachments', 'logs']);

        // Filter based on authentication type
        if ($user instanceof \App\Models\Customer) {
            $query->where('customer_id', $user->id);
        } elseif ($user instanceof \App\Models\Employee) {
            if ($user->isTeamLead()) {
                $query->where('department_id', $user->department_id);
            } elseif ($user->isStaff()) {
                $query->where(function ($q) use ($user) {
                    $q->where('assigned_to', $user->id)
                      ->orWhere('department_id', $user->department_id);
                });
            }
            // Admin can see everything
        }

        // Apply filters
        if ($request->has('status')) {
            $query->whereHas('status', function ($q) use ($request) {
                $q->where('slug', $request->status);
            });
        }

        if ($request->has('priority')) {
            $query->whereHas('priority', function ($q) use ($request) {
                $q->where('slug', $request->priority);
            });
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(15);

        return $this->successResponse(
            TicketResource::collection($tickets)->response()->getData(true),
            'Tickets retrieved successfully.'
        );
    }

    /**
     * Store a newly created ticket.
     */
    public function store(CreateTicketRequest $request)
    {
        $customer = Auth::guard('customer')->user() ?: Auth::user();

        if (!$customer || !$customer instanceof \App\Models\Customer) {
            return $this->errorResponse('Forbidden: Only customers can create tickets.', [], 403);
        }

        $priority = TicketPriority::findOrFail($request->ticket_priority_id);
        $openStatus = TicketStatus::where('slug', 'open')->firstOrFail();

        // Calculate SLA
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

            // Log ticket creation
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

            // Handle file attachments
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

        // Trigger Created Notification (Database and Mail)
        $customer->notify(new TicketCreatedNotification($ticket));

        // Auto assign Employee
        $assignedEmployee = $this->assignmentService->assign($ticket);

        if ($assignedEmployee) {
            // Notify Customer & Assigned Employee
            $assignedEmployee->notify(new TicketAssignedNotification($ticket, false));
            $customer->notify(new TicketAssignedNotification($ticket, true));
        }

        // Refresh model relations
        $ticket->load(['customer', 'department', 'priority', 'status', 'assignedTo', 'attachments', 'logs']);

        return $this->successResponse(
            new TicketResource($ticket),
            'Ticket raised successfully and ' . ($assignedEmployee ? 'auto-assigned to ' . $assignedEmployee->name : 'queued for review') . '.',
            211 // 201 Created
        );
    }

    /**
     * Display a specific ticket.
     */
    public function show($id)
    {
        $ticket = Ticket::with(['customer', 'department', 'priority', 'status', 'assignedTo', 'comments.commenter', 'comments.attachments', 'attachments', 'logs'])
            ->find($id);

        if (!$ticket) {
            return $this->errorResponse('Ticket not found.', [], 404);
        }

        $user = Auth::guard('employee')->user() ?: Auth::guard('customer')->user() ?: Auth::user();
        if (!Gate::forUser($user)->allows('view', $ticket)) {
            return $this->errorResponse('Unauthorized access to this ticket.', [], 403);
        }

        return $this->successResponse(new TicketResource($ticket), 'Ticket details retrieved.');
    }

    /**
     * Manually reassign a ticket (Team Lead / Admin only).
     */
    public function assign(AssignTicketRequest $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $operator = Auth::guard('employee')->user() ?: Auth::guard('customer')->user() ?: Auth::user();
        if (!Gate::forUser($operator)->allows('assign', $ticket)) {
            return $this->errorResponse('Forbidden: You do not have permission to manually assign this ticket.', [], 403);
        }

        $employee = Employee::findOrFail($request->employee_id);
        
        // Employee must belong to the department
        if ($employee->department_id !== $ticket->department_id) {
            return $this->errorResponse('Forbidden: The employee does not belong to the ticket\'s department.', [], 422);
        }

        $operator = Auth::guard('employee')->user() ?: Auth::user();

        DB::transaction(function () use ($ticket, $employee, $operator) {
            // Mark older assignments unassigned
            TicketAssignment::where('ticket_id', $ticket->id)
                ->whereNull('unassigned_at')
                ->update(['unassigned_at' => Carbon::now()]);

            $ticket->assigned_to = $employee->id;

            // Change status to Assigned
            $assignedStatus = TicketStatus::where('slug', 'assigned')->first();
            if ($assignedStatus) {
                $ticket->ticket_status_id = $assignedStatus->id;
            }
            $ticket->save();

            // Create assignment record
            TicketAssignment::create([
                'ticket_id' => $ticket->id,
                'employee_id' => $employee->id,
                'assigned_by' => $operator->id,
                'assigned_at' => Carbon::now(),
            ]);

            // Update last_assigned_at
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

        $ticket->load(['customer', 'department', 'priority', 'status', 'assignedTo', 'logs']);

        return $this->successResponse(new TicketResource($ticket), "Ticket successfully assigned to {$employee->name}.");
    }

    /**
     * Change ticket status.
     */
    public function updateStatus(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $operator = Auth::guard('employee')->user() ?: Auth::guard('customer')->user() ?: Auth::user();
        if (!Gate::forUser($operator)->allows('update', $ticket)) {
            return $this->errorResponse('Forbidden: You do not have permission to update this ticket.', [], 403);
        }

        $request->validate([
            'ticket_status_id' => ['required', 'exists:ticket_statuses,id'],
        ]);

        $newStatus = TicketStatus::findOrFail($request->ticket_status_id);
        $oldStatus = $ticket->status;

        if ($newStatus->id === $ticket->ticket_status_id) {
            return $this->successResponse(new TicketResource($ticket), 'Status is already set to this value.');
        }

        $operator = Auth::guard('employee')->user() ?: Auth::guard('customer')->user() ?: Auth::user();

        DB::transaction(function () use ($ticket, $newStatus, $oldStatus, $operator) {
            $ticket->ticket_status_id = $newStatus->id;

            // Handle resolution timestamps
            if ($newStatus->slug === 'resolved') {
                $ticket->actual_resolution_at = Carbon::now();
            } elseif ($newStatus->slug === 'closed') {
                if (!$ticket->actual_resolution_at) {
                    $ticket->actual_resolution_at = Carbon::now();
                }
            } else {
                // If reopened or moved back, clean actual_resolution_at
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
                    'old_status' => $oldStatus->name,
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

        $ticket->load(['customer', 'department', 'priority', 'status', 'assignedTo', 'logs']);

        return $this->successResponse(new TicketResource($ticket), "Ticket status changed to {$newStatus->name}.");
    }

    /**
     * Extend Ticket SLA resolution time (Admin / TL only).
     */
    public function extendSLA(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $operator = Auth::guard('employee')->user() ?: Auth::guard('customer')->user() ?: Auth::user();
        if (!Gate::forUser($operator)->allows('assign', $ticket)) { // assign permission maps to Admin/TL who can edit settings
            return $this->errorResponse('Forbidden: Only Admin or Team Lead can extend SLA.', [], 403);
        }

        $request->validate([
            'estimated_resolution_at' => ['required', 'date', 'after:now'],
        ]);

        $operator = Auth::guard('employee')->user() ?: Auth::user();
        $oldTime = $ticket->estimated_resolution_at;
        $newTime = Carbon::parse($request->estimated_resolution_at);

        DB::transaction(function () use ($ticket, $newTime, $oldTime, $operator) {
            $ticket->estimated_resolution_at = $newTime;
            $ticket->sla_warning_notified = false; // Reset warnings so they trigger again if appropriate
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

        // Notify customer, agent, and Team Lead
        $notification = new SLAExtendedNotification($ticket, $oldTime, $newTime);
        
        if ($ticket->customer) {
            $ticket->customer->notify($notification);
        }
        
        if ($ticket->assignedTo) {
            $ticket->assignedTo->notify($notification);
        }

        // Notify TL of department (excluding operator if they are the TL)
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

        $ticket->load(['customer', 'department', 'priority', 'status', 'assignedTo', 'logs']);

        return $this->successResponse(new TicketResource($ticket), 'Ticket SLA extension saved and notifications sent.');
    }
}
