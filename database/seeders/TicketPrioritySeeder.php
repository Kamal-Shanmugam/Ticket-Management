<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TicketPriority;

class TicketPrioritySeeder extends Seeder
{
    public function run(): void
    {
        $priorities = [
            ['name' => 'Critical', 'slug' => 'critical', 'resolution_hours' => 2],
            ['name' => 'High', 'slug' => 'high', 'resolution_hours' => 6],
            ['name' => 'Medium', 'slug' => 'medium', 'resolution_hours' => 24],
            ['name' => 'Low', 'slug' => 'low', 'resolution_hours' => 48],
        ];

        foreach ($priorities as $priority) {
            TicketPriority::firstOrCreate(['slug' => $priority['slug']], $priority);
        }
    }
}
