<?php $__env->startSection('title', 'Customer Support Center - SupportSphere'); ?>

<?php $__env->startSection('content'); ?>
<div class="dashboard-grid">
    <div class="stat-card">
        <div>
            <div class="stat-title">Total Tickets</div>
            <div class="stat-value"><?php echo e($stats['total']); ?></div>
        </div>
        <div class="stat-desc">All raised requests</div>
    </div>
    
    <div class="stat-card">
        <div>
            <div class="stat-title">Active Tickets</div>
            <div class="stat-value" style="color: var(--status-open);"><?php echo e($stats['active']); ?></div>
        </div>
        <div class="stat-desc">Open, Assigned, or In Progress</div>
    </div>

    <div class="stat-card">
        <div>
            <div class="stat-title">Resolved Tickets</div>
            <div class="stat-value" style="color: var(--status-resolved);"><?php echo e($stats['resolved']); ?></div>
        </div>
        <div class="stat-desc">Tickets resolved or closed</div>
    </div>
</div>

<div class="portal-layout">
    
    <!-- Left Sidebar: Raise Ticket Form -->
    <div class="sidebar-panel">
        <h3>Raise a Ticket</h3>
        <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 1.5rem;">Select your department and priority level.</p>
        
        <form action="<?php echo e(route('customer.ticket.store')); ?>" method="POST" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            
            <div class="form-group">
                <label class="form-label" for="ticket_title">Ticket Subject</label>
                <input class="form-input" type="text" id="ticket_title" name="title" value="<?php echo e(old('title')); ?>" required placeholder="Summary of the issue">
                <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <span class="form-error"><?php echo e($message); ?></span>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="ticket_dept">Support Department</label>
                <select class="form-select" id="ticket_dept" name="department_id" required>
                    <option value="" disabled selected>Select department</option>
                    <?php $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dept): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($dept->id); ?>" <?php echo e(old('department_id') == $dept->id ? 'selected' : ''); ?>>
                            <?php echo e($dept->name); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['department_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <span class="form-error"><?php echo e($message); ?></span>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="ticket_priority">Severity Priority</label>
                <select class="form-select" id="ticket_priority" name="ticket_priority_id" required>
                    <option value="" disabled selected>Select priority</option>
                    <?php $__currentLoopData = $priorities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pri): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($pri->id); ?>" <?php echo e(old('ticket_priority_id') == $pri->id ? 'selected' : ''); ?>>
                            <?php echo e($pri->name); ?> (SLA: <?php echo e($pri->resolution_hours); ?>h)
                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['ticket_priority_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <span class="form-error"><?php echo e($message); ?></span>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="ticket_desc">Describe the Issue</label>
                <textarea class="form-textarea" id="ticket_desc" name="description" rows="4" required placeholder="Please provide detailed replication steps or error logs..."><?php echo e(old('description')); ?></textarea>
                <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <span class="form-error"><?php echo e($message); ?></span>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="ticket_files">Attachments (Max 5 files, 10MB each)</label>
                <input class="form-input" type="file" id="ticket_files" name="attachments[]" multiple style="padding: 0.5rem 0.75rem;">
                <?php $__errorArgs = ['attachments'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <span class="form-error"><?php echo e($message); ?></span>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                <?php $__errorArgs = ['attachments.*'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <span class="form-error"><?php echo e($message); ?></span>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                <i class="fa-solid fa-paper-plane"></i> Submit Ticket
            </button>
        </form>
    </div>

    <!-- Right Content: Ticket Listings & Notifications -->
    <div>
        <!-- In-app Notifications -->
        <?php if($notifications->isNotEmpty()): ?>
            <div class="main-panel" style="margin-bottom: 2rem; padding: 1.25rem;">
                <h4 style="margin-bottom: 1rem;"><i class="fa-solid fa-bell" style="color: var(--accent-purple);"></i> Unread Notifications</h4>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <?php $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notif): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div style="background: rgba(255,255,255,0.03); border: 1px dashed var(--glass-border); padding: 0.75rem 1rem; border-radius: 8px; display: flex; align-items: center; justify-content: space-between;">
                            <span style="font-size: 0.9rem;">
                                <i class="fa-solid fa-circle-info" style="color: var(--accent-indigo); margin-right: 0.5rem;"></i>
                                <?php echo e($notif->data['message'] ?? 'Notification updated'); ?>

                            </span>
                            <form action="<?php echo e(route('notifications.read', $notif->id)); ?>" method="POST" style="display:inline;">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                    Mark Read
                                </button>
                            </form>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="main-panel">
            <h3 style="margin-bottom: 1.5rem;"><i class="fa-solid fa-list-check"></i> My Support Requests</h3>
            
            <?php if($tickets->isEmpty()): ?>
                <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                    <i class="fa-solid fa-folder-open" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                    <p>You have not raised any support tickets yet.</p>
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
                                        <i class="fa-solid fa-building"></i> <?php echo e($ticket->department->name); ?>

                                    </div>
                                    <div class="meta-item">
                                        <i class="fa-solid fa-calendar"></i> Raised <?php echo e($ticket->created_at->diffForHumans()); ?>

                                    </div>
                                    
                                    <?php if($ticket->estimated_resolution_at && !$ticket->isClosed() && !$ticket->isResolved()): ?>
                                        <div class="meta-item">
                                            <?php if($ticket->isSlaBreached()): ?>
                                                <span class="badge badge-sla-breached">SLA BREACHED</span>
                                            <?php else: ?>
                                                <i class="fa-solid fa-clock"></i> SLA: <?php echo e($ticket->estimated_resolution_at->format('M d, H:i')); ?>

                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <a href="<?php echo e(route('tickets.show', $ticket->id)); ?>" class="btn btn-secondary btn-sm">
                                    View Thread <i class="fa-solid fa-chevron-right"></i>
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

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Alpha4\ticketMonitoring\resources\views/dashboard/customer_dashboard.blade.php ENDPATH**/ ?>