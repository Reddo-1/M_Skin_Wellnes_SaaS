<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id');
            $table->foreignId('center_id');
            $table->foreignId('product_id');
            $table->decimal('quantity', 10, 3);
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['id', 'center_id'], 'uq_appointment_products_id_center');
            $table->unique(['appointment_id', 'product_id'], 'uq_appointment_products_appt_product');

            $table->foreign(['appointment_id', 'center_id'], 'fk_appt_products_appointment')
                ->references(['id', 'center_id'])->on('appointments')
                ->cascadeOnDelete();

            $table->foreign(['product_id', 'center_id'], 'fk_appt_products_product')
                ->references(['id', 'center_id'])->on('products')
                ->cascadeOnDelete();

            $table->index(['center_id', 'appointment_id'], 'idx_appointment_products_center_appt');
            $table->index(['center_id', 'product_id'], 'idx_appointment_products_center_product');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_products');
    }
};
