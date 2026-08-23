<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('company_name');
            $table->string('specialty')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('source')->nullable()->comment('Kampagne/Kanal/Empfehlungsgeber');
            $table->string('status')->default('Lead')->comment('Lead|Qualifiziert|Antrag begonnen|Unterlagen fehlen|KYC/KYB laeuft|Kreditanalyse|Angebot|Vertrag/Freigabe|Technische Einrichtung|Aktiv|Verloren');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->decimal('expected_volume', 19, 4)->nullable()->comment('Erwartetes Factoringvolumen p.a.');
            $table->unsignedTinyInteger('probability_percent')->default(20);
            $table->string('stage')->default('Qualifizierung')->comment('Qualifizierung|Bedarfsanalyse|Angebot|Verhandlung|Gewonnen|Verloren');
            $table->date('expected_close_date')->nullable();
            $table->string('next_action')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('crm_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->comment('email|telefon|meeting|notiz');
            $table->string('subject');
            $table->text('body')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('occurred_at')->useCurrent();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('related_type')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->string('status')->default('offen')->comment('offen|in_bearbeitung|erledigt');
            $table->string('priority')->default('normal')->comment('niedrig|normal|hoch');
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('crm_activities');
        Schema::dropIfExists('opportunities');
        Schema::dropIfExists('leads');
    }
};
