<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\Role;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('slug', 'admin')->first();
        $tlRole = Role::where('slug', 'team_lead')->first();
        $staffRole = Role::where('slug', 'staff')->first();

        $techDept = Department::where('name', 'Technical Support')->first();
        $billingDept = Department::where('name', 'Billing & Accounts')->first();
        $salesDept = Department::where('name', 'Sales & Inquiries')->first();
        $successDept = Department::where('name', 'Customer Success')->first();

        // 1. Admin (no department)
        Employee::firstOrCreate(
            ['email' => 'admin@system.com'],
            [
                'role_id' => $adminRole->id,
                'department_id' => null,
                'name' => 'System Admin',
                'password' => Hash::make('password'),
                'is_available' => true,
                'api_token' => 'token_admin_123',
            ]
        );

        // 2. Technical Support Team Lead
        Employee::firstOrCreate(
            ['email' => 'techlead@system.com'],
            [
                'role_id' => $tlRole->id,
                'department_id' => $techDept->id,
                'name' => 'Tech Support Lead',
                'password' => Hash::make('password'),
                'is_available' => true,
                'api_token' => 'token_techlead_123',
            ]
        );

        // 3. Technical Support Staff 1
        Employee::firstOrCreate(
            ['email' => 'staff1@system.com'],
            [
                'role_id' => $staffRole->id,
                'department_id' => $techDept->id,
                'name' => 'John Doe (Tech)',
                'password' => Hash::make('password'),
                'is_available' => true,
                'api_token' => 'token_staff1_123',
            ]
        );

        // 4. Technical Support Staff 2
        Employee::firstOrCreate(
            ['email' => 'staff2@system.com'],
            [
                'role_id' => $staffRole->id,
                'department_id' => $techDept->id,
                'name' => 'Jane Smith (Tech)',
                'password' => Hash::make('password'),
                'is_available' => true,
                'api_token' => 'token_staff2_123',
            ]
        );

        // 5. Billing Support Staff
        Employee::firstOrCreate(
            ['email' => 'staff3@system.com'],
            [
                'role_id' => $staffRole->id,
                'department_id' => $billingDept->id,
                'name' => 'Alice Brown (Billing)',
                'password' => Hash::make('password'),
                'is_available' => true,
                'api_token' => 'token_staff3_123',
            ]
        );

        // 6. Sales Staff
        Employee::firstOrCreate(
            ['email' => 'staff4@system.com'],
            [
                'role_id' => $staffRole->id,
                'department_id' => $salesDept->id,
                'name' => 'Bob Davis (Sales)',
                'password' => Hash::make('password'),
                'is_available' => true,
                'api_token' => 'token_staff4_123',
            ]
        );

        // 7. Customer Success Staff (unavailble to test routing)
        Employee::firstOrCreate(
            ['email' => 'staff5@system.com'],
            [
                'role_id' => $staffRole->id,
                'department_id' => $successDept->id,
                'name' => 'Charlie Wilson (Success)',
                'password' => Hash::make('password'),
                'is_available' => false, // unavailable!
                'api_token' => 'token_staff5_123',
            ]
        );
    }
}
