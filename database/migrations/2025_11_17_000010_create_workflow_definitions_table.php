<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('workflow_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();      // Unique workflow identifier (slug-safe)
            $table->string('label')->nullable();   // Display label
            $table->text('description')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->json('schema')->nullable();    // Optional JSON schema (states/transitions) for import/export
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('workflow_definitions');
    }
};
