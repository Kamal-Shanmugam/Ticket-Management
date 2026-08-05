<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\ApiTicketController;
use App\Http\Controllers\Api\ApiCommentController;
use App\Http\Controllers\Api\ApiDepartmentController;
use App\Http\Controllers\Api\ApiEmployeeController;
use App\Http\Controllers\Api\ApiCustomerController;
use App\Http\Controllers\Api\ApiNotificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Guest / Authentication routes
Route::post('/auth/customer/register', [ApiAuthController::class, 'customerRegister']);
Route::post('/auth/customer/login', [ApiAuthController::class, 'customerLogin']);
Route::post('/auth/employee/login', [ApiAuthController::class, 'employeeLogin']);

// Customer Protected Routes
Route::middleware(['auth.customer_api'])->group(function () {
    Route::post('/auth/customer/logout', [ApiAuthController::class, 'customerLogout']);
    
    Route::get('/customer/tickets', [ApiTicketController::class, 'index']);
    Route::post('/customer/tickets', [ApiTicketController::class, 'store']);
    Route::get('/customer/tickets/{id}', [ApiTicketController::class, 'show']);
    Route::post('/customer/tickets/{id}/comments', [ApiCommentController::class, 'store']);
});

// Employee Protected Routes
Route::middleware(['auth.employee_api'])->group(function () {
    Route::post('/auth/employee/logout', [ApiAuthController::class, 'employeeLogout']);
    
    Route::get('/employee/tickets', [ApiTicketController::class, 'index']);
    Route::get('/employee/tickets/{id}', [ApiTicketController::class, 'show']);
    Route::post('/employee/tickets/{id}/comments', [ApiCommentController::class, 'store']);
    Route::post('/employee/tickets/{id}/status', [ApiTicketController::class, 'updateStatus']);
    Route::post('/employee/tickets/{id}/assign', [ApiTicketController::class, 'assign']);
    Route::post('/employee/tickets/{id}/sla', [ApiTicketController::class, 'extendSLA']);
    
    Route::get('/employees', [ApiEmployeeController::class, 'index']);
    Route::get('/employees/{id}', [ApiEmployeeController::class, 'show']);
    Route::put('/employees/{id}', [ApiEmployeeController::class, 'update']);
    
    Route::get('/customers', [ApiCustomerController::class, 'index']);
    Route::get('/customers/{id}', [ApiCustomerController::class, 'show']);
});

// Shared Protected Routes (Accessible to either Customer or Employee API tokens)
Route::middleware(['auth.any_api'])->group(function () {
    Route::get('/departments', [ApiDepartmentController::class, 'index']);
    Route::get('/departments/{id}', [ApiDepartmentController::class, 'show']);
    
    Route::get('/notifications', [ApiNotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [ApiNotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [ApiNotificationController::class, 'markAllAsRead']);
});
