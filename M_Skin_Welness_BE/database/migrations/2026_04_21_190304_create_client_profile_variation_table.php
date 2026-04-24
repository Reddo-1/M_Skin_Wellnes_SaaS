<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_profile_variation', function (Blueprint $table) {
            $table->foreignId('client_profile_id');
            $table->foreignId('variation_id');

            $table->primary(['client_profile_id', 'variation_id'], 'pk_client_profile_variation');

            $table->foreign('client_profile_id', 'fk_client_prof_var_profile')
                ->references('id')->on('client_profiles');

            $table->foreign('variation_id', 'fk_client_prof_var_variation')
                ->references('id')->on('variations');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_profile_variation');
    }
};
