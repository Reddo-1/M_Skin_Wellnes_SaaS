<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skin_evaluation_variation', function (Blueprint $table) {
            $table->foreignId('skin_evaluation_id');
            $table->foreignId('variation_id');
            $table->timestampTz('created_at')->useCurrent();

            $table->primary(['skin_evaluation_id', 'variation_id'], 'pk_skin_evaluation_variation');

            $table->foreign('skin_evaluation_id', 'fk_skin_eval_var_eval')
                ->references('id')->on('skin_evaluations');

            $table->foreign('variation_id', 'fk_skin_eval_var_variation')
                ->references('id')->on('variations');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skin_evaluation_variation');
    }
};
