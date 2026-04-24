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
            $table->unsignedBigInteger('center_id')->nullable();
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
        });

        DB::statement("
            ALTER TABLE users
            ADD CONSTRAINT chk_users_registration_src
            CHECK (
                registration_source IS NULL
                OR registration_source IN ('online', 'staff')
            )
        ");

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
