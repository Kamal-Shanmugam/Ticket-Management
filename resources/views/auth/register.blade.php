@extends('layouts.app')

@section('title', 'Customer Registration - SupportSphere')

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-logo">
            <i class="fa-solid fa-ticket-simple"></i> SupportSphere
        </div>
        
        <form action="{{ route('customer.register.submit') }}" method="POST">
            @csrf
            <h3>Create Customer Account</h3>
            <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 1.5rem;">Register to start raising support requests.</p>
            
            <div class="form-group">
                <label class="form-label" for="reg_name">Company / Contact Name</label>
                <input class="form-input" type="text" id="reg_name" name="name" value="{{ old('name') }}" required placeholder="Acme Corp or John Doe">
                @error('name')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="reg_email">Email Address</label>
                <input class="form-input" type="email" id="reg_email" name="email" value="{{ old('email') }}" required placeholder="name@company.com">
                @error('email')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="reg_password">Password</label>
                <input class="form-input" type="password" id="reg_password" name="password" required placeholder="••••••••">
                @error('password')
                    <span class="form-error">{{ $message }}</span>
                @enderror
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
                <a href="{{ route('login') }}">Sign In</a>
            </div>
        </form>
    </div>
</div>
@endsection
