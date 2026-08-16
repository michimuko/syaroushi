<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('custom_field_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->enum('target', ['client', 'procedure_task']);
            $table->string('label');
            $table->enum('field_type', ['text', 'number', 'date', 'select', 'checkbox']);
            $table->json('options')->nullable();
            $table->timestamps();

            $table->index(['office_id', 'target']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_field_definitions');
    }
};
