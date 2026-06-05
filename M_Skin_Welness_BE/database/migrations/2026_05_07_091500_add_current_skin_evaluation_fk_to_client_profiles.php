<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    //la FK se anade en una migracion posterior porque skin_evaluations no existe cuando se crea client_profiles
    public function up(): void
    {
        Schema::table('client_profiles', function (Blueprint $table) {
            $table->foreign(['current_skin_evaluation_id', 'center_id'], 'fk_client_profiles_current_skin_eval')
                ->references(['id', 'center_id'])->on('skin_evaluations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('client_profiles', function (Blueprint $table) {
            $table->dropForeign('fk_client_profiles_current_skin_eval');
        });
    }
};
