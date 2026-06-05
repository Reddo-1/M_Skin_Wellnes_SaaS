<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id');
            $table->foreignId('product_id');
            $table->foreignId('movement_type_id');
            $table->decimal('quantity', 10, 3);
            $table->decimal('previous_quantity', 10, 3);
            $table->decimal('new_quantity', 10, 3);
            $table->string('reference_type', 30)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('user_id');
            $table->string('reason', 200)->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['id', 'center_id'], 'uq_stock_movements_id_center');

            $table->foreign(['product_id', 'center_id'], 'fk_stock_movements_product')
                ->references(['id', 'center_id'])->on('products');

            $table->foreign('movement_type_id', 'fk_stock_movements_type')
                ->references('id')->on('stock_movement_types')
                ->restrictOnDelete();

            $table->foreign(['user_id', 'center_id'], 'fk_stock_movements_user')
                ->references(['id', 'center_id'])->on('users');

            $table->index(['center_id', 'product_id'], 'idx_stock_movements_center_product');
            $table->index(['center_id', 'reference_type', 'reference_id'], 'idx_stock_movements_center_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
