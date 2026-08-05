<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Customer;
use App\Models\Employee;

class AuthenticateAnyApi
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated: Bearer token is missing.',
                'errors' => new \stdClass(),
            ], 401);
        }

        // Try customer first
        $customer = Customer::where('api_token', $token)->first();
        if ($customer) {
            Auth::guard('customer')->setUser($customer);
            Auth::setUser($customer);
            return $next($request);
        }

        // Try employee second
        $employee = Employee::where('api_token', $token)->first();
        if ($employee) {
            Auth::guard('employee')->setUser($employee);
            Auth::setUser($employee);
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated: Invalid API bearer token.',
            'errors' => new \stdClass(),
        ], 401);
    }
}
