<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['production', 'demo'])->default('demo');
            $table->boolean('is_demo')->default(true);
            $table->string('demo_seed_id')->nullable();
            $table->timestamps();
        });

        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->enum('org_type', ['customer', 'debtor', 'investor', 'partner'])->index();
            $table->string('name');
            $table->string('legal_form')->nullable();
            $table->string('register_number')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('specialty')->nullable()->comment('Fachrichtung, z.B. Zahnarztpraxis, Apotheke');
            $table->string('street')->nullable();
            $table->string('zip')->nullable();
            $table->string('city')->nullable();
            $table->string('country', 2)->default('DE');
            $table->string('iban_masked')->nullable();
            $table->string('customer_status')->default('lead')->comment('Statusfolge Onboarding');
            $table->string('risk_class')->nullable()->comment('niedrig|mittel|hoch');
            $table->string('pseudonym_id')->nullable()->comment('z.B. DEB-DEMO-000123 fuer private Rechnungsempfaenger');
            $table->foreignId('account_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('salutation')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('role')->nullable()->comment('z.B. Geschaeftsfuehrer, Praxismanager');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_authorized_representative')->default(false);
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('beneficial_owners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->decimal('ownership_percent', 5, 2)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('nationality')->nullable();
            $table->boolean('pep_status')->default(false);
            $table->boolean('sanctions_hit')->default(false);
            $table->timestamp('screened_at')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficial_owners');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('organizations');
        Schema::dropIfExists('tenants');
    }
};
