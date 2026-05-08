<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id');
            $table->foreignId('sale_id');
            $table->foreignId('payment_method_id');
            $table->foreignId('status_id');
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('EUR');
            $table->string('stripe_payment_intent_id', 100)->nullable();
            $table->string('stripe_charge_id', 100)->nullable();
            $table->json('stripe_metadata')->nullable();
            $table->timestampTz('processed_at')->nullable();
            $table->timestampsTz();

            $table->unique(['id', 'center_id'], 'uq_payments_id_center');
            $table->unique(['stripe_payment_intent_id'], 'uq_payments_stripe_intent');

            $table->foreign(['sale_id', 'center_id'], 'fk_payments_sale')
                ->references(['id', 'center_id'])->on('sales');

            $table->foreign('payment_method_id', 'fk_payments_method')
                ->references('id')->on('payment_methods')
                ->restrictOnDelete();

            $table->foreign('status_id', 'fk_payments_status')
                ->references('id')->on('payment_statuses')
                ->restrictOnDelete();

            $table->index(['center_id', 'sale_id'], 'idx_payments_center_sale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
