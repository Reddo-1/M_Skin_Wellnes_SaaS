<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id');
            $table->foreignId('product_id');
            $table->decimal('current_quantity', 10, 3)->default(0);
            $table->timestampsTz();

            $table->unique(['id', 'center_id'], 'uq_product_stocks_id_center');
            $table->unique(['center_id', 'product_id'], 'uq_product_stocks_center_product');

            $table->foreign(['product_id', 'center_id'], 'fk_product_stocks_product')
                ->references(['id', 'center_id'])->on('products');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_stocks');
    }
};
