<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * v3.04: Nachweis-Dokumente in der Personalakte (Personalausweis, Fuehrerschein,
 * SCHUFA-Auskunft, Fuehrungszeugnis, Sonstiges) als geschuetzte Datei-Uploads.
 * Dateien liegen unter storage/app (nicht oeffentlich), Auslieferung nur ueber
 * die Anwendung mit Rollenpruefung.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('doc_type')->comment('personalausweis|fuehrerschein|schufa|fuehrungszeugnis|sonstiges');
            $table->string('original_name');
            $table->string('storage_path');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'doc_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_documents');
    }
};
