<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'Ticket Management System'); ?></title>
    <link rel="stylesheet" href="<?php echo e(asset('css/styles.css')); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <header class="app-header">
        <div class="header-brand">
            <i class="fa-solid fa-ticket-simple"></i> SupportSphere
        </div>
        
        <div class="header-nav">
            <?php if(Auth::guard('employee')->check()): ?>
                <?php $emp = Auth::guard('employee')->user(); ?>
                <span class="header-user-info">
                    <i class="fa-solid fa-user-tie"></i> <?php echo e($emp->name); ?> 
                    <span class="badge" style="background-color: var(--bg-tertiary);"><?php echo e($emp->role->name); ?></span>
                    <?php if($emp->department): ?>
                        <span class="badge" style="background-color: var(--bg-primary);"><?php echo e($emp->department->name); ?></span>
                    <?php endif; ?>
                </span>
                
                <a href="<?php echo e(route('employee.dashboard')); ?>" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-chart-line"></i> Dashboard
                </a>
                
                <form action="<?php echo e(route('employee.logout')); ?>" method="POST" style="display: inline;">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </button>
                </form>
            <?php elseif(Auth::guard('customer')->check()): ?>
                <?php $cust = Auth::guard('customer')->user(); ?>
                <span class="header-user-info">
                    <i class="fa-solid fa-user"></i> <?php echo e($cust->name); ?> (Customer)
                </span>
                
                <a href="<?php echo e(route('customer.dashboard')); ?>" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-home"></i> Home
                </a>
                
                <form action="<?php echo e(route('customer.logout')); ?>" method="POST" style="display: inline;">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </button>
                </form>
            <?php else: ?>
                <a href="<?php echo e(route('login')); ?>" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-right-to-bracket"></i> Login Portal
                </a>
            <?php endif; ?>
        </div>
    </header>

    <main class="container">
        <?php if(session('success')): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i> <?php echo e(session('success')); ?>

            </div>
        <?php endif; ?>

        <?php if(session('error')): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo e(session('error')); ?>

            </div>
        <?php endif; ?>

        <?php echo $__env->yieldContent('content'); ?>
    </main>

    <?php echo $__env->yieldContent('scripts'); ?>
</body>
</html>
<?php /**PATH D:\Alpha4\ticketMonitoring\resources\views/layouts/app.blade.php ENDPATH**/ ?>