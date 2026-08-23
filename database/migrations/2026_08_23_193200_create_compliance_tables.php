<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('case_type')->comment('KYC|KYB|PEP|Sanktionen|Zulassung');
            $table->string('provider')->nullable()->comment('Adapter-Name, kein Anbieter hart verdrahtet');
            $table->string('result')->default('offen')->comment('offen|unauffaellig|auffaellig|abgelehnt');
            $table->string('risk_class')->nullable()->comment('niedrig|mittel|hoch');
            $table->date('reviewed_at')->nullable();
            $table->date('next_review_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_cases');
    }
};
