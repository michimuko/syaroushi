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
        Schema::create('client_procedure_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('procedure_type_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->json('lead_days_override')->nullable();
            $table->timestamps();

            $table->unique(['client_id', 'procedure_type_id'], 'client_procedure_subscriptions_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_procedure_subscriptions');
    }
};
