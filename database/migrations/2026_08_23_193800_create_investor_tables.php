<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('investor_organization_id')->constrained('organizations')->restrictOnDelete();
            $table->string('facility_number')->unique();
            $table->string('name');
            $table->decimal('commitment_amount', 19, 4)->comment('Zugesagtes Kapital');
            $table->decimal('drawn_amount', 19, 4)->default(0);
            $table->decimal('interest_rate_percent', 5, 3);
            $table->decimal('commitment_fee_percent', 5, 3)->nullable();
            $table->date('start_date')->nullable();
            $table->date('maturity_date')->nullable();
            $table->string('seniority')->default('senior')->comment('senior|nachrangig');
            $table->json('covenants')->nullable()->comment('Versionierte Covenant-Definitionen');
            $table->string('status')->default('aktiv')->comment('aktiv|ausgesetzt|beendet');
            $table->string('detail_level')->default('aggregiert')->comment('aggregiert|kundenebene|forderungsebene_pseudonym');
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('facility_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->string('event_type')->comment('drawdown|zinszahlung|rueckzahlung|covenant_check');
            $table->decimal('amount', 19, 4)->nullable();
            $table->date('event_date');
            $table->string('covenant_status')->nullable()->comment('eingehalten|verletzt|warnung');
            $table->text('notes')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_events');
        Schema::dropIfExists('facilities');
    }
};
