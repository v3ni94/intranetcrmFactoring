<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_role')->nullable();
            $table->string('action')->comment('view|create|update|approve|reject|export|delete');
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('session_id')->nullable();
            $table->string('previous_hash', 64)->nullable();
            $table->string('hash', 64)->nullable()->comment('Hash-Verkettung fuer Manipulationserschwerung');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('subject_type')->comment('Contract, Purchase, PayoutBatch, CreditLine, Organization, ...');
            $table->unsignedBigInteger('subject_id');
            $table->string('action')->comment('kundenaktivierung|kreditentscheidung|limitaenderung|auszahlung|bankdatei|manuelle_buchung|stammdatenaenderung');
            $table->string('status')->default('offen')->comment('offen|freigegeben|abgelehnt');
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });

        Schema::create('decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('decision_id')->unique()->comment('z.B. DEC-2026-001');
            $table->string('title');
            $table->string('status')->default('Hypothese')->comment('Hypothese|In Pruefung|Externer Rat erforderlich|Beschlossen|Verworfen|Ersetzt');
            $table->date('decision_date')->nullable();
            $table->text('participants')->nullable();
            $table->text('preconditions')->nullable();
            $table->foreignId('replaces_decision_id')->nullable()->constrained('decisions')->nullOnDelete();
            $table->string('owner')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('financial_scenarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('scenario_key')->comment('konservativ|base|wachstum|stress');
            $table->string('label');
            $table->decimal('portfolio_year1_eur', 19, 4);
            $table->decimal('growth_yoy_percent', 5, 2);
            $table->decimal('factoring_fee_percent', 5, 3);
            $table->unsignedSmallInteger('dso_days');
            $table->decimal('advance_rate_percent', 5, 2);
            $table->decimal('risk_cost_percent', 5, 3);
            $table->decimal('debt_interest_percent', 5, 3);
            $table->decimal('customer_interest_percent', 5, 3);
            $table->decimal('opex_factor', 4, 2);
            $table->string('source_document')->nullable()->comment('Finanzmodell V1 vom 19.08.2026, Hypothese');
            $table->string('status')->default('Hypothese');
            $table->boolean('is_demo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_scenarios');
        Schema::dropIfExists('decisions');
        Schema::dropIfExists('approval_requests');
        Schema::dropIfExists('audit_events');
    }
};
