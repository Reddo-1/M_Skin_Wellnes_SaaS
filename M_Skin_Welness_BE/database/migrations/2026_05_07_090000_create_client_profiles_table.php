<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id');
            $table->foreignId('user_id');
            //tipo de ficha: facial o corporal. cada cliente puede tener una de cada
            $table->string('body_type', 20);
            //puntero a la evaluacion vigente. nullable hasta que haya primera revision
            $table->unsignedBigInteger('current_skin_evaluation_id')->nullable();
            //notas permanentes del cliente (alergias, embarazo, etc.) no de una revision concreta
            $table->text('general_notes')->nullable();
            $table->timestampsTz();

            $table->unique(['id', 'center_id'], 'uq_client_profiles_id_center');
            //un cliente solo puede tener una ficha facial y una corporal activas en el mismo centro
            $table->unique(['center_id', 'user_id', 'body_type'], 'uq_client_profiles_user_body');

            $table->foreign('center_id', 'fk_client_profiles_center')
                ->references('id')->on('centers')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign(['user_id', 'center_id'], 'fk_client_profiles_user')
                ->references(['id', 'center_id'])->on('users');

            $table->index(['center_id', 'user_id'], 'idx_client_profiles_center_user');
        });

        DB::statement("
            ALTER TABLE client_profiles
            ADD CONSTRAINT chk_client_profiles_body_type
            CHECK (body_type IN ('facial', 'corporal'))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('client_profiles');
    }
};
