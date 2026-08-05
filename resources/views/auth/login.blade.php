@extends('layouts.app')

@section('title', 'Portal Login - SupportSphere')

@section('content')
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
        <form id="customerForm" action="{{ route('customer.login.submit') }}" method="POST">
            @csrf
            <h3>Customer Login</h3>
            <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 1.5rem;">Access your raised tickets and replies.</p>
            
            <div class="form-group">
                <label class="form-label" for="cust_email">Email Address</label>
                <input class="form-input" type="email" id="cust_email" name="email" value="{{ old('email') }}" required placeholder="name@company.com">
                @error('email')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="cust_password">Password</label>
                <input class="form-input" type="password" id="cust_password" name="password" required placeholder="••••••••">
                @error('password')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                <i class="fa-solid fa-right-to-bracket"></i> Login as Customer
            </button>

            <div style="text-align: center; margin-top: 1.5rem; font-size: 0.875rem;">
                <span style="color: var(--text-secondary);">New here?</span>
                <a href="{{ route('register') }}">Create an Account</a>
            </div>
        </form>

        <!-- Employee Login Form -->
        <form id="employeeForm" action="{{ route('employee.login.submit') }}" method="POST" style="display: none;">
            @csrf
            <h3>Employee Sign In</h3>
            <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 1.5rem;">Staff, Team Leads, and Admins login here.</p>
            
            <div class="form-group">
                <label class="form-label" for="emp_email">Work Email</label>
                <input class="form-input" type="email" id="emp_email" name="email" value="{{ old('email') }}" required placeholder="username@system.com">
                @error('email')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="emp_password">Password</label>
                <input class="form-input" type="password" id="emp_password" name="password" required placeholder="••••••••">
                @error('password')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                <i class="fa-solid fa-shield-halved"></i> Sign In to Staff Workspace
            </button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
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
    @if(old('form_type') === 'employee' || request()->query('portal') === 'employee')
        switchTab('employee');
    @endif
</script>
@endsection
