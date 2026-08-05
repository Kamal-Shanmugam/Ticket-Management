<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\TicketPriority;
use App\Models\Ticket;
use App\Models\TicketStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerDashboardController extends Controller
{
    public function index()
    {
        $customer = Auth::guard('customer')->user();

        if (!$customer) {
            return redirect()->route('login');
        }

        // Stats
        $ticketsCount = Ticket::where('customer_id', $customer->id)->count();
        
        $activeStatuses = ['open', 'assigned', 'in_progress', 'waiting_for_customer', 'escalated', 'reopened'];
        $activeCount = Ticket::where('customer_id', $customer->id)
            ->whereHas('status', function($q) use ($activeStatuses) {
                $q->whereIn('slug', $activeStatuses);
            })->count();

        $resolvedStatuses = ['resolved', 'closed'];
        $resolvedCount = Ticket::where('customer_id', $customer->id)
            ->whereHas('status', function($q) use ($resolvedStatuses) {
                $q->whereIn('slug', $resolvedStatuses);
            })->count();

        $stats = [
            'total' => $ticketsCount,
            'active' => $activeCount,
            'resolved' => $resolvedCount,
        ];

        // Fetch support entities
        $departments = Department::orderBy('name')->get();
        $priorities = TicketPriority::orderBy('resolution_hours', 'asc')->get();

        // Customer raised tickets list
        $tickets = Ticket::where('customer_id', $customer->id)
            ->with(['status', 'priority', 'department'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Fetch notifications
        $notifications = $customer->unreadNotifications;

        return view('dashboard.customer_dashboard', compact('stats', 'departments', 'priorities', 'tickets', 'notifications'));
    }
}
