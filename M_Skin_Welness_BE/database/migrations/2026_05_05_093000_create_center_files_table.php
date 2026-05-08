<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('center_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id');
            $table->string('type', 30);
            $table->string('path', 255);
            $table->string('mime_type', 100)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['id', 'center_id'], 'uq_center_files_id_center');

            $table->foreign('center_id', 'fk_center_files_center')
                ->references('id')->on('centers')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->index(['center_id', 'type'], 'idx_center_files_center_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('center_files');
    }
};

