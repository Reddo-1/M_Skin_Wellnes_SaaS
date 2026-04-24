<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id');
            $table->foreignId('user_id');
            $table->foreignId('skin_type_id');
            $table->date('last_review_date')->useCurrent();
            $table->foreignId('updated_by_user_id');
            $table->text('general_notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique(['id', 'center_id'], 'uq_client_profiles_id_center');

            $table->foreign('center_id', 'fk_client_profiles_center')
                ->references('id')->on('centers');

            $table->foreign(['user_id', 'center_id'], 'fk_client_profiles_user')
                ->references(['id', 'center_id'])->on('users');

            $table->foreign('skin_type_id', 'fk_client_profiles_skin_type')
                ->references('id')->on('skin_types');

            $table->foreign(['updated_by_user_id', 'center_id'], 'fk_client_profiles_updater')
                ->references(['id', 'center_id'])->on('users');

            $table->index(['center_id', 'user_id'], 'idx_client_profiles_center_user');
            $table->index('skin_type_id', 'idx_client_profiles_skin_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_profiles');
    }
};
