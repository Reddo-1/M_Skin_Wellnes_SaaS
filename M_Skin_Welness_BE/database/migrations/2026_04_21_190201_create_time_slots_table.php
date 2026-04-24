<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id');
            $table->string('name', 50)->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);

            $table->unique(['id', 'center_id'], 'uq_time_slots_id_center');
            $table->unique(['center_id', 'start_time', 'end_time'], 'uq_time_slots_center_times');

            $table->foreign('center_id', 'fk_time_slots_center')
                ->references('id')->on('centers');
        });

        DB::statement("
            ALTER TABLE time_slots
            ADD CONSTRAINT chk_time_slots_end CHECK (end_time > start_time)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('time_slots');
    }
};
