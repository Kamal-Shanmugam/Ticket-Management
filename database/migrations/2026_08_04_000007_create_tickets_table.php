<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->foreignId('ticket_priority_id')->constrained('ticket_priorities')->onDelete('cascade');
            $table->foreignId('ticket_status_id')->constrained('ticket_statuses')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('employees')->onDelete('set null');
            $table->string('title');
            $table->text('description');
            $table->timestamp('estimated_resolution_at')->nullable();
            $table->timestamp('actual_resolution_at')->nullable();
            $table->boolean('sla_warning_notified')->default(false);
            $table->boolean('sla_breached_notified')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
