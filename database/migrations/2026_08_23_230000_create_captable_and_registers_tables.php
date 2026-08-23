<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Abschnitt 14.1/19: streng geschuetztes optionales Modul, ausschliesslich
        // Geschaeftsleitung/Superadmin. Alle Werte sind Hypothese/Entwurf.
        Schema::create('shareholders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('person')->comment('person|gesellschaft');
            $table->text('notes')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('cap_table_scenarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('scenario_key');
            $table->string('label');
            $table->string('status')->default('Hypothese')->comment('Hypothese|In Pruefung|Beschlossen|Verworfen');
            $table->text('description')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('equity_instruments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shareholder_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cap_table_scenario_id')->nullable()->constrained()->nullOnDelete();
            $table->string('instrument_type')->comment('stammkapital|anteile|wandeldarlehen|virtuelle_beteiligung');
            $table->decimal('nominal_amount', 19, 4)->nullable();
            $table->decimal('percentage', 6, 3)->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->date('valid_from')->nullable();
            $table->string('status')->default('Hypothese')->comment('Hypothese|In Pruefung|Beschlossen|Ersetzt');
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('related_parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('relation_type')->comment('organ|gesellschafter|angehoeriger|sonstige_nahestehende_person');
            $table->text('description')->nullable();
            $table->string('conflict_status')->default('keiner')->comment('keiner|zu_pruefen|bestaetigt_mit_massnahme');
            $table->text('mitigation')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('outsourcing_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('service');
            $table->string('provider');
            $table->string('data_access')->comment('keine|personenbezogen|finanzdaten|gesundheitsdaten');
            $table->string('criticality')->default('niedrig')->comment('niedrig|mittel|hoch');
            $table->string('contract_reference')->nullable();
            $table->text('exit_plan')->nullable();
            $table->boolean('audit_right')->default(false);
            $table->boolean('dora_relevant')->default(false);
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outsourcing_registrations');
        Schema::dropIfExists('related_parties');
        Schema::dropIfExists('equity_instruments');
        Schema::dropIfExists('cap_table_scenarios');
        Schema::dropIfExists('shareholders');
    }
};
