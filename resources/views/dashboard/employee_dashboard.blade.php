@extends('layouts.app')

@section('title', 'Staff Dashboard - SupportSphere')

@section('content')
<!-- Dynamic Statistics Panel -->
<div class="dashboard-grid">
    @if(Auth::guard('employee')->user()->isAdmin())
        <div class="stat-card">
            <div>
                <div class="stat-title">System Tickets</div>
                <div class="stat-value">{{ $stats['total'] }}</div>
            </div>
            <div class="stat-desc">Total tickets across system</div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-title">Open Tickets</div>
                <div class="stat-value" style="color: var(--status-open);">{{ $stats['open'] }}</div>
            </div>
            <div class="stat-desc">Awaiting resolution</div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-title">SLA Breached</div>
                <div class="stat-value" style="color: var(--status-escalated);">{{ $stats['sla_breached'] }}</div>
            </div>
            <div class="stat-desc">Active tickets past SLA due date</div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-title">Closed Tickets</div>
                <div class="stat-value" style="color: var(--status-closed);">{{ $stats['closed'] }}</div>
            </div>
            <div class="stat-desc">Resolved and closed</div>
        </div>
    @elseif(Auth::guard('employee')->user()->isTeamLead())
        <div class="stat-card">
            <div>
                <div class="stat-title">Team Tickets</div>
                <div class="stat-value">{{ $stats['total'] }}</div>
            </div>
            <div class="stat-desc">Tickets in your department</div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-title">Unassigned</div>
                <div class="stat-value" style="color: var(--status-open);">{{ $stats['unassigned'] }}</div>
            </div>
            <div class="stat-desc">Awaiting allocation</div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-title">SLA Warning / Breached</div>
                <div class="stat-value" style="color: var(--status-escalated);">{{ $stats['sla_breached'] }}</div>
            </div>
            <div class="stat-desc">Attention required</div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-title">Resolved Today</div>
                <div class="stat-value" style="color: var(--status-resolved);">{{ $stats['resolved_today'] }}</div>
            </div>
            <div class="stat-desc">Solved in last 24h</div>
        </div>
    @else
        <div class="stat-card">
            <div>
                <div class="stat-title">Assigned Tickets</div>
                <div class="stat-value">{{ $stats['assigned'] }}</div>
            </div>
            <div class="stat-desc">Assigned to you</div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-title">In Progress</div>
                <div class="stat-value" style="color: var(--status-progress);">{{ $stats['in_progress'] }}</div>
            </div>
            <div class="stat-desc">Actively working on</div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-title">SLA Due Soon</div>
                <div class="stat-value" style="color: var(--status-waiting);">{{ $stats['sla_due'] }}</div>
            </div>
            <div class="stat-desc">Due in next 24h</div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-title">Resolved Today</div>
                <div class="stat-value" style="color: var(--status-resolved);">{{ $stats['resolved_today'] }}</div>
            </div>
            <div class="stat-desc">Tickets solved today</div>
        </div>
    @endif
</div>

<!-- Availability & Notification Center -->
<div style="display: flex; flex-wrap: wrap; gap: 1.5rem; margin-bottom: 2rem;">
    <!-- Availability Toggle Widget -->
    <div class="main-panel" style="flex: 1; min-width: 300px; padding: 1.25rem;">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h4>Work Availability Status</h4>
                <p style="color: var(--text-secondary); font-size: 0.85rem;">
                    Currently: 
                    @if(Auth::guard('employee')->user()->is_available)
                        <span style="color: var(--status-resolved); font-weight: 600;">ACTIVE & AVAILABLE (Auto-assignment enabled)</span>
                    @else
                        <span style="color: var(--text-muted); font-weight: 600;">OFFLINE / BUSY (Auto-assignment ignored)</span>
                    @endif
                </p>
            </div>
            <form action="{{ route('employee.availability') }}" method="POST">
                @csrf
                <button type="submit" class="btn {{ Auth::guard('employee')->user()->is_available ? 'btn-secondary' : 'btn-primary' }} btn-sm">
                    <i class="fa-solid fa-power-off"></i> Toggle Status
                </button>
            </form>
        </div>
    </div>

    <!-- In-app Notifications -->
    @if($notifications->isNotEmpty())
        <div class="main-panel" style="flex: 2; min-width: 400px; padding: 1.25rem;">
            <h4 style="margin-bottom: 0.5rem;"><i class="fa-solid fa-bell" style="color: var(--accent-purple);"></i> Notifications Center</h4>
            <div style="max-height: 120px; overflow-y: auto; display: flex; flex-direction: column; gap: 0.5rem;">
                @foreach($notifications as $notif)
                    <div style="background: rgba(255,255,255,0.02); border: 1px dashed var(--glass-border); padding: 0.5rem 0.75rem; border-radius: 6px; display: flex; align-items: center; justify-content: space-between; font-size: 0.85rem;">
                        <span>{{ $notif->data['message'] ?? 'Notification received' }}</span>
                        <form action="{{ route('notifications.read', $notif->id) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-secondary btn-sm" style="padding: 0.15rem 0.4rem; font-size: 0.7rem;">
                                Dismiss
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

