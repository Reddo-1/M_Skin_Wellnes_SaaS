<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->nullable();
            $table->string('name', 120);
            $table->string('email', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('password', 255)->nullable();
            $table->timestampTz('email_verified_at')->nullable();
            $table->rememberToken();
            $table->string('registration_source', 20)->nullable();
            $table->integer('failed_attempts')->default(0);
            $table->timestampTz('locked_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique(['center_id', 'email'], 'uq_users_center_email');
            $table->unique(['id', 'center_id'], 'uq_users_id_center');

            $table->foreign('center_id', 'fk_users_center')
                ->references('id')->on('centers');
        });

        DB::statement("
            ALTER TABLE users
            ADD CONSTRAINT chk_users_registration_src
            CHECK (
                registration_source IS NULL
                OR registration_source IN ('online', 'staff')
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
