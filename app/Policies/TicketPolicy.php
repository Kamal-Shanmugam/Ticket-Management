<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\Employee;
use App\Models\Customer;
use Illuminate\Contracts\Auth\Authenticatable;

class TicketPolicy
{
    /**
     * Determine whether the user can view the ticket.
     */
    public function view(Authenticatable $user, Ticket $ticket): bool
    {
        if ($user instanceof Customer) {
            return $ticket->customer_id === $user->id;
        }

        if ($user instanceof Employee) {
            if ($user->isAdmin()) {
                return true;
            }

            if ($user->isTeamLead()) {
                return $ticket->department_id === $user->department_id;
            }

            if ($user->isStaff()) {
                return $ticket->assigned_to === $user->id || $ticket->department_id === $user->department_id;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can update the ticket (status, details, etc.).
     */
    public function update(Authenticatable $user, Ticket $ticket): bool
    {
        if ($user instanceof Customer) {
            // Customer can only close their own open tickets, or update them.
            return $ticket->customer_id === $user->id && !$ticket->isClosed();
        }

        if ($user instanceof Employee) {
            if ($user->isAdmin()) {
                return true;
            }

            if ($user->isTeamLead()) {
                return $ticket->department_id === $user->department_id;
            }

            if ($user->isStaff()) {
                // Staff can only update if assigned to them and same department.
                return $ticket->assigned_to === $user->id && $ticket->department_id === $user->department_id;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can manually assign/reassign this ticket.
     */
    public function assign(Authenticatable $user, Ticket $ticket): bool
    {
        if ($user instanceof Employee) {
            if ($user->isAdmin()) {
                return true;
            }

            if ($user->isTeamLead()) {
                // Team Leads can assign tickets within their department
                return $ticket->department_id === $user->department_id;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can comment on the ticket.
     */
    public function comment(Authenticatable $user, Ticket $ticket): bool
    {
        if ($user instanceof Customer) {
            // Customer can only reply to their own tickets and if not closed
            return $ticket->customer_id === $user->id && !$ticket->isClosed();
        }

        if ($user instanceof Employee) {
            if ($user->isAdmin()) {
                return true;
            }

            if ($user->isTeamLead()) {
                return $ticket->department_id === $user->department_id;
            }

            if ($user->isStaff()) {
                // Staff can comment if the ticket is in their department
                return $ticket->department_id === $user->department_id;
            }
        }

        return false;
    }
}
