<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ergebnis des Codereviews: DB-seitige Integritaet und Indizes.
 * - Genau ein Ankauf pro Forderung (Vier-Augen-Prozess setzt das voraus).
 * - Rechnungsnummer eindeutig je Kunde (Doppelankaufsrisiko).
 * - Indizes fuer die haeufigsten Filter (Status, Faelligkeit).
 * - Replay-Schutz fuer TOTP (letzter akzeptierter Zeitschritt).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('two_factor_last_otp_at')->nullable()->after('two_factor_confirmed_at');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->unique('receivable_id');
            $table->index('status');
        });

        Schema::table('receivables', function (Blueprint $table) {
            $table->unique(['organization_id', 'invoice_number']);
            $table->index('due_date');
        });

        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('receivables', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'invoice_number']);
            $table->dropIndex(['due_date']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropUnique(['receivable_id']);
            $table->dropIndex(['status']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('two_factor_last_otp_at');
        });
    }
};
