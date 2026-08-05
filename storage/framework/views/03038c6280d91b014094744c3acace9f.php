<?php $__env->startSection('title', 'Ticket #' . $ticket->id . ' - SupportSphere'); ?>

<?php $__env->startSection('content'); ?>
<div style="margin-bottom: 1.5rem;">
    <?php if(Auth::guard('employee')->check()): ?>
        <a href="<?php echo e(route('employee.dashboard')); ?>" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Back to Queue
        </a>
    <?php else: ?>
        <a href="<?php echo e(route('customer.dashboard')); ?>" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
    <?php endif; ?>
</div>

<div class="detail-grid">
    
    <!-- Left Panel: Ticket Detail & Comments Thread -->
    <div>
        <div class="main-panel">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
                <div>
                    <span style="color: var(--text-muted); font-size: 0.9rem; font-weight: 600;">TICKET #<?php echo e($ticket->id); ?></span>
                    <h2><?php echo e($ticket->title); ?></h2>
                </div>
                <div>
                    <?php if($ticket->isSlaBreached()): ?>
                        <span class="badge badge-sla-breached">SLA BREACHED</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="ticket-body-desc">
                <?php echo e($ticket->description); ?>

            </div>

            <!-- Ticket Attachments -->
            <?php if($ticket->attachments->whereNull('ticket_comment_id')->isNotEmpty()): ?>
                <div style="margin-bottom: 2rem;">
                    <h5 style="color: var(--text-secondary); margin-bottom: 0.5rem;">Original Attachments:</h5>
                    <div class="comment-attachments">
                        <?php $__currentLoopData = $ticket->attachments->whereNull('ticket_comment_id'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $att): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <a href="<?php echo e(asset('storage/' . $att->file_path)); ?>" target="_blank" class="attachment-chip">
                                <i class="fa-solid fa-file-invoice"></i> <?php echo e($att->file_name); ?> 
                                <span style="color: var(--text-muted); font-size: 0.75rem;">(<?php echo e(round($att->file_size / 1024, 1)); ?> KB)</span>
                            </a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Comments/Conversation Thread -->
            <div class="comment-section">
                <h3>Conversation History</h3>
                <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 1.5rem;">Replies between customer and support agents.</p>

                <div class="comment-list">
                    <?php $__currentLoopData = $ticket->comments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $comment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $isCustomer = $comment->commenter_type === 'App\Models\Customer';
                        ?>
                        <div class="comment-bubble <?php echo e($isCustomer ? 'customer-reply' : 'employee-reply'); ?>">
                            <div class="comment-header">
                                <span>
                                    <i class="fa-solid <?php echo e($isCustomer ? 'fa-user' : 'fa-user-tie'); ?>"></i> 
                                    <?php echo e($comment->commenter ? $comment->commenter->name : 'Deleted User'); ?>

                                    <span style="font-size: 0.75rem; color: var(--text-muted);">
                                        (<?php echo e($isCustomer ? 'Customer' : ($comment->commenter && $comment->commenter->role ? $comment->commenter->role->name : 'Staff')); ?>)
                                    </span>
                                </span>
                                <span><?php echo e($comment->created_at->format('M d, Y H:i')); ?></span>
                            </div>
                            <div class="comment-text">
                                <?php echo e($comment->comment); ?>

                            </div>
                            
                            <?php if($comment->attachments->isNotEmpty()): ?>
                                <div class="comment-attachments">
                                    <?php $__currentLoopData = $comment->attachments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $att): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <a href="<?php echo e(asset('storage/' . $att->file_path)); ?>" target="_blank" class="attachment-chip">
                                            <i class="fa-solid fa-paperclip"></i> <?php echo e($att->file_name); ?>

                                            <span style="color: var(--text-muted); font-size: 0.75rem;">(<?php echo e(round($att->file_size / 1024, 1)); ?> KB)</span>
                                        </a>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>

                <!-- Add Comment Form -->
                <?php if(!$ticket->isClosed() || Auth::guard('employee')->check()): ?>
                    <form action="<?php echo e(route('tickets.comment.store', $ticket->id)); ?>" method="POST" enctype="multipart/form-data" style="margin-top: 2rem;">
                        <?php echo csrf_field(); ?>
                        <div class="form-group">
                            <label class="form-label" for="reply_comment">Post a Reply</label>
                            <textarea class="form-textarea" id="reply_comment" name="comment" rows="4" required placeholder="Type your response here... <?php if($ticket->isClosed() || $ticket->isResolved()): ?> (Replying will automatically Reopen the ticket) <?php endif; ?>"></textarea>
                            <?php $__errorArgs = ['comment'];
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
                            <label class="form-label" for="reply_files">Upload Attachments (Max 5 files)</label>
                            <input class="form-input" type="file" id="reply_files" name="attachments[]" multiple style="padding: 0.5rem 0.75rem;">
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
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-reply"></i> Submit Reply
                        </button>
                    </form>
                <?php else: ?>
                    <div style="background: rgba(255,255,255,0.02); padding: 1.5rem; text-align: center; border-radius: 8px; border: 1px dashed var(--glass-border); color: var(--text-secondary);">
                        <i class="fa-solid fa-lock" style="font-size: 1.5rem; margin-bottom: 0.5rem;"></i>
                        <p>This ticket is closed. If you require further assistance, please raise a new support request.</p>
                    </div>
                <?php endif; ?>
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
                    <span class="badge badge-status-<?php echo e($ticket->status->slug); ?>"><?php echo e($ticket->status->name); ?></span>
                </div>
                
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--text-secondary);">Priority:</span>
                    <span class="badge badge-priority-<?php echo e($ticket->priority->slug); ?>"><?php echo e($ticket->priority->name); ?></span>
                </div>
                
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--text-secondary);">Department:</span>
                    <span style="font-weight: 500;"><?php echo e($ticket->department->name); ?></span>
                </div>

                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--text-secondary);">Customer:</span>
                    <span style="font-weight: 500;"><?php echo e($ticket->customer->name); ?></span>
                </div>

                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--text-secondary);">Assigned To:</span>
                    <span style="font-weight: 500; color: var(--accent-indigo);">
                        <?php echo e($ticket->assignedTo ? $ticket->assignedTo->name : 'Unassigned'); ?>

                    </span>
                </div>

                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--text-secondary);">Raised:</span>
                    <span><?php echo e($ticket->created_at->format('M d, Y H:i')); ?></span>
                </div>

                <?php if($ticket->estimated_resolution_at): ?>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-secondary);">SLA Target:</span>
                        <span style="font-weight: 500; color: <?php echo e($ticket->isSlaBreached() ? '#ef4444' : 'inherit'); ?>">
                            <?php echo e($ticket->estimated_resolution_at->format('M d, Y H:i')); ?>

                        </span>
                    </div>
                <?php endif; ?>

                <?php if($ticket->actual_resolution_at): ?>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-secondary);">Resolved At:</span>
                        <span style="font-weight: 500; color: var(--status-resolved);">
                            <?php echo e($ticket->actual_resolution_at->format('M d, Y H:i')); ?>

                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php
            $currentUser = Auth::guard('employee')->user() ?: Auth::guard('customer')->user();
        ?>

        <!-- Update Status Actions (For authorized employees or customer closing) -->
        <?php if(Gate::forUser($currentUser)->allows('update', $ticket)): ?>
            <div class="sidebar-panel" style="margin-bottom: 2rem;">
                <h3>Update Status</h3>
                <hr style="border-color: var(--glass-border); margin: 0.75rem 0;">
                
                <?php if(Auth::guard('employee')->check()): ?>
                    <form action="<?php echo e(route('tickets.status.update', $ticket->id)); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="form-group">
                            <select class="form-select" name="ticket_status_id" required onchange="this.form.submit()">
                                <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $st): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($st->id); ?>" <?php echo e($ticket->ticket_status_id == $st->id ? 'selected' : ''); ?>>
                                        Move to: <?php echo e($st->name); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                    </form>
                <?php else: ?>
                    <?php
                        $closedStatus = $statuses->where('slug', 'closed')->first();
                    ?>
                    <?php if($closedStatus && !$ticket->isClosed()): ?>
                        <form action="<?php echo e(route('tickets.status.update', $ticket->id)); ?>" method="POST">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="ticket_status_id" value="<?php echo e($closedStatus->id); ?>">
                            <button type="submit" class="btn btn-danger btn-block btn-sm">
                                <i class="fa-solid fa-circle-xmark"></i> Close Ticket
                            </button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if(Auth::guard('employee')->check()): ?>
            <!-- Manual Assignment Actions (Admin/TL only) -->
            <?php if(Gate::forUser($currentUser)->allows('assign', $ticket)): ?>
                <div class="sidebar-panel" style="margin-bottom: 2rem;">
                    <h3>Manual Assignment</h3>
                    <hr style="border-color: var(--glass-border); margin: 0.75rem 0;">
                    
                    <form action="<?php echo e(route('tickets.assign.submit', $ticket->id)); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="form-group">
                            <label class="form-label" for="manual_agent">Select Staff</label>
                            <select class="form-select" id="manual_agent" name="employee_id" required>
                                <option value="" disabled selected>Select agent</option>
                                <?php $__currentLoopData = $eligibleEmployees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $eligible): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($eligible->id); ?>" <?php echo e($ticket->assigned_to == $eligible->id ? 'selected' : ''); ?>>
                                        <?php echo e($eligible->name); ?> (<?php echo e($eligible->is_available ? 'Available' : 'Unavailable'); ?>)
                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['employee_id'];
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
                        <button type="submit" class="btn btn-secondary btn-sm btn-block">
                            <i class="fa-solid fa-user-tag"></i> Assign Agent
                        </button>
                    </form>
                </div>

                <!-- Extend SLA Form -->
                <div class="sidebar-panel" style="margin-bottom: 2rem;">
                    <h3>Extend SLA Deadline</h3>
                    <hr style="border-color: var(--glass-border); margin: 0.75rem 0;">
                    
                    <form action="<?php echo e(route('tickets.sla.extend', $ticket->id)); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="form-group">
                            <label class="form-label" for="sla_date">New Due Date</label>
                            <input class="form-input" type="datetime-local" id="sla_date" name="estimated_resolution_at" required 
                                   value="<?php echo e($ticket->estimated_resolution_at ? $ticket->estimated_resolution_at->format('Y-m-d\TH:i') : ''); ?>">
                            <?php $__errorArgs = ['estimated_resolution_at'];
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
                        <button type="submit" class="btn btn-secondary btn-sm btn-block">
                            <i class="fa-solid fa-clock-rotate-left"></i> Save Extension
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Audit Trail / Ticket Logs -->
        <div class="sidebar-panel">
            <h3>Audit Trail</h3>
            <hr style="border-color: var(--glass-border); margin: 1rem 0;">
            
            <div class="timeline">
                <?php $__currentLoopData = $ticket->logs->sortByDesc('created_at'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="timeline-item action-<?php echo e($log->action); ?>">
                        <div class="timeline-date"><?php echo e($log->created_at->format('M d, Y H:i')); ?></div>
                        <div class="timeline-text">
                            <strong><?php echo e($log->action === 'created' ? 'Ticket Raised' : ($log->action === 'assigned' ? 'Agent Assigned' : ($log->action === 'reassigned' ? 'Agent Reassigned' : ($log->action === 'status_changed' ? 'Status Updated' : ($log->action === 'sla_updated' ? 'SLA Extended' : ucfirst($log->action)))))); ?></strong>
                        </div>
                        <div class="timeline-details">
                            <?php if($log->action === 'status_changed' && isset($log->details['new_status'])): ?>
                                Moved to status "<?php echo e($log->details['new_status']); ?>"
                            <?php elseif($log->action === 'assigned' || $log->action === 'reassigned'): ?>
                                Assigned to <?php echo e($log->details['assigned_to_name'] ?? 'Agent'); ?> (<?php echo e($log->details['method'] ?? 'manual'); ?>).
                            <?php elseif($log->action === 'sla_updated'): ?>
                                Extended to <?php echo e(Carbon\Carbon::parse($log->details['new_sla'])->format('M d, H:i')); ?>

                            <?php elseif($log->action === 'comment_added'): ?>
                                Reply added.
                            <?php elseif(is_array($log->details) && isset($log->details['message'])): ?>
                                <?php echo e($log->details['message']); ?>

                            <?php elseif(is_string($log->details)): ?>
                                <?php echo e($log->details); ?>

                            <?php endif; ?>
                            <br>
                            <span style="color: var(--text-muted); font-size: 0.75rem;">
                                By: <?php echo e($log->performedBy ? $log->performedBy->name : 'System'); ?>

                            </span>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Alpha4\ticketMonitoring\resources\views/dashboard/ticket_show.blade.php ENDPATH**/ ?>