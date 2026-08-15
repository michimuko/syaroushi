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
        Schema::create('procedure_task_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->foreignId('procedure_task_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_required')->default(true);
            $table->boolean('is_collected')->default(false);
            $table->timestamp('collected_at')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedSmallInteger('retention_years')->nullable();
            $table->date('retention_until')->nullable();
            $table->timestamps();

            $table->index(['office_id', 'procedure_task_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procedure_task_documents');
    }
};
