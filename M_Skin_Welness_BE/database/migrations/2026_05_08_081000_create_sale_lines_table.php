<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id');
            $table->foreignId('center_id');
            $table->string('type', 20);
            $table->unsignedBigInteger('reference_id');
            $table->string('description', 200);
            $table->decimal('quantity', 8, 3)->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('line_discount', 10, 2)->default(0);
            $table->decimal('line_total', 10, 2);

            $table->foreign(['sale_id', 'center_id'], 'fk_sale_lines_sale')
                ->references(['id', 'center_id'])->on('sales');

            $table->index(['center_id', 'sale_id'], 'idx_sale_lines_center_sale');
        });

        DB::statement("
            ALTER TABLE sale_lines
            ADD CONSTRAINT chk_sale_lines_type CHECK (type IN ('treatment', 'product'))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_lines');
    }
};
