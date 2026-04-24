<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_treatment', function (Blueprint $table) {
            $table->foreignId('center_id');
            $table->foreignId('role_id');
            $table->foreignId('treatment_id');

            $table->primary(['center_id', 'role_id', 'treatment_id'], 'pk_role_treatment');

            $table->foreign('role_id', 'fk_role_treatment_role')
                ->references('id')->on('roles');

            $table->foreign(['treatment_id', 'center_id'], 'fk_role_treatment_treat')
                ->references(['id', 'center_id'])->on('treatments');

            $table->index(['center_id', 'role_id'], 'idx_role_treatment_center_role');
            $table->index(['center_id', 'treatment_id'], 'idx_role_treatment_center_treat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_treatment');
    }
};
