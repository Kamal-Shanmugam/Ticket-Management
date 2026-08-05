<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
            $table->string('performed_by_type')->nullable(); // can be Customer, Employee, or system (null)
            $table->unsignedBigInteger('performed_by_id')->nullable();
            $table->string('action'); // e.g. created, assigned, status_changed, etc.
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index(['performed_by_type', 'performed_by_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_logs');
    }
};
