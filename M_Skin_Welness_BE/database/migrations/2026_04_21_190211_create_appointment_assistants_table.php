<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_assistants', function (Blueprint $table) {
            $table->foreignId('appointment_id');
            $table->foreignId('center_id');
            $table->foreignId('user_id');
            $table->text('notes')->nullable();

            $table->primary(['appointment_id', 'user_id'], 'pk_appointment_assistants');

            $table->foreign('center_id', 'fk_appt_assistants_center')
                ->references('id')->on('centers');

            $table->foreign(['appointment_id', 'center_id'], 'fk_appt_assistants_appointment')
                ->references(['id', 'center_id'])->on('appointments');

            $table->foreign(['user_id', 'center_id'], 'fk_appt_assistants_user')
                ->references(['id', 'center_id'])->on('users');

            $table->index(['center_id', 'user_id'], 'idx_appointment_assistants_center_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_assistants');
    }
};
