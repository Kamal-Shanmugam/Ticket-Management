<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            [
                'name' => 'Acme Corp',
                'email' => 'acme@customer.com',
                'password' => Hash::make('password'),
                'api_token' => 'token_acme_123',
            ],
            [
                'name' => 'Stark Industries',
                'email' => 'stark@customer.com',
                'password' => Hash::make('password'),
                'api_token' => 'token_stark_123',
            ],
            [
                'name' => 'Wayne Enterprises',
                'email' => 'wayne@customer.com',
                'password' => Hash::make('password'),
                'api_token' => 'token_wayne_123',
            ],
        ];

        foreach ($customers as $cust) {
            Customer::firstOrCreate(['email' => $cust['email']], $cust);
        }
    }
}
