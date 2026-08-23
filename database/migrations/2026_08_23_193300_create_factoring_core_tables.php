<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factoring_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('recourse_type')->comment('echt_ohne_regress|unecht_mit_regress');
            $table->string('service_type')->comment('full_service|inhouse|maturity');
            $table->string('disclosure_type')->comment('offen|still');
            $table->string('scope_type')->comment('selektiv|gesamtumsatz');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('factoring_product_id')->constrained()->restrictOnDelete();
            $table->string('contract_number')->unique();
            $table->unsignedInteger('version')->default(1);
            $table->date('start_date')->nullable();
            $table->unsignedSmallInteger('term_months')->nullable();
            $table->unsignedSmallInteger('notice_period_days')->default(90);
            $table->string('status')->default('entwurf')->comment('entwurf|zur_freigabe|aktiv|gekuendigt|beendet');
            $table->decimal('purchase_line', 19, 4)->comment('Ankaufslinie');
            $table->decimal('payout_line', 19, 4)->comment('Auszahlungslinie');
            $table->decimal('advance_rate_percent', 5, 2)->default(85);
            $table->decimal('reserve_percent', 5, 2)->default(15);
            $table->decimal('factoring_fee_percent', 5, 3)->comment('Factoringgebuehr auf Ankauf');
            $table->decimal('service_fee_percent', 5, 3)->nullable();
            $table->decimal('minimum_fee', 19, 4)->nullable();
            $table->decimal('setup_fee', 19, 4)->nullable();
            $table->string('interest_basis')->default('variabel')->comment('variabel|fest');
            $table->decimal('reference_rate_percent', 5, 3)->nullable();
            $table->decimal('margin_percent', 5, 3)->nullable();
            $table->decimal('interest_floor_percent', 5, 3)->nullable();
            $table->decimal('interest_cap_percent', 5, 3)->nullable();
            $table->string('day_count_convention')->default('act/360');
            $table->unsignedSmallInteger('max_days_outstanding')->default(120);
            $table->unsignedSmallInteger('recourse_period_days')->default(90);
            $table->json('excluded_debtor_types')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('credit_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
            $table->string('line_type')->comment('ankauf|auszahlung|debitor|konzentration');
            $table->decimal('limit_amount', 19, 4);
            $table->decimal('used_amount', 19, 4)->default(0);
            $table->string('status')->default('aktiv')->comment('aktiv|ausgesetzt|abgelaufen');
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision_reason')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('debtor_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('debtor_organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('customer_organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->decimal('limit_amount', 19, 4);
            $table->decimal('used_amount', 19, 4)->default(0);
            $table->string('status')->default('aktiv')->comment('aktiv|abgelehnt|wiedervorlage');
            $table->date('valid_until')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debtor_limits');
        Schema::dropIfExists('credit_lines');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('factoring_products');
    }
};
