<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('account_name');
            $table->string('bank_name')->comment('z.B. Medizinbank AG - Demo (fiktiv)');
            $table->string('iban_masked');
            $table->string('bic')->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->decimal('balance_amount', 19, 4)->default(0);
            $table->string('purpose')->comment('betrieb|auszahlung|investorenkonto');
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('payout_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('batch_number')->unique();
            $table->foreignId('bank_account_id')->constrained()->restrictOnDelete();
            $table->decimal('total_amount', 19, 4)->default(0);
            $table->unsignedInteger('item_count')->default(0);
            $table->string('status')->default('erstellt')->comment('erstellt|freigegeben_1|freigegeben_2|angewiesen|bestaetigt|zurueckgewiesen');
            $table->string('sepa_export_reference')->nullable()->comment('pain.001 Demo-Dateiname');
            $table->foreignId('approved_by_first')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_second')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('executed_at')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payout_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_id')->constrained()->restrictOnDelete();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 19, 4);
            $table->string('idempotency_key')->unique();
            $table->string('status')->default('erstellt')->comment('erstellt|angewiesen|bestaetigt|zurueckgewiesen');
            $table->timestamp('confirmed_at')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
            $table->date('value_date');
            $table->decimal('amount', 19, 4)->comment('positiv Eingang, negativ Ausgang');
            $table->string('reference')->nullable()->comment('Verwendungszweck');
            $table->string('counterparty_name')->nullable();
            $table->string('import_source')->default('camt.053')->comment('camt.052|camt.053|camt.054|csv');
            $table->string('status')->default('offen')->comment('offen|zugeordnet|ignoriert');
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('receivable_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 19, 4);
            $table->string('type')->default('eingang')->comment('eingang|teilzahlung|sammelzahlung|ueberzahlung|rueckzahlung|unbekannt');
            $table->decimal('match_confidence_percent', 5, 2)->nullable();
            $table->string('match_reason')->nullable();
            $table->foreignId('matched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('matched_at')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('payout_batches');
        Schema::dropIfExists('bank_accounts');
    }
};