<div class="portal-layout">
    
    <!-- Sidebar: Filters & Workloads -->
    <div class="sidebar-panel">
        <h4 style="margin-bottom: 1rem;"><i class="fa-solid fa-filter"></i> Filters</h4>
        <form action="{{ route('employee.dashboard') }}" method="GET">
            <div class="form-group">
                <label class="form-label" for="filter_status">Status</label>
                <select class="form-select" id="filter_status" name="status" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $st)
                        <option value="{{ $st->slug }}" {{ request('status') === $st->slug ? 'selected' : '' }}>
                            {{ $st->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="filter_priority">Priority</label>
                <select class="form-select" id="filter_priority" name="priority" onchange="this.form.submit()">
                    <option value="">All Priorities</option>
                    @foreach($priorities as $pr)
                        <option value="{{ $pr->slug }}" {{ request('priority') === $pr->slug ? 'selected' : '' }}>
                            {{ $pr->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            @if(Auth::guard('employee')->user()->isAdmin())
                <div class="form-group">
                    <label class="form-label" for="filter_dept">Department</label>
                    <select class="form-select" id="filter_dept" name="department" onchange="this.form.submit()">
                        <option value="">All Departments</option>
                        @foreach($departments as $dp)
                            <option value="{{ $dp->id }}" {{ request('department') == $dp->id ? 'selected' : '' }}>
                                {{ $dp->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            
            <a href="{{ route('employee.dashboard') }}" class="btn btn-secondary btn-block btn-sm">Clear Filters</a>
        </form>

        <!-- Department/Employee Workload Chart Widget (Admin/TL only) -->
        @if(Auth::guard('employee')->user()->isAdmin() || Auth::guard('employee')->user()->isTeamLead())
            <div style="margin-top: 2rem; border-top: 1px solid var(--glass-border); padding-top: 1.5rem;">
                <h4 style="margin-bottom: 1rem;"><i class="fa-solid fa-users"></i> Staff Workload</h4>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    @foreach($workloads as $workload)
                        <div style="background: rgba(0,0,0,0.2); padding: 0.75rem; border-radius: 8px; border: 1px solid var(--glass-border);">
                            <div style="display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 500; margin-bottom: 0.25rem;">
                                <span>{{ $workload['name'] }}</span>
                                <span style="color: var(--accent-indigo);">{{ $workload['active_count'] }} Active</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--text-secondary);">
                                <span>{{ $workload['department'] ?? 'No Dept' }}</span>
                                <span>{!! $workload['is_available'] ? '<span style="color:var(--status-resolved);">Online</span>' : '<span style="color:var(--text-muted);">Offline</span>' !!}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Main Workspace Panel: Tickets List -->
    <div class="main-panel">
        <h3 style="margin-bottom: 1.5rem;"><i class="fa-solid fa-envelope-open-text"></i> Ticket Queue</h3>
        
        @if($tickets->isEmpty())
            <div style="text-align: center; padding: 4rem 1rem; color: var(--text-muted);">
                <i class="fa-solid fa-inbox" style="font-size: 3.5rem; margin-bottom: 1.25rem;"></i>
                <p>No tickets matching the filter queues are assigned or available.</p>
            </div>
        @else
            <div class="ticket-list">
                @foreach($tickets as $ticket)
                    <div class="ticket-item">
                        <div class="ticket-info">
                            <h3><a href="{{ route('tickets.show', $ticket->id) }}">#{{ $ticket->id }} - {{ $ticket->title }}</a></h3>
                            
                            <div class="ticket-meta">
                                <div class="meta-item">
                                    <span class="badge badge-status-{{ $ticket->status->slug }}">{{ $ticket->status->name }}</span>
                                </div>
                                <div class="meta-item">
                                    <span class="badge badge-priority-{{ $ticket->priority->slug }}">{{ $ticket->priority->name }}</span>
                                </div>
                                <div class="meta-item">
                                    <i class="fa-solid fa-user"></i> {{ $ticket->customer->name }}
                                </div>
                                <div class="meta-item">
                                    <i class="fa-solid fa-building"></i> {{ $ticket->department->name }}
                                </div>
                                <div class="meta-item">
                                    <i class="fa-solid fa-user-check"></i> Assignee: {{ $ticket->assignedTo ? $ticket->assignedTo->name : 'Unassigned' }}
                                </div>
                                <div class="meta-item">
                                    <i class="fa-solid fa-clock"></i> Raised {{ $ticket->created_at->diffForHumans() }}
                                </div>
                                
                                @if($ticket->estimated_resolution_at && !$ticket->isClosed() && !$ticket->isResolved())
                                    <div class="meta-item">
                                        @if($ticket->isSlaBreached())
                                            <span class="badge badge-sla-breached">SLA BREACHED</span>
                                        @else
                                            <span style="color: var(--text-secondary);"><i class="fa-solid fa-clock"></i> SLA Due: {{ $ticket->estimated_resolution_at->format('M d, H:i') }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div>
                            <a href="{{ route('tickets.show', $ticket->id) }}" class="btn btn-secondary btn-sm">
                                Manage Ticket <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <div style="margin-top: 1.5rem;">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
