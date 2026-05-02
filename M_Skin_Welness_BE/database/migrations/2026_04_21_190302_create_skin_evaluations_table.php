<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skin_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id');
            $table->foreignId('user_id');
            $table->foreignId('client_profile_id');
            $table->foreignId('skin_type_id');
            $table->date('evaluation_date')->useCurrent();
            $table->foreignId('professional_id');
            $table->text('general_notes')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['id', 'center_id'], 'uq_skin_evaluations_id_center');

            $table->foreign('center_id', 'fk_skin_eval_center')
                ->references('id')->on('centers')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign(['user_id', 'center_id'], 'fk_skin_eval_user')
                ->references(['id', 'center_id'])->on('users');

            $table->foreign(['client_profile_id', 'center_id'], 'fk_skin_eval_profile')
                ->references(['id', 'center_id'])->on('client_profiles');

            $table->foreign('skin_type_id', 'fk_skin_eval_skin_type')
                ->references('id')->on('skin_types')
                ->restrictOnDelete();

            $table->foreign(['professional_id', 'center_id'], 'fk_skin_eval_professional')
                ->references(['id', 'center_id'])->on('users');

            $table->index(['center_id', 'user_id', 'evaluation_date'], 'idx_skin_eval_center_user_date');
            $table->index(['center_id', 'client_profile_id', 'evaluation_date'], 'idx_skin_eval_center_profile_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skin_evaluations');
    }
};
