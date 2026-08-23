<?php

namespace App\Services;

use App\Models\Document;
use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * Automatisches Wasserzeichen (Status, Version, Empfaenger) beim Dokumentenexport,
 * siehe Abschnitt 14 „automatische Wasserzeichen mit Status, Version und Empfaenger".
 * Wird ausschliesslich fuer PDF-Dateien angewendet; andere Dateitypen werden
 * unveraendert ausgeliefert (dokumentierte Einschraenkung, siehe README).
 */
class WatermarkService
{
    public function isSupported(string $absolutePath): bool
    {
        return strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION)) === 'pdf';
    }

    /**
     * Erzeugt eine gestempelte Kopie der PDF-Datei und gibt deren Pfad zurueck.
     * Der Aufrufer ist fuer das Loeschen der temporaeren Datei verantwortlich.
     */
    public function stamp(string $absoluteSourcePath, Document $document, string $recipientName): string
    {
        $pdf = new Fpdi;
        $pageCount = $pdf->setSourceFile($absoluteSourcePath);

        $label = sprintf(
            '%s · Version %d · Empfänger: %s · %s',
            str_replace('_', ' ', $document->visibility),
            $document->version,
            $recipientName,
            now()->format('d.m.Y H:i')
        );

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);

            // Diagonales, halbtransparentes Wasserzeichen ueber die gesamte Seite.
            $pdf->SetAlpha(0.35);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetTextColor(180, 60, 60);
            $pdf->StartTransform();
            $pdf->Rotate(45, $size['width'] / 2, $size['height'] / 2);
            $pdf->SetXY(0, $size['height'] / 2 - 5);
            $pdf->Cell($size['width'], 10, $label, 0, 0, 'C');
            $pdf->StopTransform();
            $pdf->SetAlpha(1);
        }

        $outputPath = tempnam(sys_get_temp_dir(), 'aurevia-wm-').'.pdf';
        $pdf->Output($outputPath, 'F');

        return $outputPath;
    }
}
