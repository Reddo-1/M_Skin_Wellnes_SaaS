<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->unsignedBigInteger('center_id')->nullable();
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->string('action', 60);
            $table->json('metadata')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('actor_user_id', 'fk_audit_logs_actor')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->foreign('center_id', 'fk_audit_logs_center')
                ->references('id')->on('centers')
                ->nullOnDelete();

            $table->foreign('plan_id', 'fk_audit_logs_plan')
                ->references('id')->on('plans')
                ->nullOnDelete();

            $table->index(['center_id', 'created_at'], 'idx_audit_logs_center');
            $table->index(['action', 'created_at'], 'idx_audit_logs_action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
