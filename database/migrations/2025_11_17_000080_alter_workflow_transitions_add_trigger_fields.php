<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('workflow_transitions', function (Blueprint $table) {
            $table->string('trigger_type', 32)->default('manual')->index(); // manual|automatic|timer|message
            $table->string('trigger_channel')->nullable();                  // For message/signal
            $table->string('guard_provider')->nullable();                   // Registry key of guard provider
            $table->string('action_provider')->nullable();                  // Registry key of action provider
        });
    }

    public function down(): void {
        Schema::table('workflow_transitions', function (Blueprint $table) {
            $table->dropColumn(['trigger_type', 'trigger_channel', 'guard_provider', 'action_provider']);
        });
    }
};
