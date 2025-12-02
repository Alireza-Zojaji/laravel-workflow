<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('workflow_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('instance_id');
            $table->unsignedBigInteger('state_id')->nullable();  // Associated state for the task
            $table->string('name');                               // Task title (e.g., "Edit content")
            $table->unsignedBigInteger('assigned_to')->nullable();// User/Role ID (generic)
            $table->timestamp('due_at')->nullable();
            $table->string('status')->default('open');            // open|in_progress|done|cancelled
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['assigned_to', 'status']);
            $table->foreign('instance_id')->references('id')->on('workflow_instances')->cascadeOnDelete();
            $table->foreign('state_id')->references('id')->on('workflow_states')->nullOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('workflow_tasks');
    }
};
