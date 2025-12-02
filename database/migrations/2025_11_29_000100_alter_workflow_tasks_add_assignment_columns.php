<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('workflow_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('workflow_tasks', 'assignment_type')) {
                $table->string('assignment_type')->nullable()->after('assigned_to');
            }
            if (!Schema::hasColumn('workflow_tasks', 'assignment_ref')) {
                $table->string('assignment_ref')->nullable()->after('assignment_type');
            }
            if (!Schema::hasColumn('workflow_tasks', 'strategy_key')) {
                $table->string('strategy_key')->nullable()->after('assignment_ref');
            }
            if (!Schema::hasColumn('workflow_tasks', 'decision_options')) {
                $table->json('decision_options')->nullable()->after('metadata');
            }
        });
    }

    public function down(): void {
        Schema::table('workflow_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('workflow_tasks', 'assignment_type')) {
                $table->dropColumn('assignment_type');
            }
            if (Schema::hasColumn('workflow_tasks', 'assignment_ref')) {
                $table->dropColumn('assignment_ref');
            }
            if (Schema::hasColumn('workflow_tasks', 'strategy_key')) {
                $table->dropColumn('strategy_key');
            }
            if (Schema::hasColumn('workflow_tasks', 'decision_options')) {
                $table->dropColumn('decision_options');
            }
        });
    }
};

