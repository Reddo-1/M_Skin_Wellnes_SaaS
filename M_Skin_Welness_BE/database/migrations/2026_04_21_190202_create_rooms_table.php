<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id');
            $table->string('name', 120);
            $table->boolean('is_active')->default(true);
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['id', 'center_id'], 'uq_rooms_id_center');
            $table->unique(['center_id', 'name'], 'uq_rooms_center_name');

            $table->foreign('center_id', 'fk_rooms_center')
                ->references('id')->on('centers');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
