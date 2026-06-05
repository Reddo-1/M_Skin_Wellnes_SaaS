<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id');
            $table->foreignId('user_id');
            $table->foreignId('reviewed_by_user_id');

            $table->boolean('clinical_photos_consent')->default(false);
            $table->boolean('marketing_data_consent')->default(false);
            $table->boolean('commercial_images_consent')->default(false);

            $table->unsignedBigInteger('signature_user_file_id')->nullable();
            $table->timestampTz('signed_at')->nullable();

            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique(['id', 'center_id'], 'uq_client_consents_id_center');

            $table->foreign('center_id', 'fk_client_consents_center')
                ->references('id')->on('centers')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign(['user_id', 'center_id'], 'fk_client_consents_user')
                ->references(['id', 'center_id'])->on('users');

            $table->foreign(['reviewed_by_user_id', 'center_id'], 'fk_client_consents_reviewer')
                ->references(['id', 'center_id'])->on('users');

            $table->foreign('signature_user_file_id', 'fk_client_consents_signature')
                ->references('id')->on('user_files')
                ->nullOnDelete();

            $table->index(['center_id', 'user_id'], 'idx_client_consents_center_user');
        });

        //una sola consent vigente por paciente y centro
        DB::statement("
            CREATE UNIQUE INDEX uq_client_consents_active
            ON client_consents (center_id, user_id)
            WHERE is_active = true
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('client_consents');
    }
};
