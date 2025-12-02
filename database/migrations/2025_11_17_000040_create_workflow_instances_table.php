<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('definition_id');
            $table->unsignedBigInteger('current_state_id')->nullable(); // May be null at start
            $table->string('status')->default('running');  // running|completed|cancelled|paused
            $table->string('model_type')->nullable();      // Optional external model type
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('variables')->nullable();         // Execution context (extensible)
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->foreign('definition_id')->references('id')->on('workflow_definitions')->cascadeOnDelete();
            $table->foreign('current_state_id')->references('id')->on('workflow_states')->nullOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('workflow_instances');
    }
};
