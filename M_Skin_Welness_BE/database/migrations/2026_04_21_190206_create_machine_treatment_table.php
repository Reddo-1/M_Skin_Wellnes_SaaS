<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_treatment', function (Blueprint $table) {
            $table->foreignId('center_id');
            $table->foreignId('machine_id');
            $table->foreignId('treatment_id');

            $table->primary(['machine_id', 'treatment_id'], 'pk_machine_treatment');

            $table->foreign('center_id', 'fk_machine_treatment_center')
                ->references('id')->on('centers');

            $table->foreign(['machine_id', 'center_id'], 'fk_machine_treatment_machine')
                ->references(['id', 'center_id'])->on('machines');

            $table->foreign(['treatment_id', 'center_id'], 'fk_machine_treatment_treat')
                ->references(['id', 'center_id'])->on('treatments');

            $table->index(['center_id', 'machine_id'], 'idx_machine_treatment_center_machine');
            $table->index(['center_id', 'treatment_id'], 'idx_machine_treatment_center_treat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_treatment');
    }
};
