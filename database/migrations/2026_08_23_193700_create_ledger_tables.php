<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('entry_number')->unique();
            $table->date('booking_date');
            $table->date('value_date')->nullable();
            $table->string('event_type')->comment('ankauf|auszahlung|sicherheitseinbehalt|gebuehr|zins|zahlungseingang|reservefreigabe|rueckgriff|ruecklastschrift|abschreibung|wiedererlangung|investorenziehung|investorenrueckzahlung|korrektur');
            $table->string('source_type')->nullable()->comment('Polymorpher Bezug, z.B. Purchase, Payment');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('reverses_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->string('account_code')->comment('Sachkontenmapping, konfigurierbar');
            $table->string('account_name');
            $table->decimal('debit_amount', 19, 4)->default(0);
            $table->decimal('credit_amount', 19, 4)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
    }
};
