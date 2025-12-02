<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('workflow_transitions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('definition_id');
            $table->string('key');                           // Transition identifier within definition
            $table->string('label')->nullable();
            $table->unsignedBigInteger('from_state_id');     // FK to workflow_states
            $table->unsignedBigInteger('to_state_id');       // FK to workflow_states
            $table->json('guard')->nullable();               // Guard condition(s) (extensible)
            $table->json('action')->nullable();              // Action(s) (extensible)
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['definition_id', 'key']);
            $table->foreign('definition_id')->references('id')->on('workflow_definitions')->cascadeOnDelete();
            $table->foreign('from_state_id')->references('id')->on('workflow_states')->restrictOnDelete();
            $table->foreign('to_state_id')->references('id')->on('workflow_states')->restrictOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('workflow_transitions');
    }
};
