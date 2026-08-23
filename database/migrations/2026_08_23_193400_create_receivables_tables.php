<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receivables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('receivable_number')->unique()->comment('z.B. FRD-DEMO-000123');
            $table->foreignId('organization_id')->constrained()->restrictOnDelete()->comment('Kunde/Praxis');
            $table->foreignId('contract_id')->constrained()->restrictOnDelete();
            $table->foreignId('debtor_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->string('debtor_pseudonym_id')->nullable()->comment('z.B. PAT-DEMO-000471 fuer private Rechnungsempfaenger');
            $table->string('invoice_number');
            $table->date('invoice_date');
            $table->date('due_date');
            $table->decimal('invoice_amount', 19, 4);
            $table->string('currency', 3)->default('EUR');
            $table->string('status')->default('entwurf')->index()->comment(
                'entwurf|eingereicht|formale_pruefung|risiko_limitpruefung|rueckfrage|freigegeben|angekauft|zur_auszahlung|zahlung_angewiesen|ausgezahlt|teilbezahlt|bezahlt|abgerechnet|abgelehnt|zurueckgezogen|gesperrt|streitig|ueberfaellig|rueckgriff|ausgefallen|abgeschrieben|wieder_eingezogen'
            );
            $table->string('source_channel')->default('manuell')->comment('manuell|upload_pdf|upload_csv|upload_xlsx|upload_xml|api');
            $table->text('rejection_reason')->nullable();
            $table->string('triggered_rule')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('receivable_id')->constrained()->cascadeOnDelete();
            $table->decimal('nominal_amount', 19, 4);
            $table->decimal('purchasable_amount', 19, 4)->comment('Ankauffaehiger Betrag nach Abzuegen');
            $table->decimal('advance_rate_percent', 5, 2);
            $table->decimal('immediate_payout_amount', 19, 4);
            $table->decimal('reserve_amount', 19, 4);
            $table->decimal('factoring_fee_amount', 19, 4);
            $table->decimal('expected_interest_amount', 19, 4)->nullable();
            $table->decimal('deductions_amount', 19, 4)->default(0);
            $table->text('deduction_reason')->nullable();
            $table->string('status')->default('berechnet')->comment('berechnet|freigegeben|storniert');
            $table->foreignId('approved_by_first')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_second')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('purchased_at')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('receivables');
    }
};
