<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id');
            $table->foreignId('user_id');
            $table->unsignedBigInteger('skin_evaluation_id')->nullable();
            $table->string('type', 30);
            $table->string('category', 40);
            $table->string('path', 255);
            $table->string('mime_type', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['id', 'center_id'], 'uq_user_files_id_center');

            $table->foreign('center_id', 'fk_user_files_center')
                ->references('id')->on('centers')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign(['user_id', 'center_id'], 'fk_user_files_user')
                ->references(['id', 'center_id'])->on('users');

            $table->foreign(['skin_evaluation_id', 'center_id'], 'fk_user_files_skin_evaluation')
                ->references(['id', 'center_id'])->on('skin_evaluations');

            $table->index(['center_id', 'user_id'], 'idx_user_files_center_user');
            $table->index(['center_id', 'skin_evaluation_id'], 'idx_user_files_center_skin_eval');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_files');
    }
};
