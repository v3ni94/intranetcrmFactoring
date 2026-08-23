<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('key')->comment('z.B. kyc_kyb, pep_sanctions, credit_bureau, esignature, bank, datev, praxis_import, ocr, collections, bi');
            $table->string('category')->comment('Bank|KYC|PEP|Bonitaet|E-Signatur|DATEV|Praxissoftware|OCR|Inkasso|BI');
            $table->string('name')->comment('Anzeigename des Adapters, kein Anbieter hart verdrahtet');
            $table->string('mode')->default('sandbox')->comment('sandbox|manuell|live');
            $table->string('status')->default('unbekannt')->comment('unbekannt|healthy|degraded|fehler');
            $table->string('mapping_version')->default('v1');
            $table->timestamp('last_success_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'key']);
        });

        Schema::create('integration_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('integration_provider_id')->constrained()->cascadeOnDelete();
            $table->string('direction')->default('outbound')->comment('outbound|inbound');
            $table->string('external_reference')->nullable()->comment('Eindeutige externe ID des Adapters');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('status')->default('erfolgreich')->comment('erfolgreich|fehlgeschlagen|dead_letter');
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->string('consent_reference')->nullable()->comment('Bezug zur Einwilligung/Rechtsgrundlage');
            $table->text('summary')->nullable()->comment('Kurzbeschreibung ohne Gesundheitsdaten/Secrets');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_events');
        Schema::dropIfExists('integration_providers');
    }
};
