<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('category')->comment('vertrag|onboarding|kyc|rechnung|board_pack|sonstiges');
            $table->string('related_type')->nullable()->comment('Polymorpher Bezug: Organization, Contract, Receivable, Facility, KycCase');
            $table->unsignedBigInteger('related_id')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->string('storage_path')->nullable()->comment('Nie oeffentlich, nur ueber Controller-Download');
            $table->string('visibility')->default('intern')->comment('intern|vertraulich|externe_freigabe_ausstehend|extern_freigegeben|gesperrt');
            $table->string('release_purpose')->nullable();
            $table->string('release_audience')->nullable();
            $table->date('release_expires_at')->nullable();
            $table->boolean('export_locked')->default(false)->comment('Sperrvermerk: technisch erzwungene Exportsperre');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
