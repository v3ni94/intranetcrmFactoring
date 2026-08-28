<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * v3.00: Rating (Kunde und Investor), Branchensegment, B2B/B2C-Kennzeichnung,
 * Sonderkuendigungsrechte fuer Fazilitaeten und Support-Ticketsystem.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            // Internes Rating AAA..C (siehe App\Support\RatingCatalog), gilt fuer
            // Kunden- UND Investor-Organisationen.
            $table->string('rating', 3)->nullable()->after('risk_class');
            $table->unsignedSmallInteger('rating_points')->nullable()->after('rating')->comment('0-100, hoeher = besser');
            $table->timestamp('rating_updated_at')->nullable()->after('rating_points');
            // Branchensegment innerhalb Medizin (arzt, zahnarzt, apotheke, ...)
            $table->string('segment')->nullable()->after('specialty');
            // b2b: gewerblicher Debitor/Kunde; b2c: Verbraucher (Abtretungsanzeige
            // und Verbraucherschutz beachten, siehe FAQ/Prozessleitfaden).
            $table->string('customer_type', 3)->default('b2b')->after('segment');
        });

        Schema::table('facilities', function (Blueprint $table) {
            $table->boolean('early_termination_right')->default(false)->after('covenants')
                ->comment('Sonderkuendigungsrecht des Investors vereinbart');
            $table->unsignedSmallInteger('termination_notice_days')->nullable()->after('early_termination_right');
            $table->timestamp('terminated_at')->nullable()->after('termination_notice_days');
            $table->string('termination_reason')->nullable()->after('terminated_at')
                ->comment('ordentlich|sonderkuendigung|insolvenz_investor');
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('ticket_number')->unique();
            $table->string('subject');
            $table->string('category')->default('frage')->comment('frage|problem|wunsch|kunde|investor|sonstiges');
            $table->string('status')->default('offen')->comment('offen|in_bearbeitung|beantwortet|geschlossen');
            $table->string('priority')->default('normal')->comment('niedrig|normal|hoch');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
            $table->index('status');
        });

        // Report-Abonnements: KPI-Report per E-Mail, manuell oder automatisch
        // (taeglich/woechentlich/monatlich) ueber den Scheduler.
        Schema::create('report_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('recipient_email');
            $table->string('report_type')->default('kpi_uebersicht')->comment('kpi_uebersicht');
            $table->string('frequency')->comment('taeglich|woechentlich|monatlich');
            $table->boolean('active')->default(true);
            $table->timestamp('last_sent_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        // Controlling: einfache Kostenerfassung fuer Deckungsbeitrags-/Kostensicht
        Schema::create('operating_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->date('cost_date');
            $table->string('category')->comment('personal|it|buero|versicherung|refinanzierung|beratung|marketing|sonstiges');
            $table->string('description');
            $table->decimal('amount', 19, 4);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
            $table->index('cost_date');
        });

        // Warenkreditversicherung je Kreditlinie (Klumpenrisiko-Steuerung):
        // Linien oberhalb der Schwelle (config aurevia.insurance_threshold) sollen
        // ganz oder teilweise versichert werden; monatliche Meldung an den
        // Versicherer ueber den vorbereiteten Adapter.
        Schema::table('credit_lines', function (Blueprint $table) {
            $table->decimal('insured_amount', 19, 4)->nullable()->after('used_amount');
            $table->string('insurer_name')->nullable()->after('insured_amount');
            $table->string('insurance_status')->default('nicht_versichert')->after('insurer_name')
                ->comment('nicht_versichert|beantragt|versichert|abgelehnt');
        });

        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->text('body');
            $table->boolean('is_internal_note')->default(false)->comment('Nur intern sichtbar');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('credit_lines', function (Blueprint $table) {
            $table->dropColumn(['insured_amount', 'insurer_name', 'insurance_status']);
        });
        Schema::dropIfExists('operating_costs');
        Schema::dropIfExists('report_subscriptions');
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('tickets');

        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn(['early_termination_right', 'termination_notice_days', 'terminated_at', 'termination_reason']);
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['rating', 'rating_points', 'rating_updated_at', 'segment', 'customer_type']);
        });
    }
};
