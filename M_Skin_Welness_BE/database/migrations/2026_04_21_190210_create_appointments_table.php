<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id');
            $table->foreignId('treatment_id');
            $table->foreignId('room_id');
            $table->foreignId('client_id');
            $table->foreignId('worker_id');
            $table->unsignedBigInteger('machine_id')->nullable();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->integer('actual_duration_minutes')->nullable();
            $table->string('booking_source', 50);
            $table->foreignId('status_id');
            $table->decimal('reserved_price', 10, 2)->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->unique(['id', 'center_id'], 'uq_appointments_id_center');

            $table->foreign('center_id', 'fk_appointments_center')
                ->references('id')->on('centers');

            $table->foreign('status_id', 'fk_appointments_status')
                ->references('id')->on('session_statuses');

            $table->foreign(['client_id', 'center_id'], 'fk_appointments_client')
                ->references(['id', 'center_id'])->on('users');

            $table->foreign(['worker_id', 'center_id'], 'fk_appointments_worker')
                ->references(['id', 'center_id'])->on('users');

            $table->foreign(['treatment_id', 'center_id'], 'fk_appointments_treatment')
                ->references(['id', 'center_id'])->on('treatments');

            $table->foreign(['room_id', 'center_id'], 'fk_appointments_room')
                ->references(['id', 'center_id'])->on('rooms');

            $table->foreign(['machine_id', 'center_id'], 'fk_appointments_machine')
                ->references(['id', 'center_id'])->on('machines');

            $table->index(['center_id', 'starts_at'], 'idx_appointments_center_starts');
            $table->index(['center_id', 'worker_id', 'starts_at'], 'idx_appointments_center_worker');
            $table->index(['center_id', 'room_id', 'starts_at'], 'idx_appointments_center_room');
            $table->index(['center_id', 'client_id', 'starts_at'], 'idx_appointments_center_client');
            $table->index(['center_id', 'treatment_id', 'starts_at'], 'idx_appointments_center_treatment');
        });

        DB::statement("
            ALTER TABLE appointments
            ADD CONSTRAINT chk_appointments_times CHECK (ends_at > starts_at)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
