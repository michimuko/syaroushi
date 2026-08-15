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
        Schema::create('procedure_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('procedure_type_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('due_date');
            $table->enum('status', [
                'not_started',
                'in_progress',
                'documents_collected',
                'submitted',
                'completed',
            ])->default('not_started');
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('custom_fields')->nullable();
            $table->json('calc_result')->nullable();
            $table->timestamps();

            $table->index(['office_id', 'status', 'due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procedure_tasks');
    }
};
