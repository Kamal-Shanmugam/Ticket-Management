<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\Ticket;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EmployeeDashboardController extends Controller
{
    public function index(Request $request)
    {
        $employee = Auth::guard('employee')->user();

        if (!$employee) {
            return redirect()->route('login');
        }

        $activeStatuses = ['open', 'assigned', 'in_progress', 'waiting_for_customer', 'escalated', 'reopened'];

        // Determine stats & workloads
        $stats = [];
        $workloads = [];

        if ($employee->isAdmin()) {
            // Admin Statistics
            $stats['total'] = Ticket::count();
            $stats['open'] = Ticket::whereHas('status', function($q) use ($activeStatuses) {
                $q->whereIn('slug', $activeStatuses);
            })->count();
            
            // SLA Breached (estimated resolution in the past, status active)
            $stats['sla_breached'] = Ticket::whereHas('status', function($q) use ($activeStatuses) {
                    $q->whereIn('slug', $activeStatuses);
                })
                ->whereNotNull('estimated_resolution_at')
                ->where('estimated_resolution_at', '<', Carbon::now())
                ->count();

            $stats['closed'] = Ticket::whereHas('status', function($q) {
                $q->whereIn('slug', ['resolved', 'closed']);
            })->count();

            // Staff workload listing
            $allEmployees = Employee::with(['role', 'department'])->get();
            foreach ($allEmployees as $emp) {
                $activeCount = Ticket::where('assigned_to', $emp->id)
                    ->whereHas('status', function($q) use ($activeStatuses) {
                        $q->whereIn('slug', $activeStatuses);
                    })->count();

                $workloads[] = [
                    'name' => $emp->name,
                    'department' => $emp->department ? $emp->department->name : 'N/A',
                    'active_count' => $activeCount,
                    'is_available' => $emp->is_available,
                ];
            }

        } elseif ($employee->isTeamLead()) {
            // Team Lead Statistics
            $deptId = $employee->department_id;
            
            $stats['total'] = Ticket::where('department_id', $deptId)->count();
            
            $stats['unassigned'] = Ticket::where('department_id', $deptId)
                ->whereNull('assigned_to')
                ->count();
            
            $stats['sla_breached'] = Ticket::where('department_id', $deptId)
                ->whereHas('status', function($q) use ($activeStatuses) {
                    $q->whereIn('slug', $activeStatuses);
                })
                ->whereNotNull('estimated_resolution_at')
                ->where('estimated_resolution_at', '<', Carbon::now())
                ->count();

            $stats['resolved_today'] = Ticket::where('department_id', $deptId)
                ->whereHas('status', function($q) {
                    $q->where('slug', 'resolved');
                })
                ->where('actual_resolution_at', '>=', Carbon::today())
                ->count();

            // Department Staff workloads
            $deptStaff = Employee::where('department_id', $deptId)->get();
            foreach ($deptStaff as $emp) {
                $activeCount = Ticket::where('assigned_to', $emp->id)
                    ->whereHas('status', function($q) use ($activeStatuses) {
                        $q->whereIn('slug', $activeStatuses);
                    })->count();

                $workloads[] = [
                    'name' => $emp->name,
                    'department' => $emp->department ? $emp->department->name : 'N/A',
                    'active_count' => $activeCount,
                    'is_available' => $emp->is_available,
                ];
            }

        } else {
            // Staff statistics
            $stats['assigned'] = Ticket::where('assigned_to', $employee->id)->count();
            
            $stats['in_progress'] = Ticket::where('assigned_to', $employee->id)
                ->whereHas('status', function($q) {
                    $q->where('slug', 'in_progress');
                })->count();

            // Due in next 24h
            $stats['sla_due'] = Ticket::where('assigned_to', $employee->id)
                ->whereHas('status', function($q) use ($activeStatuses) {
                    $q->whereIn('slug', $activeStatuses);
                })
                ->whereNotNull('estimated_resolution_at')
                ->whereBetween('estimated_resolution_at', [Carbon::now(), Carbon::now()->addDay()])
                ->count();

            $stats['resolved_today'] = Ticket::where('assigned_to', $employee->id)
                ->whereHas('status', function($q) {
                    $q->where('slug', 'resolved');
                })
                ->where('actual_resolution_at', '>=', Carbon::today())
                ->count();
        }

        // Setup filterable queues
        $query = Ticket::with(['customer', 'department', 'priority', 'status', 'assignedTo']);

        // Scope queue access
        if ($employee->isTeamLead()) {
            $query->where('department_id', $employee->department_id);
        } elseif ($employee->isStaff()) {
            $query->where(function($q) use ($employee) {
                $q->where('assigned_to', $employee->id)
                  ->orWhere('department_id', $employee->department_id);
            });
        }
        // Admin sees all

        // Apply filters
        if ($request->has('status') && $request->status !== '') {
            $query->whereHas('status', function($q) use ($request) {
                $q->where('slug', $request->status);
            });
        }
        if ($request->has('priority') && $request->priority !== '') {
            $query->whereHas('priority', function($q) use ($request) {
                $q->where('slug', $request->priority);
            });
        }
        if ($request->has('department') && $request->department !== '') {
            $query->where('department_id', $request->department);
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        $statuses = TicketStatus::all();
        $priorities = TicketPriority::all();
        $departments = Department::all();
        $notifications = $employee->unreadNotifications;

        return view('dashboard.employee_dashboard', compact(
            'stats',
            'workloads',
            'tickets',
            'statuses',
            'priorities',
            'departments',
            'notifications'
        ));
    }

    /**
     * Self toggle employee availability.
     */
    public function toggleAvailability()
    {
        $employee = Auth::guard('employee')->user();
        
        if ($employee) {
            $employee->is_available = !$employee->is_available;
            $employee->save();
        }

        return back()->with('success', 'Your availability status has been updated.');
    }
}
