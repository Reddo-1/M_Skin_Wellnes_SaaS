<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);

            $table->unique(['name'], 'uq_roles_name');
        });

        Schema::create('session_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->integer('sort_order')->default(0);

            $table->unique(['name'], 'uq_session_statuses_name');
        });

        Schema::create('absence_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);

            $table->unique(['name'], 'uq_absence_types_name');
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);

            $table->unique(['name'], 'uq_payment_methods_name');
        });

        Schema::create('payment_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);

            $table->unique(['name'], 'uq_payment_statuses_name');
        });

        Schema::create('sale_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);

            $table->unique(['name'], 'uq_sale_statuses_name');
        });

        Schema::create('stock_movement_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);

            $table->unique(['name'], 'uq_stock_movement_types_name');
        });

        Schema::create('skin_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);

            $table->unique(['name'], 'uq_skin_types_name');
        });

        Schema::create('variations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);

            $table->unique(['name'], 'uq_variations_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variations');
        Schema::dropIfExists('skin_types');
        Schema::dropIfExists('stock_movement_types');
        Schema::dropIfExists('sale_statuses');
        Schema::dropIfExists('payment_statuses');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('absence_types');
        Schema::dropIfExists('session_statuses');
        Schema::dropIfExists('roles');
    }
};

