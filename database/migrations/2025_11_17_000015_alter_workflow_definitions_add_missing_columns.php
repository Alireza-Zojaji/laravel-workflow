<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('workflow_definitions', function (Blueprint $table) {
            if (!Schema::hasColumn('workflow_definitions', 'marking_store')) {
                $table->string('marking_store')->nullable()->index();
            }
            if (!Schema::hasColumn('workflow_definitions', 'places')) {
                $table->json('places')->nullable();
            }
            if (!Schema::hasColumn('workflow_definitions', 'transitions')) {
                $table->json('transitions')->nullable();
            }
        });
    }

    public function down(): void {
        Schema::table('workflow_definitions', function (Blueprint $table) {
            if (Schema::hasColumn('workflow_definitions', 'marking_store')) {
                $table->dropColumn('marking_store');
            }
            if (Schema::hasColumn('workflow_definitions', 'places')) {
                $table->dropColumn('places');
            }
            if (Schema::hasColumn('workflow_definitions', 'transitions')) {
                $table->dropColumn('transitions');
            }
        });
    }
};
