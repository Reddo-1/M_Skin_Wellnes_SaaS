<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_extra_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id');
            $table->foreignId('worker_id');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('reason', 120)->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(
                ['center_id', 'worker_id', 'date', 'start_time', 'end_time'],
                'uq_worker_extra_avail_key'
            );

            $table->foreign('center_id', 'fk_worker_extra_avail_center')
                ->references('id')->on('centers')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign(['worker_id', 'center_id'], 'fk_worker_extra_avail_worker')
                ->references(['id', 'center_id'])->on('users');
        });

        DB::statement("
            ALTER TABLE worker_extra_availabilities
            ADD CONSTRAINT chk_worker_extra_avail_times CHECK (end_time > start_time)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_extra_availabilities');
    }
};
