<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TicketStatus;

class TicketStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'Open', 'slug' => 'open'],
            ['name' => 'Assigned', 'slug' => 'assigned'],
            ['name' => 'In Progress', 'slug' => 'in_progress'],
            ['name' => 'Waiting for Customer', 'slug' => 'waiting_for_customer'],
            ['name' => 'Escalated', 'slug' => 'escalated'],
            ['name' => 'Resolved', 'slug' => 'resolved'],
            ['name' => 'Closed', 'slug' => 'closed'],
            ['name' => 'Reopened', 'slug' => 'reopened'],
        ];

        foreach ($statuses as $status) {
            TicketStatus::firstOrCreate(['slug' => $status['slug']], $status);
        }
    }
}
