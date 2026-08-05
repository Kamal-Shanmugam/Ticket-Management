<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckEmployeeRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $employee = Auth::guard('employee')->user();

        if (!$employee) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                    'errors' => new \stdClass(),
                ], 401);
            }
            return redirect()->route('employee.login')->with('error', 'Please log in first.');
        }

        if (!$employee->role || !in_array($employee->role->slug, $roles)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden: You do not have the required role privileges.',
                    'errors' => new \stdClass(),
                ], 403);
            }
            abort(403, 'Forbidden: You do not have permission to access this section.');
        }

        return $next($request);
    }
}
