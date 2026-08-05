<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Technical Support', 'description' => 'Handles code errors, website bugs, and developer assistance.'],
            ['name' => 'Billing & Accounts', 'description' => 'Handles invoices, refunds, subscription plans, and accounting queries.'],
            ['name' => 'Sales & Inquiries', 'description' => 'Handles new customer queries, quote requests, and pricing calls.'],
            ['name' => 'Customer Success', 'description' => 'Handles onboarding assistance, product walk-throughs, and feedback logs.'],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(['name' => $dept['name']], $dept);
        }
    }
}
