<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id');
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->decimal('cost_price', 10, 2)->nullable();
            //dosis que rinde un paquete; 1 cuando un paquete es una sola dosis (mascarillas individuales)
            $table->integer('doses_per_package')->default(1);
            $table->decimal('minimum_stock', 10, 3)->default(0);
            $table->boolean('is_sellable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique(['id', 'center_id'], 'uq_products_id_center');
            $table->unique(['center_id', 'name'], 'uq_products_center_name');

            $table->foreign('center_id', 'fk_products_center')
                ->references('id')->on('centers')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
