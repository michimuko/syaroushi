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
        Schema::table('offices', function (Blueprint $table) {
            $table->string('stripe_customer_id')->nullable()->after('billing_plan_id');
            $table->string('stripe_subscription_id')->nullable()->after('stripe_customer_id');
            // Stripeのsubscription.status（active/trialing/past_due/canceled等）をそのまま保持する。
            // nullはまだ決済連携（Checkout）を一度も開始していない事務所を意味する。
            $table->string('stripe_subscription_status')->nullable()->after('stripe_subscription_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->dropColumn(['stripe_customer_id', 'stripe_subscription_id', 'stripe_subscription_status']);
        });
    }
};
