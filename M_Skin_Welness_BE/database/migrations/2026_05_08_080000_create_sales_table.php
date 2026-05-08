<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id');
            $table->foreignId('client_id');
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->foreignId('created_by_user_id');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->foreignId('status_id');
            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->unique(['id', 'center_id'], 'uq_sales_id_center');

            $table->foreign('center_id', 'fk_sales_center')
                ->references('id')->on('centers')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign(['client_id', 'center_id'], 'fk_sales_client')
                ->references(['id', 'center_id'])->on('users');

            $table->foreign('appointment_id', 'fk_sales_appointment')
                ->references('id')->on('appointments')
                ->nullOnDelete();

            $table->foreign(['created_by_user_id', 'center_id'], 'fk_sales_creator')
                ->references(['id', 'center_id'])->on('users');

            $table->foreign('status_id', 'fk_sales_status')
                ->references('id')->on('sale_statuses')
                ->restrictOnDelete();

            $table->index(['center_id', 'client_id'], 'idx_sales_center_client');
            $table->index(['center_id', 'appointment_id'], 'idx_sales_center_appointment');
            $table->index(['center_id', 'status_id'], 'idx_sales_center_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
