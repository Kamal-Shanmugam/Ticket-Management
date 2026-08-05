@extends('layouts.app')

@section('title', 'Ticket #' . $ticket->id . ' - SupportSphere')

@section('content')
<div style="margin-bottom: 1.5rem;">
    @if(Auth::guard('employee')->check())
        <a href="{{ route('employee.dashboard') }}" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Back to Queue
        </a>
    @else
        <a href="{{ route('customer.dashboard') }}" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
    @endif
</div>

<div class="detail-grid">
    
    <!-- Left Panel: Ticket Detail & Comments Thread -->
    <div>
        <div class="main-panel">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
                <div>
                    <span style="color: var(--text-muted); font-size: 0.9rem; font-weight: 600;">TICKET #{{ $ticket->id }}</span>
                    <h2>{{ $ticket->title }}</h2>
                </div>
                <div>
                    @if($ticket->isSlaBreached())
                        <span class="badge badge-sla-breached">SLA BREACHED</span>
                    @endif
                </div>
            </div>

            <div class="ticket-body-desc">
                {{ $ticket->description }}
            </div>

            <!-- Ticket Attachments -->
            @if($ticket->attachments->whereNull('ticket_comment_id')->isNotEmpty())
                <div style="margin-bottom: 2rem;">
                    <h5 style="color: var(--text-secondary); margin-bottom: 0.5rem;">Original Attachments:</h5>
                    <div class="comment-attachments">
                        @foreach($ticket->attachments->whereNull('ticket_comment_id') as $att)
                            <a href="{{ asset('storage/' . $att->file_path) }}" target="_blank" class="attachment-chip">
                                <i class="fa-solid fa-file-invoice"></i> {{ $att->file_name }} 
                                <span style="color: var(--text-muted); font-size: 0.75rem;">({{ round($att->file_size / 1024, 1) }} KB)</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Comments/Conversation Thread -->
            <div class="comment-section">
                <h3>Conversation History</h3>
                <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 1.5rem;">Replies between customer and support agents.</p>

                <div class="comment-list">
                    @foreach($ticket->comments as $comment)
                        @php
                            $isCustomer = $comment->commenter_type === 'App\Models\Customer';
                        @endphp
                        <div class="comment-bubble {{ $isCustomer ? 'customer-reply' : 'employee-reply' }}">
                            <div class="comment-header">
                                <span>
                                    <i class="fa-solid {{ $isCustomer ? 'fa-user' : 'fa-user-tie' }}"></i> 
                                    {{ $comment->commenter ? $comment->commenter->name : 'Deleted User' }}
                                    <span style="font-size: 0.75rem; color: var(--text-muted);">
                                        ({{ $isCustomer ? 'Customer' : ($comment->commenter && $comment->commenter->role ? $comment->commenter->role->name : 'Staff') }})
                                    </span>
                                </span>
                                <span>{{ $comment->created_at->format('M d, Y H:i') }}</span>
                            </div>
                            <div class="comment-text">
                                {{ $comment->comment }}
                            </div>
                            
                            @if($comment->attachments->isNotEmpty())
                                <div class="comment-attachments">
                                    @foreach($comment->attachments as $att)
                                        <a href="{{ asset('storage/' . $att->file_path) }}" target="_blank" class="attachment-chip">
                                            <i class="fa-solid fa-paperclip"></i> {{ $att->file_name }}
                                            <span style="color: var(--text-muted); font-size: 0.75rem;">({{ round($att->file_size / 1024, 1) }} KB)</span>
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Add Comment Form -->
                @if(!$ticket->isClosed() || Auth::guard('employee')->check())
                    <form action="{{ route('tickets.comment.store', $ticket->id) }}" method="POST" enctype="multipart/form-data" style="margin-top: 2rem;">
                        @csrf
                        <div class="form-group">
                            <label class="form-label" for="reply_comment">Post a Reply</label>
                            <textarea class="form-textarea" id="reply_comment" name="comment" rows="4" required placeholder="Type your response here... @if($ticket->isClosed() || $ticket->isResolved()) (Replying will automatically Reopen the ticket) @endif"></textarea>
                            @error('comment')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="reply_files">Upload Attachments (Max 5 files)</label>
                            <input class="form-input" type="file" id="reply_files" name="attachments[]" multiple style="padding: 0.5rem 0.75rem;">
                            @error('attachments')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-reply"></i> Submit Reply
                        </button>
                    </form>
                @else
                    <div style="background: rgba(255,255,255,0.02); padding: 1.5rem; text-align: center; border-radius: 8px; border: 1px dashed var(--glass-border); color: var(--text-secondary);">
                        <i class="fa-solid fa-lock" style="font-size: 1.5rem; margin-bottom: 0.5rem;"></i>
                        <p>This ticket is closed. If you require further assistance, please raise a new support request.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Right Panel: Sidebar Metadata & Controls -->
    <div>
        <!-- Ticket Metadata Summary -->
        <div class="sidebar-panel" style="margin-bottom: 2rem;">
            <h3>Ticket Details</h3>
            <hr style="border-color: var(--glass-border); margin: 1rem 0;">
            
            <div style="display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.9rem;">
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--text-secondary);">Status:</span>
                    <span class="badge badge-status-{{ $ticket->status->slug }}">{{ $ticket->status->name }}</span>
                </div>
                
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--text-secondary);">Priority:</span>
                    <span class="badge badge-priority-{{ $ticket->priority->slug }}">{{ $ticket->priority->name }}</span>
                </div>
                
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--text-secondary);">Department:</span>
                    <span style="font-weight: 500;">{{ $ticket->department->name }}</span>
                </div>

                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--text-secondary);">Customer:</span>
                    <span style="font-weight: 500;">{{ $ticket->customer->name }}</span>
                </div>

                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--text-secondary);">Assigned To:</span>
                    <span style="font-weight: 500; color: var(--accent-indigo);">
                        {{ $ticket->assignedTo ? $ticket->assignedTo->name : 'Unassigned' }}
                    </span>
                </div>

                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--text-secondary);">Raised:</span>
                    <span>{{ $ticket->created_at->format('M d, Y H:i') }}</span>
                </div>

                @if($ticket->estimated_resolution_at)
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-secondary);">SLA Target:</span>
                        <span style="font-weight: 500; color: {{ $ticket->isSlaBreached() ? '#ef4444' : 'inherit' }}">
                            {{ $ticket->estimated_resolution_at->format('M d, Y H:i') }}
                        </span>
                    </div>
                @endif

                @if($ticket->actual_resolution_at)
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-secondary);">Resolved At:</span>
                        <span style="font-weight: 500; color: var(--status-resolved);">
                            {{ $ticket->actual_resolution_at->format('M d, Y H:i') }}
                        </span>
                    </div>
                @endif
            </div>
        </div>

        @php
            $currentUser = Auth::guard('employee')->user() ?: Auth::guard('customer')->user();
        @endphp

        <!-- Update Status Actions (For authorized employees or customer closing) -->
        @if(Gate::forUser($currentUser)->allows('update', $ticket))
            <div class="sidebar-panel" style="margin-bottom: 2rem;">
                <h3>Update Status</h3>
                <hr style="border-color: var(--glass-border); margin: 0.75rem 0;">
                
                @if(Auth::guard('employee')->check())
                    <form action="{{ route('tickets.status.update', $ticket->id) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <select class="form-select" name="ticket_status_id" required onchange="this.form.submit()">
                                @foreach($statuses as $st)
                                    <option value="{{ $st->id }}" {{ $ticket->ticket_status_id == $st->id ? 'selected' : '' }}>
                                        Move to: {{ $st->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                @else
                    @php
                        $closedStatus = $statuses->where('slug', 'closed')->first();
                    @endphp
                    @if($closedStatus && !$ticket->isClosed())
                        <form action="{{ route('tickets.status.update', $ticket->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="ticket_status_id" value="{{ $closedStatus->id }}">
                            <button type="submit" class="btn btn-danger btn-block btn-sm">
                                <i class="fa-solid fa-circle-xmark"></i> Close Ticket
                            </button>
                        </form>
                    @endif
                @endif
            </div>
        @endif

        @if(Auth::guard('employee')->check())
            <!-- Manual Assignment Actions (Admin/TL only) -->
            @if(Gate::forUser($currentUser)->allows('assign', $ticket))
                <div class="sidebar-panel" style="margin-bottom: 2rem;">
                    <h3>Manual Assignment</h3>
                    <hr style="border-color: var(--glass-border); margin: 0.75rem 0;">
                    
                    <form action="{{ route('tickets.assign.submit', $ticket->id) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label class="form-label" for="manual_agent">Select Staff</label>
                            <select class="form-select" id="manual_agent" name="employee_id" required>
                                <option value="" disabled selected>Select agent</option>
                                @foreach($eligibleEmployees as $eligible)
                                    <option value="{{ $eligible->id }}" {{ $ticket->assigned_to == $eligible->id ? 'selected' : '' }}>
                                        {{ $eligible->name }} ({{ $eligible->is_available ? 'Available' : 'Unavailable' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('employee_id')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-secondary btn-sm btn-block">
                            <i class="fa-solid fa-user-tag"></i> Assign Agent
                        </button>
                    </form>
                </div>

                <!-- Extend SLA Form -->
                <div class="sidebar-panel" style="margin-bottom: 2rem;">
                    <h3>Extend SLA Deadline</h3>
                    <hr style="border-color: var(--glass-border); margin: 0.75rem 0;">
                    
                    <form action="{{ route('tickets.sla.extend', $ticket->id) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label class="form-label" for="sla_date">New Due Date</label>
                            <input class="form-input" type="datetime-local" id="sla_date" name="estimated_resolution_at" required 
                                   value="{{ $ticket->estimated_resolution_at ? $ticket->estimated_resolution_at->format('Y-m-d\TH:i') : '' }}">
                            @error('estimated_resolution_at')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-secondary btn-sm btn-block">
                            <i class="fa-solid fa-clock-rotate-left"></i> Save Extension
                        </button>
                    </form>
                </div>
            @endif
        @endif

        <!-- Audit Trail / Ticket Logs -->
        <div class="sidebar-panel">
            <h3>Audit Trail</h3>
            <hr style="border-color: var(--glass-border); margin: 1rem 0;">
            
            <div class="timeline">
                @foreach($ticket->logs->sortByDesc('created_at') as $log)
                    <div class="timeline-item action-{{ $log->action }}">
                        <div class="timeline-date">{{ $log->created_at->format('M d, Y H:i') }}</div>
                        <div class="timeline-text">
                            <strong>{{ $log->action === 'created' ? 'Ticket Raised' : ($log->action === 'assigned' ? 'Agent Assigned' : ($log->action === 'reassigned' ? 'Agent Reassigned' : ($log->action === 'status_changed' ? 'Status Updated' : ($log->action === 'sla_updated' ? 'SLA Extended' : ucfirst($log->action))))) }}</strong>
                        </div>
                        <div class="timeline-details">
                            @if($log->action === 'status_changed' && isset($log->details['new_status']))
                                Moved to status "{{ $log->details['new_status'] }}"
                            @elseif($log->action === 'assigned' || $log->action === 'reassigned')
                                Assigned to {{ $log->details['assigned_to_name'] ?? 'Agent' }} ({{ $log->details['method'] ?? 'manual' }}).
                            @elseif($log->action === 'sla_updated')
                                Extended to {{ Carbon\Carbon::parse($log->details['new_sla'])->format('M d, H:i') }}
                            @elseif($log->action === 'comment_added')
                                Reply added.
                            @elseif(is_array($log->details) && isset($log->details['message']))
                                {{ $log->details['message'] }}
                            @elseif(is_string($log->details))
                                {{ $log->details }}
                            @endif
                            <br>
                            <span style="color: var(--text-muted); font-size: 0.75rem;">
                                By: {{ $log->performedBy ? $log->performedBy->name : 'System' }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

</div>
@endsection
