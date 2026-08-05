<?php $__env->startSection('title', 'Staff Dashboard - SupportSphere'); ?>

<?php $__env->startSection('content'); ?>
<!-- Dynamic Statistics Panel -->
<div class="dashboard-grid">
    <?php if(Auth::guard('employee')->user()->isAdmin()): ?>
        <div class="stat-card">
            <div>
                <div class="stat-title">System Tickets</div>
                <div class="stat-value"><?php echo e($stats['total']); ?></div>
            </div>
            <div class="stat-desc">Total tickets across system</div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-title">Open Tickets</div>
                <div class="stat-value" style="color: var(--status-open);"><?php echo e($stats['open']); ?></div>
            </div>
            <div class="stat-desc">Awaiting resolution</div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-title">SLA Breached</div>
                <div class="stat-value" style="color: var(--status-escalated);"><?php echo e($stats['sla_breached']); ?></div>
            </div>
            <div class="stat-desc">Active tickets past SLA due date</div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-title">Closed Tickets</div>
                <div class="stat-value" style="color: var(--status-closed);"><?php echo e($stats['closed']); ?></div>
            </div>
            <div class="stat-desc">Resolved and closed</div>
        </div>
    <?php elseif(Auth::guard('employee')->user()->isTeamLead()): ?>
        <div class="stat-card">
            <div>
                <div class="stat-title">Team Tickets</div>
                <div class="stat-value"><?php echo e($stats['total']); ?></div>
            </div>
            <div class="stat-desc">Tickets in your department</div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-title">Unassigned</div>
                <div class="stat-value" style="color: var(--status-open);"><?php echo e($stats['unassigned']); ?></div>
            </div>
            <div class="stat-desc">Awaiting allocation</div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-title">SLA Warning / Breached</div>
                <div class="stat-value" style="color: var(--status-escalated);"><?php echo e($stats['sla_breached']); ?></div>
            </div>
            <div class="stat-desc">Attention required</div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-title">Resolved Today</div>
                <div class="stat-value" style="color: var(--status-resolved);"><?php echo e($stats['resolved_today']); ?></div>
            </div>
            <div class="stat-desc">Solved in last 24h</div>
        </div>
    <?php else: ?>
        <div class="stat-card">
            <div>
                <div class="stat-title">Assigned Tickets</div>
                <div class="stat-value"><?php echo e($stats['assigned']); ?></div>
            </div>
            <div class="stat-desc">Assigned to you</div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-title">In Progress</div>
                <div class="stat-value" style="color: var(--status-progress);"><?php echo e($stats['in_progress']); ?></div>
            </div>
            <div class="stat-desc">Actively working on</div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-title">SLA Due Soon</div>
                <div class="stat-value" style="color: var(--status-waiting);"><?php echo e($stats['sla_due']); ?></div>
            </div>
            <div class="stat-desc">Due in next 24h</div>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-title">Resolved Today</div>
                <div class="stat-value" style="color: var(--status-resolved);"><?php echo e($stats['resolved_today']); ?></div>
            </div>
            <div class="stat-desc">Tickets solved today</div>
        </div>
    <?php endif; ?>
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
                    <?php if(Auth::guard('employee')->user()->is_available): ?>
                        <span style="color: var(--status-resolved); font-weight: 600;">ACTIVE & AVAILABLE (Auto-assignment enabled)</span>
                    <?php else: ?>
                        <span style="color: var(--text-muted); font-weight: 600;">OFFLINE / BUSY (Auto-assignment ignored)</span>
                    <?php endif; ?>
                </p>
            </div>
            <form action="<?php echo e(route('employee.availability')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <button type="submit" class="btn <?php echo e(Auth::guard('employee')->user()->is_available ? 'btn-secondary' : 'btn-primary'); ?> btn-sm">
                    <i class="fa-solid fa-power-off"></i> Toggle Status
                </button>
            </form>
        </div>
    </div>

    <!-- In-app Notifications -->
    <?php if($notifications->isNotEmpty()): ?>
        <div class="main-panel" style="flex: 2; min-width: 400px; padding: 1.25rem;">
            <h4 style="margin-bottom: 0.5rem;"><i class="fa-solid fa-bell" style="color: var(--accent-purple);"></i> Notifications Center</h4>
            <div style="max-height: 120px; overflow-y: auto; display: flex; flex-direction: column; gap: 0.5rem;">
                <?php $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notif): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div style="background: rgba(255,255,255,0.02); border: 1px dashed var(--glass-border); padding: 0.5rem 0.75rem; border-radius: 6px; display: flex; align-items: center; justify-content: space-between; font-size: 0.85rem;">
                        <span><?php echo e($notif->data['message'] ?? 'Notification received'); ?></span>
                        <form action="<?php echo e(route('notifications.read', $notif->id)); ?>" method="POST" style="display:inline;">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="btn btn-secondary btn-sm" style="padding: 0.15rem 0.4rem; font-size: 0.7rem;">
                                Dismiss
                            </button>
                        </form>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="portal-layout">
    
    <!-- Sidebar: Filters & Workloads -->
    <div class="sidebar-panel">
        <h4 style="margin-bottom: 1rem;"><i class="fa-solid fa-filter"></i> Filters</h4>
        <form action="<?php echo e(route('employee.dashboard')); ?>" method="GET">
            <div class="form-group">
                <label class="form-label" for="filter_status">Status</label>
                <select class="form-select" id="filter_status" name="status" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $st): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($st->slug); ?>" <?php echo e(request('status') === $st->slug ? 'selected' : ''); ?>>
                            <?php echo e($st->name); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="filter_priority">Priority</label>
                <select class="form-select" id="filter_priority" name="priority" onchange="this.form.submit()">
                    <option value="">All Priorities</option>
                    <?php $__currentLoopData = $priorities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($pr->slug); ?>" <?php echo e(request('priority') === $pr->slug ? 'selected' : ''); ?>>
                            <?php echo e($pr->name); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            
            <?php if(Auth::guard('employee')->user()->isAdmin()): ?>
                <div class="form-group">
                    <label class="form-label" for="filter_dept">Department</label>
                    <select class="form-select" id="filter_dept" name="department" onchange="this.form.submit()">
                        <option value="">All Departments</option>
                        <?php $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($dp->id); ?>" <?php echo e(request('department') == $dp->id ? 'selected' : ''); ?>>
                                <?php echo e($dp->name); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <a href="<?php echo e(route('employee.dashboard')); ?>" class="btn btn-secondary btn-block btn-sm">Clear Filters</a>
        </form>

        <!-- Department/Employee Workload Chart Widget (Admin/TL only) -->
        <?php if(Auth::guard('employee')->user()->isAdmin() || Auth::guard('employee')->user()->isTeamLead()): ?>
            <div style="margin-top: 2rem; border-top: 1px solid var(--glass-border); padding-top: 1.5rem;">
                <h4 style="margin-bottom: 1rem;"><i class="fa-solid fa-users"></i> Staff Workload</h4>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <?php $__currentLoopData = $workloads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $workload): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div style="background: rgba(0,0,0,0.2); padding: 0.75rem; border-radius: 8px; border: 1px solid var(--glass-border);">
                            <div style="display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 500; margin-bottom: 0.25rem;">
                                <span><?php echo e($workload['name']); ?></span>
                                <span style="color: var(--accent-indigo);"><?php echo e($workload['active_count']); ?> Active</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--text-secondary);">
                                <span><?php echo e($workload['department'] ?? 'No Dept'); ?></span>
                                <span><?php echo $workload['is_available'] ? '<span style="color:var(--status-resolved);">Online</span>' : '<span style="color:var(--text-muted);">Offline</span>'; ?></span>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Main Workspace Panel: Tickets List -->
    <div class="main-panel">
        <h3 style="margin-bottom: 1.5rem;"><i class="fa-solid fa-envelope-open-text"></i> Ticket Queue</h3>
        
        <?php if($tickets->isEmpty()): ?>
            <div style="text-align: center; padding: 4rem 1rem; color: var(--text-muted);">
                <i class="fa-solid fa-inbox" style="font-size: 3.5rem; margin-bottom: 1.25rem;"></i>
                <p>No tickets matching the filter queues are assigned or available.</p>
            </div>
        <?php else: ?>
            <div class="ticket-list">
                <?php $__currentLoopData = $tickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="ticket-item">
                        <div class="ticket-info">
                            <h3><a href="<?php echo e(route('tickets.show', $ticket->id)); ?>">#<?php echo e($ticket->id); ?> - <?php echo e($ticket->title); ?></a></h3>
                            
                            <div class="ticket-meta">
                                <div class="meta-item">
                                    <span class="badge badge-status-<?php echo e($ticket->status->slug); ?>"><?php echo e($ticket->status->name); ?></span>
                                </div>
                                <div class="meta-item">
                                    <span class="badge badge-priority-<?php echo e($ticket->priority->slug); ?>"><?php echo e($ticket->priority->name); ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fa-solid fa-user"></i> <?php echo e($ticket->customer->name); ?>

                                </div>
                                <div class="meta-item">
                                    <i class="fa-solid fa-building"></i> <?php echo e($ticket->department->name); ?>

                                </div>
                                <div class="meta-item">
                                    <i class="fa-solid fa-user-check"></i> Assignee: <?php echo e($ticket->assignedTo ? $ticket->assignedTo->name : 'Unassigned'); ?>

                                </div>
                                <div class="meta-item">
                                    <i class="fa-solid fa-clock"></i> Raised <?php echo e($ticket->created_at->diffForHumans()); ?>

                                </div>
                                
                                <?php if($ticket->estimated_resolution_at && !$ticket->isClosed() && !$ticket->isResolved()): ?>
                                    <div class="meta-item">
                                        <?php if($ticket->isSlaBreached()): ?>
                                            <span class="badge badge-sla-breached">SLA BREACHED</span>
                                        <?php else: ?>
                                            <span style="color: var(--text-secondary);"><i class="fa-solid fa-clock"></i> SLA Due: <?php echo e($ticket->estimated_resolution_at->format('M d, H:i')); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div>
                            <a href="<?php echo e(route('tickets.show', $ticket->id)); ?>" class="btn btn-secondary btn-sm">
                                Manage Ticket <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            
            <div style="margin-top: 1.5rem;">
                <?php echo e($tickets->links()); ?>

            </div>
        <?php endif; ?>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Alpha4\ticketMonitoring\resources\views/dashboard/employee_dashboard.blade.php ENDPATH**/ ?>