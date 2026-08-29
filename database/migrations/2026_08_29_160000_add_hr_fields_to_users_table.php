<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * v3.02: Personalakte fuer Benutzer (HR-Stammdaten, Nachweise, Ein-/Austritt).
 * Sensible Kennungen (Steuer-ID, Ausweisnummer) werden verschluesselt gespeichert
 * (encrypted-Cast im Model). Zugriff ausschliesslich Benutzerverwaltung.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Organisation / Berichtslinien
            $table->string('position')->nullable()->after('company_name');
            $table->string('department')->nullable()->after('position');
            $table->foreignId('supervisor_id')->nullable()->after('department')
                ->constrained('users')->nullOnDelete()->comment('fachlicher Vorgesetzter');
            $table->foreignId('disciplinary_supervisor_id')->nullable()->after('supervisor_id')
                ->constrained('users')->nullOnDelete()->comment('disziplinarischer Vorgesetzter');

            // Kontakt
            $table->string('phone_business')->nullable()->after('disciplinary_supervisor_id');
            $table->string('phone_private')->nullable()->after('phone_business');
            $table->string('email_private')->nullable()->after('phone_private');

            // Adresse / Person
            $table->string('street')->nullable()->after('email_private');
            $table->string('zip', 10)->nullable()->after('street');
            $table->string('city')->nullable()->after('zip');
            $table->string('country', 2)->default('DE')->after('city');
            $table->date('birth_date')->nullable()->after('country');

            // Sensible Kennungen (verschluesselt via Model-Cast)
            $table->text('tax_id')->nullable()->after('birth_date')->comment('Steuer-ID, verschluesselt');
            $table->text('id_card_number')->nullable()->after('tax_id')->comment('Personalausweisnr., verschluesselt');
            $table->date('id_card_valid_until')->nullable()->after('id_card_number');

            // Nachweise (Datumsangaben, Originale in der Personalakte/DMS)
            $table->date('criminal_record_check_at')->nullable()->after('id_card_valid_until')->comment('Fuehrungszeugnis vorgelegt am');
            $table->string('drivers_license_class')->nullable()->after('criminal_record_check_at');
            $table->date('drivers_license_valid_until')->nullable()->after('drivers_license_class');
            $table->date('schufa_check_at')->nullable()->after('drivers_license_valid_until')->comment('SCHUFA-Auskunft vorgelegt am');
            $table->text('hr_notes')->nullable()->after('schufa_check_at');

            // Beschaeftigungszeitraum: aktiv erst ab Eintritt, gesperrt nach Austritt
            $table->date('joined_at')->nullable()->after('hr_notes');
            $table->date('left_at')->nullable()->after('joined_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supervisor_id');
            $table->dropConstrainedForeignId('disciplinary_supervisor_id');
            $table->dropColumn([
                'position', 'department', 'phone_business', 'phone_private', 'email_private',
                'street', 'zip', 'city', 'country', 'birth_date', 'tax_id', 'id_card_number',
                'id_card_valid_until', 'criminal_record_check_at', 'drivers_license_class',
                'drivers_license_valid_until', 'schufa_check_at', 'hr_notes', 'joined_at', 'left_at',
            ]);
        });
    }
};
