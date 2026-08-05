<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class EmployeeAuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('employee')->attempt($credentials)) {
            $request->session()->regenerate();

            // Set token if missing
            $employee = Auth::guard('employee')->user();
            if (!$employee->api_token) {
                $employee->api_token = Str::random(60);
                $employee->save();
            }

            return redirect()->route('employee.dashboard')->with('success', 'Workspace session loaded successfully!');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our staff records.',
        ])->onlyInput('email')->withInput(['form_type' => 'employee']);
    }

    public function logout(Request $request)
    {
        Auth::guard('employee')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login', ['portal' => 'employee'])->with('success', 'Workspace session closed.');
    }
}
