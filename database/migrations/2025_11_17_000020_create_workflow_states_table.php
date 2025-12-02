<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('workflow_states', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('definition_id');
            $table->string('key');                 // State identifier within definition (unique per definition)
            $table->string('label')->nullable();
            $table->string('type')->default('normal'); // initial|normal|final
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['definition_id', 'key']);
            $table->foreign('definition_id')->references('id')->on('workflow_definitions')->cascadeOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('workflow_states');
    }
};
