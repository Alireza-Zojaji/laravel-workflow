<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('workflow_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('instance_id');
            $table->unsignedBigInteger('transition_id')->nullable();
            $table->unsignedBigInteger('from_state_id')->nullable();
            $table->unsignedBigInteger('to_state_id')->nullable();
            $table->unsignedBigInteger('performed_by')->nullable(); // User ID (no FK for generality)
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('performed_by');
            $table->foreign('instance_id')->references('id')->on('workflow_instances')->cascadeOnDelete();
            $table->foreign('transition_id')->references('id')->on('workflow_transitions')->nullOnDelete();
            $table->foreign('from_state_id')->references('id')->on('workflow_states')->nullOnDelete();
            $table->foreign('to_state_id')->references('id')->on('workflow_states')->nullOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('workflow_history');
    }
};
