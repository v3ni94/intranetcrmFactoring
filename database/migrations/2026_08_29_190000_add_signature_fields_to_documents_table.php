<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * v3.03: Einfache elektronische Signatur fuer im System erzeugte Mustervertraege.
 * Beide Seiten (Gesellschaft und Gegenseite) bestaetigen mit Namenszug und
 * Zeitstempel; die Metadaten werden zusaetzlich in den PDF-Signaturblock gerendert.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('signed_company_name')->nullable()->after('released_by');
            $table->timestamp('signed_company_at')->nullable()->after('signed_company_name');
            $table->foreignId('signed_company_by')->nullable()->after('signed_company_at')
                ->constrained('users')->nullOnDelete();
            $table->string('signed_counterparty_name')->nullable()->after('signed_company_by');
            $table->timestamp('signed_counterparty_at')->nullable()->after('signed_counterparty_name');
            $table->foreignId('signed_counterparty_by')->nullable()->after('signed_counterparty_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('signed_company_by');
            $table->dropConstrainedForeignId('signed_counterparty_by');
            $table->dropColumn([
                'signed_company_name', 'signed_company_at',
                'signed_counterparty_name', 'signed_counterparty_at',
            ]);
        });
    }
};
