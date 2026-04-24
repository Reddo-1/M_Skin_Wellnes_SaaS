<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('user_id');
            $table->foreignId('role_id');

            $table->primary(['user_id', 'role_id'], 'pk_role_user');

            $table->foreign('user_id', 'fk_role_user_user')
                ->references('id')->on('users');

            $table->foreign('role_id', 'fk_role_user_role')
                ->references('id')->on('roles');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
    }
};
