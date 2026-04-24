<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id');
            $table->foreignId('worker_id');
            $table->integer('weekday');
            $table->foreignId('time_slot_id');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(
                ['center_id', 'worker_id', 'weekday', 'time_slot_id', 'start_date'],
                'uq_worker_schedules_key'
            );

            $table->foreign('center_id', 'fk_worker_schedules_center')
                ->references('id')->on('centers');

            $table->foreign(['worker_id', 'center_id'], 'fk_worker_schedules_worker')
                ->references(['id', 'center_id'])->on('users');

            $table->foreign(['time_slot_id', 'center_id'], 'fk_worker_schedules_slot')
                ->references(['id', 'center_id'])->on('time_slots');

            $table->index(['center_id', 'weekday', 'time_slot_id'], 'idx_worker_schedules_center_day_slot');
        });

        DB::statement("
            ALTER TABLE worker_schedules
            ADD CONSTRAINT chk_worker_schedules_day CHECK (weekday BETWEEN 1 AND 7)
        ");

        DB::statement("
            ALTER TABLE worker_schedules
            ADD CONSTRAINT chk_worker_schedules_dates
            CHECK (end_date IS NULL OR end_date >= start_date)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_schedules');
    }
};
