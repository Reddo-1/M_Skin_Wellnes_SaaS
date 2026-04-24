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
            $table->string('custom_domain', 255)->nullable();
            $table->boolean('is_domain_verified')->default(false);
            $table->foreignId('plan_id');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique(['uuid'], 'uq_centers_uuid');
            $table->unique(['slug'], 'uq_centers_slug');
            $table->unique(['custom_domain'], 'uq_centers_custom_domain');

            $table->foreign('plan_id', 'fk_centers_plan')
                ->references('id')->on('plans');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('centers');
    }
};

