@title('Customer Registration - SupportSphere')

<?php $__env->startSection('content'); ?>
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-logo">
            <i class="fa-solid fa-ticket-simple"></i> SupportSphere
        </div>
        
        <form action="<?php echo e(route('customer.register.submit')); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <h3>Create Customer Account</h3>
            <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 1.5rem;">Register to start raising support requests.</p>
            
            <div class="form-group">
                <label class="form-label" for="reg_name">Company / Contact Name</label>
                <input class="form-input" type="text" id="reg_name" name="name" value="<?php echo e(old('name')); ?>" required placeholder="Acme Corp or John Doe">
                <?php $__errorArgs = ['name'];
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
                <label class="form-label" for="reg_email">Email Address</label>
                <input class="form-input" type="email" id="reg_email" name="email" value="<?php echo e(old('email')); ?>" required placeholder="name@company.com">
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
                <label class="form-label" for="reg_password">Password</label>
                <input class="form-input" type="password" id="reg_password" name="password" required placeholder="••••••••">
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

            <div class="form-group">
                <label class="form-label" for="reg_password_confirmation">Confirm Password</label>
                <input class="form-input" type="password" id="reg_password_confirmation" name="password_confirmation" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                <i class="fa-solid fa-user-plus"></i> Sign Up
            </button>

            <div style="text-align: center; margin-top: 1.5rem; font-size: 0.875rem;">
                <span style="color: var(--text-secondary);">Already registered?</span>
                <a href="<?php echo e(route('login')); ?>">Sign In</a>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Alpha4\ticketMonitoring\resources\views/auth/register.blade.php ENDPATH**/ ?>