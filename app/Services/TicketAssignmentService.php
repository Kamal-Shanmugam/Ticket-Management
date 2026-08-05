<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\Employee;
use App\Models\TicketAssignment;
use App\Models\TicketLog;
use App\Models\TicketStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TicketAssignmentService
{
    /**
     * Automatically assign a ticket to an employee in the same department
     * based on active workload and SLA expectations.
     */
    public function assign(Ticket $ticket): ?Employee
    {
        $departmentId = $ticket->department_id;
        if (!$departmentId) {
            return null;
        }

        // 1. Find employees belonging to the department who are currently available
        $employees = Employee::where('department_id', $departmentId)
            ->where('is_available', true)
            ->get();

        if ($employees->isEmpty()) {
            return null; // No available employees
        }

        // Active statuses are: 'open', 'assigned', 'in_progress', 'waiting_for_customer', 'escalated', 'reopened'
        $activeStatuses = ['open', 'assigned', 'in_progress', 'waiting_for_customer', 'escalated', 'reopened'];

        // 2. Count active tickets for each available employee
        $employeeWorkloads = $employees->map(function ($employee) use ($activeStatuses) {
            $activeCount = Ticket::where('assigned_to', $employee->id)
                ->whereHas('status', function ($query) use ($activeStatuses) {
                    $query->whereIn('slug', $activeStatuses);
                })
                ->count();
            return [
                'employee' => $employee,
                'active_count' => $activeCount,
            ];
        });

        // 3. Check if there are employees with 0 active tickets
        $freeEmployees = $employeeWorkloads->filter(fn($e) => $e['active_count'] === 0);

        $chosenEmployee = null;

        if ($freeEmployees->isNotEmpty()) {
            // Choose the earliest available employee.
            // We order by last_assigned_at ASC. Those who have last_assigned_at as null (never assigned) come first.
            $chosen = $freeEmployees->sortBy(function ($item) {
                return $item['employee']->last_assigned_at ? $item['employee']->last_assigned_at->timestamp : 0;
            })->first();

            $chosenEmployee = $chosen['employee'];
        } else {
            // 4. Every employee has active tickets.
            // Select the employee expected to become free first.
            // Free time = max(estimated_resolution_at) among their active tickets.
            $employeeFreeTimes = $employees->map(function ($emp) use ($activeStatuses) {
                $activeTickets = Ticket::where('assigned_to', $emp->id)
                    ->whereHas('status', function ($query) use ($activeStatuses) {
                        $query->whereIn('slug', $activeStatuses);
                    })
                    ->get();

                // Find the latest estimated resolution timestamp
                $maxEstimated = $activeTickets->max(function ($t) {
                    return $t->estimated_resolution_at ? $t->estimated_resolution_at->timestamp : Carbon::now()->addHours(24)->timestamp;
                });

                return [
                    'employee' => $emp,
                    'free_at_timestamp' => $maxEstimated ?? Carbon::now()->timestamp,
                ];
            });

            $chosen = $employeeFreeTimes->sortBy('free_at_timestamp')->first();
            $chosenEmployee = $chosen['employee'];
        }

        if (!$chosenEmployee) {
            return null;
        }

        // 5. Perform the assignment inside a transaction
        DB::transaction(function () use ($ticket, $chosenEmployee) {
            $ticket->assigned_to = $chosenEmployee->id;

            // Move status to Assigned
            $assignedStatus = TicketStatus::where('slug', 'assigned')->first();
            if ($assignedStatus) {
                $ticket->ticket_status_id = $assignedStatus->id;
            }
            $ticket->save();

            // Create assignment record
            TicketAssignment::create([
                'ticket_id' => $ticket->id,
                'employee_id' => $chosenEmployee->id,
                'assigned_by' => null, // null indicates system auto-assignment
                'assigned_at' => Carbon::now(),
            ]);

            // Update the employee's last_assigned_at timestamp
            $chosenEmployee->last_assigned_at = Carbon::now();
            $chosenEmployee->save();

            // Write to audit trail log
            TicketLog::create([
                'ticket_id' => $ticket->id,
                'performed_by_type' => null,
                'performed_by_id' => null,
                'action' => 'assigned',
                'details' => [
                    'assigned_to_id' => $chosenEmployee->id,
                    'assigned_to_name' => $chosenEmployee->name,
                    'method' => 'auto',
                ],
            ]);
        });

        return $chosenEmployee;
    }
}
