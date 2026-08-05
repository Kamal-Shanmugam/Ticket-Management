<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\EmployeeAuthController;
use App\Http\Controllers\CustomerDashboardController;
use App\Http\Controllers\EmployeeDashboardController;
use App\Http\Controllers\WebTicketController;
use Illuminate\Support\Facades\Auth;

// 1. Root & Guest portal routing
Route::get('/', function () {
    if (Auth::guard('employee')->check()) {
        return redirect()->route('employee.dashboard');
    }
    if (Auth::guard('customer')->check()) {
        return redirect()->route('customer.dashboard');
    }
    return redirect()->route('login');
});

Route::get('/login', [CustomerAuthController::class, 'showLogin'])->name('login');
Route::post('/login/customer', [CustomerAuthController::class, 'login'])->name('customer.login.submit');
Route::post('/login/employee', [EmployeeAuthController::class, 'login'])->name('employee.login.submit');

Route::get('/register', [CustomerAuthController::class, 'showRegister'])->name('register');
Route::post('/register', [CustomerAuthController::class, 'register'])->name('customer.register.submit');

// 2. Logouts
Route::post('/logout/customer', [CustomerAuthController::class, 'logout'])->name('customer.logout');
Route::post('/logout/employee', [EmployeeAuthController::class, 'logout'])->name('employee.logout');

// 3. Customer Portal
Route::middleware(['auth:customer'])->group(function () {
    Route::get('/customer/dashboard', [CustomerDashboardController::class, 'index'])->name('customer.dashboard');
    Route::post('/customer/tickets', [WebTicketController::class, 'store'])->name('customer.ticket.store');
});

// 4. Employee Portal
Route::middleware(['auth:employee'])->group(function () {
    Route::get('/employee/dashboard', [EmployeeDashboardController::class, 'index'])->name('employee.dashboard');
    Route::post('/employee/availability', [EmployeeDashboardController::class, 'toggleAvailability'])->name('employee.availability');
});

// 5. Shared Authenticated Ticket & Notification Operations
Route::middleware([])->group(function () {
    // We let the Controller/Policies handle specific authorization, but require at least one guard session to exist
    Route::get('/tickets/{id}', [WebTicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{id}/comments', [WebTicketController::class, 'storeComment'])->name('tickets.comment.store');
    Route::post('/tickets/{id}/status', [WebTicketController::class, 'updateStatus'])->name('tickets.status.update');
    Route::post('/tickets/{id}/assign', [WebTicketController::class, 'assign'])->name('tickets.assign.submit');
    Route::post('/tickets/{id}/sla', [WebTicketController::class, 'extendSLA'])->name('tickets.sla.extend');
    
    Route::post('/notifications/{id}/read', [WebTicketController::class, 'readNotification'])->name('notifications.read');
});

