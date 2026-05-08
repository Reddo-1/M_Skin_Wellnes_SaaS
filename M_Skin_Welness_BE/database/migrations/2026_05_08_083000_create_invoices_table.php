<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id');
            $table->foreignId('sale_id');
            $table->foreignId('client_id');
            $table->string('invoice_number', 20);
            $table->date('issued_date')->useCurrent();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('vat_percentage', 5, 2)->default(21);
            $table->decimal('vat_amount', 10, 2);
            $table->decimal('total', 10, 2);
            $table->json('client_snapshot');
            $table->json('center_snapshot');
            $table->string('pdf_path', 255)->nullable();
            $table->foreignId('issued_by_user_id');
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['sale_id'], 'uq_invoices_sale_id');
            $table->unique(['id', 'center_id'], 'uq_invoices_id_center');
            $table->unique(['center_id', 'invoice_number'], 'uq_invoices_center_number');

            $table->foreign('center_id', 'fk_invoices_center')
                ->references('id')->on('centers')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign(['sale_id', 'center_id'], 'fk_invoices_sale')
                ->references(['id', 'center_id'])->on('sales');

            $table->foreign(['client_id', 'center_id'], 'fk_invoices_client')
                ->references(['id', 'center_id'])->on('users');

            $table->foreign(['issued_by_user_id', 'center_id'], 'fk_invoices_issuer')
                ->references(['id', 'center_id'])->on('users');

            $table->index(['center_id', 'client_id'], 'idx_invoices_center_client');
            $table->index(['center_id', 'issued_date'], 'idx_invoices_center_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
