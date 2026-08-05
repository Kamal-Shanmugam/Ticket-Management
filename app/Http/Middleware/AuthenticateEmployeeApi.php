<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;

class AuthenticateEmployeeApi
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

        $employee = Employee::where('api_token', $token)->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated: Invalid employee bearer token.',
                'errors' => new \stdClass(),
            ], 401);
        }

        // Set the authenticated user for the employee guard
        Auth::guard('employee')->setUser($employee);
        
        // Also share as default auth user
        Auth::setUser($employee);

        return $next($request);
    }
}
