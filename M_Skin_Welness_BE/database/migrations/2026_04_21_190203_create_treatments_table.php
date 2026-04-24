<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id');
            $table->string('name', 120);
            $table->integer('duration_minutes');
            $table->decimal('price', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique(['id', 'center_id'], 'uq_treatments_id_center');
            $table->unique(['center_id', 'name'], 'uq_treatments_center_name');

            $table->foreign('center_id', 'fk_treatments_center')
                ->references('id')->on('centers');
        });

        DB::statement("
            ALTER TABLE treatments
            ADD CONSTRAINT chk_treatments_duration CHECK (duration_minutes > 0)
        ");

        DB::statement("
            ALTER TABLE treatments
            ADD CONSTRAINT chk_treatments_price CHECK (price >= 0)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('treatments');
    }
};
