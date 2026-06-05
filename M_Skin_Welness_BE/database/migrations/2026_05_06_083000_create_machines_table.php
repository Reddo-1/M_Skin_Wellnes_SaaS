<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id');
            $table->string('name', 120);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_mobile')->default(false);
            $table->unsignedBigInteger('fixed_room_id')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['id', 'center_id'], 'uq_machines_id_center');
            $table->unique(['center_id', 'name'], 'uq_machines_center_name');

            $table->foreign('center_id', 'fk_machines_center')
                ->references('id')->on('centers')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('fixed_room_id', 'fk_machines_room')
                ->references('id')->on('rooms')
                ->nullOnDelete();
        });

        DB::statement("
            ALTER TABLE machines
            ADD CONSTRAINT chk_machines_mobile_room
            CHECK (NOT (is_mobile = TRUE AND fixed_room_id IS NOT NULL))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('machines');
    }
};
