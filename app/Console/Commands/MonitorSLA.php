<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;
use App\Models\Employee;
use App\Models\Role;
use App\Notifications\SLABreachWarningNotification;
use App\Notifications\SLABreachedNotification;
use App\Models\TicketLog;
use Carbon\Carbon;

class MonitorSLA extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sla:monitor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor tickets for SLA warnings and breaches';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $activeStatuses = ['open', 'assigned', 'in_progress', 'waiting_for_customer', 'escalated', 'reopened'];

        // 1. SLA Warning (due within 1 hour, not yet warned, and in the future)
        $warningTickets = Ticket::whereHas('status', function ($query) use ($activeStatuses) {
                $query->whereIn('slug', $activeStatuses);
            })
            ->where('sla_warning_notified', false)
            ->whereNotNull('estimated_resolution_at')
            ->where('estimated_resolution_at', '<=', Carbon::now()->addHour())
            ->where('estimated_resolution_at', '>', Carbon::now())
            ->get();

        foreach ($warningTickets as $ticket) {
            // Notify Customer
            if ($ticket->customer) {
                $ticket->customer->notify(new SLABreachWarningNotification($ticket));
            }
            // Notify Assigned Employee
            if ($ticket->assignedTo) {
                $ticket->assignedTo->notify(new SLABreachWarningNotification($ticket));
            }

            $ticket->sla_warning_notified = true;
            $ticket->save();

            $this->info("Sent SLA warning for Ticket #{$ticket->id}");
        }

        // 2. SLA Breached (due in the past and not yet processed)
        $breachedTickets = Ticket::whereHas('status', function ($query) use ($activeStatuses) {
                $query->whereIn('slug', $activeStatuses);
            })
            ->where('sla_breached_notified', false)
            ->whereNotNull('estimated_resolution_at')
            ->where('estimated_resolution_at', '<', Carbon::now())
            ->get();

        foreach ($breachedTickets as $ticket) {
            // Notify Assigned Employee
            if ($ticket->assignedTo) {
                $ticket->assignedTo->notify(new SLABreachedNotification($ticket));
            }
            
            // Notify Team Leads of the department
            $teamLeadRole = Role::where('slug', 'team_lead')->first();
            if ($teamLeadRole && $ticket->department_id) {
                $leads = Employee::where('role_id', $teamLeadRole->id)
                    ->where('department_id', $ticket->department_id)
                    ->get();
                foreach ($leads as $lead) {
                    $lead->notify(new SLABreachedNotification($ticket));
                }
            }

            // Auto-escalate the ticket status to Escalated if it is breached
            $escalatedStatus = \App\Models\TicketStatus::where('slug', 'escalated')->first();
            
            $ticket->sla_breached_notified = true;
            if ($escalatedStatus) {
                $ticket->ticket_status_id = $escalatedStatus->id;
            }
            $ticket->save();

            // Log the SLA breach in the audit trail
            TicketLog::create([
                'ticket_id' => $ticket->id,
                'performed_by_type' => null,
                'performed_by_id' => null,
                'action' => 'sla_breached',
                'details' => [
                    'message' => 'Ticket breached the estimated resolution time.',
                    'estimated_resolution_at' => $ticket->estimated_resolution_at->format('Y-m-d H:i:s'),
                ],
            ]);

            $this->warn("Escalated and sent SLA breach alerts for Ticket #{$ticket->id}");
        }
    }
}
