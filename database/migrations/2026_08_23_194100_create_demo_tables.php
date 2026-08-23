<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_seeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('seed_id')->unique();
            $table->string('label');
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('demo_reset_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('action')->comment('reset|delete');
            $table->foreignId('performed_by')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('affected_records')->default(0);
            $table->timestamp('performed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_reset_logs');
        Schema::dropIfExists('demo_seeds');
    }
};
