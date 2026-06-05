<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('centers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->string('name', 120);
            $table->string('slug', 80);
            $table->foreignId('plan_id');
            $table->foreignId('billing_user_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique(['uuid'], 'uq_centers_uuid');
            $table->unique(['slug'], 'uq_centers_slug');

            $table->foreign('plan_id', 'fk_centers_plan')
                ->references('id')->on('plans')
                ->restrictOnDelete();

            $table->foreign('billing_user_id', 'fk_centers_billing_user')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('centers');
    }
};

