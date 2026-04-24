<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_absences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id');
            $table->foreignId('worker_id');
            $table->date('date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->boolean('is_full_day')->default(false);
            $table->string('reason', 120)->nullable();
            $table->foreignId('absence_type_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->foreign('center_id', 'fk_worker_absences_center')
                ->references('id')->on('centers');

            $table->foreign(['worker_id', 'center_id'], 'fk_worker_absences_worker')
                ->references(['id', 'center_id'])->on('users');

            $table->foreign('absence_type_id', 'fk_worker_absences_type')
                ->references('id')->on('absence_types');

            $table->index(
                ['center_id', 'worker_id', 'date', 'start_time', 'end_time'],
                'idx_worker_absences_key'
            );
        });

        DB::statement("
            ALTER TABLE worker_absences
            ADD CONSTRAINT chk_worker_absences_times
            CHECK (end_time IS NULL OR end_time > start_time)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_absences');
    }
};
