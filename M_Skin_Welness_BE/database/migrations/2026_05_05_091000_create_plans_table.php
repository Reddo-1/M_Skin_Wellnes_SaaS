<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30);
            $table->string('name', 60);
            $table->text('description')->nullable();
            $table->integer('max_workers')->default(3);
            $table->boolean('allows_online_clients')->default(false);
            $table->boolean('allows_emails')->default(false);
            $table->boolean('allows_public_page')->default(false);
            $table->boolean('allows_custom_domain')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique(['code'], 'uq_plans_code');
        });

        DB::statement("
            ALTER TABLE plans
            ADD CONSTRAINT chk_plans_max_workers CHECK (max_workers > 0)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};

