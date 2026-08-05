<?php $__env->startSection('title', 'Portal Login - SupportSphere'); ?>

<?php $__env->startSection('content'); ?>
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-logo">
            <i class="fa-solid fa-ticket-simple"></i> SupportSphere
        </div>
        
        <div class="auth-tabs">
            <div class="auth-tab active" id="customerTab" onclick="switchTab('customer')">Customer</div>
            <div class="auth-tab" id="employeeTab" onclick="switchTab('employee')">Staff Portal</div>
        </div>

        <!-- Customer Login Form -->
        <form id="customerForm" action="<?php echo e(route('customer.login.submit')); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <h3>Customer Login</h3>
            <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 1.5rem;">Access your raised tickets and replies.</p>
            
            <div class="form-group">
                <label class="form-label" for="cust_email">Email Address</label>
                <input class="form-input" type="email" id="cust_email" name="email" value="<?php echo e(old('email')); ?>" required placeholder="name@company.com">
                <?php $__errorArgs = ['email'];
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
                <label class="form-label" for="cust_password">Password</label>
                <input class="form-input" type="password" id="cust_password" name="password" required placeholder="••••••••">
                <?php $__errorArgs = ['password'];
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
                <i class="fa-solid fa-right-to-bracket"></i> Login as Customer
            </button>

            <div style="text-align: center; margin-top: 1.5rem; font-size: 0.875rem;">
                <span style="color: var(--text-secondary);">New here?</span>
                <a href="<?php echo e(route('register')); ?>">Create an Account</a>
            </div>
        </form>

        <!-- Employee Login Form -->
        <form id="employeeForm" action="<?php echo e(route('employee.login.submit')); ?>" method="POST" style="display: none;">
            <?php echo csrf_field(); ?>
            <h3>Employee Sign In</h3>
            <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 1.5rem;">Staff, Team Leads, and Admins login here.</p>
            
            <div class="form-group">
                <label class="form-label" for="emp_email">Work Email</label>
                <input class="form-input" type="email" id="emp_email" name="email" value="<?php echo e(old('email')); ?>" required placeholder="username@system.com">
                <?php $__errorArgs = ['email'];
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
                <label class="form-label" for="emp_password">Password</label>
                <input class="form-input" type="password" id="emp_password" name="password" required placeholder="••••••••">
                <?php $__errorArgs = ['password'];
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
                <i class="fa-solid fa-shield-halved"></i> Sign In to Staff Workspace
            </button>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
    function switchTab(type) {
        const customerTab = document.getElementById('customerTab');
        const employeeTab = document.getElementById('employeeTab');
        const customerForm = document.getElementById('customerForm');
        const employeeForm = document.getElementById('employeeForm');

        if (type === 'customer') {
            customerTab.classList.add('active');
            employeeTab.classList.remove('active');
            customerForm.style.display = 'block';
            employeeForm.style.display = 'none';
        } else {
            employeeTab.classList.add('active');
            customerTab.classList.remove('active');
            employeeForm.style.display = 'block';
            customerForm.style.display = 'none';
        }
    }

    // Retain active tab on validation fail redirect
    <?php if(old('form_type') === 'employee' || request()->query('portal') === 'employee'): ?>
        switchTab('employee');
    <?php endif; ?>
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Alpha4\ticketMonitoring\resources\views/auth/login.blade.php ENDPATH**/ ?>