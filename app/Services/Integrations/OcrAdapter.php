<?php

namespace App\Services\Integrations;

use App\Models\Document;

class OcrAdapter extends IntegrationAdapter
{
    protected string $key = 'ocr';

    /**
     * Platzhalter fuer strukturierte Extraktion aus gescannten Dokumenten.
     * Liefert im Prototyp keine echten Werte, nur den Ablauf-/Statusnachweis.
     *
     * @return array<string, mixed>
     */
    public function extract(Document $document): array
    {
        $this->logSuccess(Document::class, $document->id, null, 'OCR-Platzhalter aufgerufen, kein produktiver OCR-Anbieter angebunden');

        return [];
    }
}
