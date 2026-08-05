<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterCustomerRequest;
use App\Http\Requests\LoginRequest;
use App\Models\Customer;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApiAuthController extends Controller
{
    use HandlesApiResponses;

    /**
     * Register a new customer via API.
     */
    public function customerRegister(RegisterCustomerRequest $request)
    {
        $customer = Customer::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'api_token' => Str::random(60),
        ]);

        return $this->successResponse([
            'token' => $customer->api_token,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
            ]
        ], 'Customer registered successfully.', 211); // 201 Created
    }

    /**
     * Log in a customer and generate an API token.
     */
    public function customerLogin(LoginRequest $request)
    {
        $customer = Customer::where('email', $request->email)->first();

        if (!$customer || !Hash::check($request->password, $customer->password)) {
            return $this->errorResponse('Invalid credentials.', [], 401);
        }

        // Generate token if null or refresh
        $customer->api_token = Str::random(60);
        $customer->save();

        return $this->successResponse([
            'token' => $customer->api_token,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
            ]
        ], 'Customer logged in successfully.');
    }

    /**
     * Log out the customer by invalidating their current API token.
     */
    public function customerLogout(Request $request)
    {
        $customer = $request->user(); // obtained from auth middleware
        if ($customer && $customer instanceof Customer) {
            $customer->api_token = null;
            $customer->save();
        }

        return $this->successResponse(null, 'Customer logged out successfully.');
    }

    /**
     * Log in an employee and generate an API token.
     */
    public function employeeLogin(LoginRequest $request)
    {
        $employee = Employee::where('email', $request->email)->first();

        if (!$employee || !Hash::check($request->password, $employee->password)) {
            return $this->errorResponse('Invalid credentials.', [], 401);
        }

        // Generate token if null or refresh
        $employee->api_token = Str::random(60);
        $employee->save();

        return $this->successResponse([
            'token' => $employee->api_token,
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
                'role' => $employee->role ? $employee->role->slug : null,
                'department' => $employee->department ? $employee->department->name : null,
            ]
        ], 'Employee logged in successfully.');
    }

    /**
     * Log out the employee by invalidating their current API token.
     */
    public function employeeLogout(Request $request)
    {
        $employee = $request->user(); // obtained from auth middleware
        if ($employee && $employee instanceof Employee) {
            $employee->api_token = null;
            $employee->save();
        }

        return $this->successResponse(null, 'Employee logged out successfully.');
    }
}
