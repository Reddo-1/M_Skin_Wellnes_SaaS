<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_suitability_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id');
            $table->foreignId('user_id');
            $table->foreignId('treatment_id');
            $table->foreignId('reviewed_by_user_id');
            $table->date('review_date')->useCurrent();
            $table->boolean('is_suitable')->default(true);
            $table->string('unsuitability_reason', 150)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique(['id', 'center_id'], 'uq_treat_suit_eval_id_center');

            $table->foreign('center_id', 'fk_treat_suit_eval_center')
                ->references('id')->on('centers');

            $table->foreign(['user_id', 'center_id'], 'fk_treat_suit_eval_user')
                ->references(['id', 'center_id'])->on('users');

            $table->foreign(['treatment_id', 'center_id'], 'fk_treat_suit_eval_treatment')
                ->references(['id', 'center_id'])->on('treatments');

            $table->foreign(['reviewed_by_user_id', 'center_id'], 'fk_treat_suit_eval_reviewer')
                ->references(['id', 'center_id'])->on('users');

            $table->index(['center_id', 'user_id'], 'idx_treat_suit_eval_center_user');
            $table->index(['center_id', 'treatment_id'], 'idx_treat_suit_eval_center_treatment');
            $table->index(
                ['center_id', 'user_id', 'treatment_id', 'review_date'],
                'idx_treat_suit_eval_key'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_suitability_evaluations');
    }
};
