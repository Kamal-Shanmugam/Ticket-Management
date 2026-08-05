<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Customer;

class AuthenticateCustomerApi
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

        $customer = Customer::where('api_token', $token)->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated: Invalid customer bearer token.',
                'errors' => new \stdClass(),
            ], 401);
        }

        // Set the authenticated user for the customer guard
        Auth::guard('customer')->setUser($customer);
        
        // Also share as default auth user
        Auth::setUser($customer);

        return $next($request);
    }
}
