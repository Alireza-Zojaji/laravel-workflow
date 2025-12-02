<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('workflow_tasks', function (Blueprint $table) {
            $table->string('assignment_type', 32)->default('user')->index(); // user|role|strategy
            $table->string('assignment_ref')->nullable();                    // User ID / Role name / public identifier
            $table->string('strategy_key')->nullable()->index();             // Strategy registry key
            $table->json('decision_options')->nullable();                    // Decision options (for UI)
        });
    }

    public function down(): void {
        Schema::table('workflow_tasks', function (Blueprint $table) {
            $table->dropColumn(['assignment_type', 'assignment_ref', 'strategy_key', 'decision_options']);
        });
    }
};
