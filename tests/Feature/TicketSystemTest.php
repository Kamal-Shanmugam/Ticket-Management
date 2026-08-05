<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketSystemTest extends TestCase
{
    use RefreshDatabase;

    protected $adminRole;
    protected $tlRole;
    protected $staffRole;
    protected $techDept;
    protected $criticalPriority;
    protected $openStatus;
    protected $assignedStatus;
    protected $resolvedStatus;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        $this->adminRole = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $this->tlRole = Role::create(['name' => 'Team Lead', 'slug' => 'team_lead']);
        $this->staffRole = Role::create(['name' => 'Staff', 'slug' => 'staff']);

        // Seed departments
        $this->techDept = Department::create(['name' => 'Technical Support']);

        // Seed priorities
        $this->criticalPriority = TicketPriority::create(['name' => 'Critical', 'slug' => 'critical', 'resolution_hours' => 2]);
        TicketPriority::create(['name' => 'High', 'slug' => 'high', 'resolution_hours' => 6]);
        TicketPriority::create(['name' => 'Medium', 'slug' => 'medium', 'resolution_hours' => 24]);
        TicketPriority::create(['name' => 'Low', 'slug' => 'low', 'resolution_hours' => 48]);

        // Seed statuses
        $this->openStatus = TicketStatus::create(['name' => 'Open', 'slug' => 'open']);
        $this->assignedStatus = TicketStatus::create(['name' => 'Assigned', 'slug' => 'assigned']);
        TicketStatus::create(['name' => 'In Progress', 'slug' => 'in_progress']);
        TicketStatus::create(['name' => 'Waiting for Customer', 'slug' => 'waiting_for_customer']);
        TicketStatus::create(['name' => 'Escalated', 'slug' => 'escalated']);
        $this->resolvedStatus = TicketStatus::create(['name' => 'Resolved', 'slug' => 'resolved']);
        TicketStatus::create(['name' => 'Closed', 'slug' => 'closed']);
        TicketStatus::create(['name' => 'Reopened', 'slug' => 'reopened']);
    }

    public function test_a_customer_can_register_and_login_via_api()
    {
        // 1. Test Registration
        $registerResponse = $this->postJson('/api/auth/customer/register', [
            'name' => 'John Doe',
            'email' => 'john@acme.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $registerResponse->assertStatus(211)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['token', 'customer']
            ]);

        $this->assertDatabaseHas('customers', [
            'email' => 'john@acme.com',
        ]);

        // 2. Test Login
        $loginResponse = $this->postJson('/api/auth/customer/login', [
            'email' => 'john@acme.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Customer logged in successfully.'
            ]);
    }

    public function test_invalid_registration_returns_standard_validation_json()
    {
        $response = $this->postJson('/api/auth/customer/register', [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
            ])
            ->assertJsonStructure(['errors']);
    }

    public function test_ticket_is_auto_assigned_on_creation_to_employee_with_least_workload()
    {
        // Setup Customer
        $customer = Customer::create([
            'name' => 'Acme Inc',
            'email' => 'acme@test.com',
            'password' => bcrypt('password'),
            'api_token' => 'test_token',
        ]);

        // Setup available staff members in Tech Department
        // Staff 1 (Available, last assigned null)
        $staff1 = Employee::create([
            'role_id' => $this->staffRole->id,
            'department_id' => $this->techDept->id,
            'name' => 'Staff One',
            'email' => 'staff1@test.com',
            'password' => bcrypt('password'),
            'is_available' => true,
            'last_assigned_at' => null,
        ]);

        // Staff 2 (Available, last assigned null)
        $staff2 = Employee::create([
            'role_id' => $this->staffRole->id,
            'department_id' => $this->techDept->id,
            'name' => 'Staff Two',
            'email' => 'staff2@test.com',
            'password' => bcrypt('password'),
            'is_available' => true,
            'last_assigned_at' => null,
        ]);

        // Staff 3 (Unavailable, shouldn't get assigned)
        $staff3 = Employee::create([
            'role_id' => $this->staffRole->id,
            'department_id' => $this->techDept->id,
            'name' => 'Staff Three (Offline)',
            'email' => 'staff3@test.com',
            'password' => bcrypt('password'),
            'is_available' => false,
            'last_assigned_at' => null,
        ]);

        // Create a ticket for the Customer via API
        $response = $this->withHeader('Authorization', 'Bearer test_token')
            ->postJson('/api/customer/tickets', [
                'title' => 'Critical website crash',
                'description' => 'Homepage returning 500 error.',
                'department_id' => $this->techDept->id,
                'ticket_priority_id' => $this->criticalPriority->id,
            ]);

        $response->assertStatus(211);
        
        $ticketId = $response->json('data.id');

        // Confirm ticket was assigned to either Staff 1 or Staff 2, but not Staff 3
        $ticket = Ticket::find($ticketId);
        $this->assertNotNull($ticket->assigned_to);
        $this->assertNotEquals($staff3->id, $ticket->assigned_to);
        $this->assertTrue(in_array($ticket->assigned_to, [$staff1->id, $staff2->id]));
        $this->assertEquals($this->assignedStatus->id, $ticket->ticket_status_id);

        // Verify SLA Estimated time matches 2 hours (Critical priority resolution time)
        $estimatedTime = $ticket->estimated_resolution_at;
        $this->assertNotNull($estimatedTime);
        $this->assertTrue($estimatedTime->diffInHours(now()) <= 2);
    }

    public function test_customer_cannot_raise_duplicate_tickets_within_5_minutes()
    {
        $customer = Customer::create([
            'name' => 'Wayne Ent',
            'email' => 'wayne@test.com',
            'password' => bcrypt('password'),
            'api_token' => 'wayne_token',
        ]);

        // Raise first ticket
        $this->withHeader('Authorization', 'Bearer wayne_token')
            ->postJson('/api/customer/tickets', [
                'title' => 'Server offline',
                'description' => 'Database connection timeout.',
                'department_id' => $this->techDept->id,
                'ticket_priority_id' => $this->criticalPriority->id,
            ])
            ->assertStatus(211);

        // Raise duplicate ticket immediately
        $response = $this->withHeader('Authorization', 'Bearer wayne_token')
            ->postJson('/api/customer/tickets', [
                'title' => 'Server offline',
                'description' => 'Database connection timeout.',
                'department_id' => $this->techDept->id,
                'ticket_priority_id' => $this->criticalPriority->id,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
            ]);
            
        $this->assertArrayHasKey('title', $response->json('errors'));
    }

    public function test_customer_comment_on_resolved_ticket_reopens_it()
    {
        $customer = Customer::create([
            'name' => 'Stark Corp',
            'email' => 'stark@test.com',
            'password' => bcrypt('password'),
            'api_token' => 'stark_token',
        ]);

        $ticket = Ticket::create([
            'customer_id' => $customer->id,
            'department_id' => $this->techDept->id,
            'ticket_priority_id' => $this->criticalPriority->id,
            'ticket_status_id' => $this->resolvedStatus->id,
            'title' => 'Resolved issue',
            'description' => 'This is resolved.',
            'actual_resolution_at' => now()->subDay(),
        ]);

        // Post comment via API
        $response = $this->withHeader('Authorization', 'Bearer stark_token')
            ->postJson("/api/customer/tickets/{$ticket->id}/comments", [
                'comment' => 'It is still broken, reopening.',
            ]);

        $response->assertStatus(211);

        // Check status updated to reopened, and actual_resolution_at reset to null
        $ticket->refresh();
        $this->assertEquals('reopened', $ticket->status->slug);
        $this->assertNull($ticket->actual_resolution_at);
        $this->assertDatabaseHas('ticket_logs', [
            'ticket_id' => $ticket->id,
            'action' => 'status_changed',
        ]);
    }
}
