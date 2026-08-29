<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Document;
use App\Models\Facility;
use App\Models\Organization;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Storage;

/**
 * Mustervertraege (v3.03): erzeugt Factoring- und Fazilitaetsvertraege als PDF
 * direkt aus den Systemdaten, legt sie als Dokument ab und rendert bei jeder
 * Signatur den aktualisierten Signaturblock neu. Alle Dokumente sind als
 * MUSTER/ENTWURF gekennzeichnet und ersetzen keine anwaltliche Pruefung.
 */
class ContractTemplateService
{
    public function buildCustomerContract(Contract $contract, int $ownerId, ?Document $existing = null): Document
    {
        $org = $contract->organization;
        $product = $contract->factoringProduct;

        $recourse = str_contains((string) $product?->recourse_type, 'unecht')
            ? 'Unechtes Factoring: Das Ausfallrisiko verbleibt beim Anschlusskunden (Rückgriff gemäß § 6).'
            : 'Echtes Factoring: Die Factorin übernimmt das Ausfallrisiko (Delkredere) nach Maßgabe von § 6.';
        $disclosure = ($product?->disclosure_type ?? 'offen') === 'offen'
            ? 'Offenes Verfahren: Die Debitoren werden über die Abtretung informiert.'
            : 'Stilles Verfahren: Eine Information der Debitoren erfolgt nur in den vertraglich geregelten Fällen.';

        $sections = [
            ['§ 1 Vertragsgegenstand', 'Die Factorin kauft laufend Geldforderungen des Anschlusskunden aus erbrachten Leistungen gegenüber seinen Debitoren an. Grundlage sind die in § 3 vereinbarten Konditionen sowie die Ankaufs- und Auszahlungslinien nach § 4.'],
            ['§ 2 Abtretung', 'Der Anschlusskunde tritt die angekauften Forderungen einschließlich Nebenrechten an die Factorin ab (Globalzession im vereinbarten Umfang). '.$disclosure.' Bei Forderungen gegenüber Verbrauchern (B2C) werden die zusätzlichen gesetzlichen Anforderungen beachtet.'],
            ['§ 3 Konditionen', sprintf(
                'Bevorschussung: %s %% des Rechnungsbetrags; Sicherheitseinbehalt: %s %%. Factoringgebühr: %s %% zzgl. ratingabhängigem Aufschlag gemäß Konditionenblatt. Zins: Referenzzins %s %% p. a. zzgl. Marge %s %% p. a. (%s).',
                self::num($contract->advance_rate_percent), self::num($contract->reserve_percent),
                self::num($contract->factoring_fee_percent), self::num($contract->reference_rate_percent),
                self::num($contract->margin_percent), $contract->day_count_convention ?? 'act/360'
            )],
            ['§ 4 Linien und Limite', sprintf(
                'Ankaufslinie: %s EUR; Auszahlungslinie: %s EUR. Je Debitor gelten gesonderte Limite. Maximale Außenstandsdauer: %d Tage.',
                self::eur($contract->purchase_line), self::eur($contract->payout_line), (int) $contract->max_days_outstanding
            )],
            ['§ 5 Prüfung und Freigabe', 'Jede Forderung durchläuft die formale Prüfung, die automatische Risiko-/Limitprüfung und die Freigabe im Vier-Augen-Prinzip. Ablehnungen können über das Zweitvotum-Verfahren (Marktfolge, Vorstand) eskaliert werden.'],
            ['§ 6 Risikotragung', $recourse.sprintf(' Rückgriffsfrist: %d Tage.', (int) $contract->recourse_period_days)],
            ['§ 7 Laufzeit und Kündigung', sprintf(
                'Vertragsbeginn: %s. Laufzeit: %d Monate, Verlängerung um jeweils 12 Monate, sofern nicht mit einer Frist von %d Tagen gekündigt wird. Das Recht zur außerordentlichen Kündigung aus wichtigem Grund bleibt unberührt.',
                optional($contract->start_date)->format('d.m.Y') ?? '—', (int) $contract->term_months, (int) $contract->notice_period_days
            )],
            ['§ 8 Datenschutz und Verschwiegenheit', 'Die Parteien verarbeiten personenbezogene Daten ausschließlich nach DSGVO. Gesundheitsbezogene Angaben werden pseudonymisiert verarbeitet; Berufsgeheimnisse (§ 203 StGB) werden gewahrt. Einzelheiten regelt die Datenschutzvereinbarung.'],
            ['§ 9 Schlussbestimmungen', 'Änderungen bedürfen der Textform. Sollten einzelne Bestimmungen unwirksam sein, bleibt der Vertrag im Übrigen wirksam. Gerichtsstand ist der Sitz der Factorin. ENTWURF: Dieses Muster ist vor Verwendung durch Rechtsanwalt/Steuerberater zu prüfen.'],
        ];

        return $this->render(
            $existing,
            'Factoringvertrag (Muster) '.$contract->contract_number,
            'Factoringvertrag – MUSTER / ENTWURF',
            'zwischen der Aurevia Factoring AG (Arbeitsname, in Gründung) – nachfolgend „Factorin" –'
                ."\nund ".$org->name.($org->city ? ', '.trim(($org->street ? $org->street.', ' : '').($org->zip ? $org->zip.' ' : '').$org->city) : '')
                .' – nachfolgend „Anschlusskunde" –',
            $sections,
            $org->id,
            $ownerId,
            (bool) $contract->is_demo
        );
    }

    public function buildInvestorContract(Facility $facility, int $ownerId, ?Document $existing = null): Document
    {
        $org = $facility->investorOrganization;

        $termination = $facility->early_termination_right
            ? sprintf('Dem Kapitalgeber steht ein Sonderkündigungsrecht mit einer Frist von %d Tagen zu.', (int) ($facility->termination_notice_days ?? 90))
            : 'Ein Sonderkündigungsrecht ist nicht vereinbart; die ordentliche Kündigung richtet sich nach § 5.';

        $sections = [
            ['§ 1 Fazilität', sprintf(
                'Der Kapitalgeber stellt der Gesellschaft eine %s Fazilität über %s EUR zur Refinanzierung des Forderungsankaufs zur Verfügung (Fazilität %s).',
                $facility->seniority === 'senior' ? 'vorrangige (senior)' : 'nachrangige', self::eur($facility->commitment_amount), $facility->facility_number
            )],
            ['§ 2 Verzinsung und Ausschüttung', sprintf(
                'Die Verzinsung beträgt %s %% p. a. auf das jeweils gezogene Kapital, zahlbar monatlich nachschüssig. Bereitstellungsprovision auf nicht gezogene Beträge: %s %% p. a.',
                self::num($facility->interest_rate_percent), self::num($facility->commitment_fee_percent)
            )],
            ['§ 3 Verwendung und Abwicklung', 'Die Mittel werden ausschließlich für den Ankauf geprüfter Forderungen sowie zugehörige Abwicklungskosten verwendet. Die Abwicklung erfolgt über getrennte Abwicklungskonten; der Kapitalgeber erhält monatliches Reporting (Portfolio, Auslastung, Zahlungen).'],
            ['§ 4 Laufzeit', sprintf(
                'Beginn: %s; Endfälligkeit: %s. Rückführung des gezogenen Kapitals bei Endfälligkeit, vorbehaltlich § 5.',
                optional($facility->start_date)->format('d.m.Y') ?? '—', optional($facility->maturity_date)->format('d.m.Y') ?? '—'
            )],
            ['§ 5 Kündigung', 'Die ordentliche Kündigung ist zum Laufzeitende möglich. '.$termination.' Im Fall der Insolvenz einer Partei gelten die gesetzlichen Regelungen; Kündigungen werden mit Grund und Zeitstempel im System protokolliert.'],
            ['§ 6 Informationsrechte', 'Der Kapitalgeber erhält Zugang zum Investorenportal (eigenes Investment, Auslastung, Zinszahlungen, gekennzeichnete Modellrechnungen). Operative Kundendaten sind vom Zugriff ausgenommen (Datenschutz, Berufsgeheimnisse).'],
            ['§ 7 Schlussbestimmungen', 'Änderungen bedürfen der Textform. Gerichtsstand ist der Sitz der Gesellschaft. ENTWURF: Dieses Muster ist vor Verwendung durch Rechtsanwalt/Steuerberater zu prüfen; Renditeangaben sind Modellgrößen und keine Zusage.'],
        ];

        return $this->render(
            $existing,
            'Fazilitätsvertrag (Muster) '.$facility->facility_number,
            'Fazilitäts- und Beteiligungsvertrag – MUSTER / ENTWURF',
            'zwischen der Aurevia Factoring AG (Arbeitsname, in Gründung) – nachfolgend „Gesellschaft" –'
                ."\nund ".$org->name.($org->city ? ', '.$org->city : '').' – nachfolgend „Kapitalgeber" –',
            $sections,
            $org->id,
            $ownerId,
            (bool) $facility->is_demo
        );
    }

    /**
     * Rendert das PDF neu (z. B. nach einer Signatur). Die Quelle wird ueber die
     * eindeutige Vertrags- bzw. Fazilitaetsnummer im Titel aufgeloest.
     */
    public function refresh(Document $document, int $ownerId): Document
    {
        $number = trim((string) str($document->title)->after('(Muster) '));

        if (str_contains($document->title, 'Fazilitätsvertrag')) {
            $facility = Facility::where('facility_number', $number)->first();

            return $facility ? $this->buildInvestorContract($facility, $ownerId, $document) : $document;
        }

        $contract = Contract::where('contract_number', $number)->first();

        return $contract ? $this->buildCustomerContract($contract, $ownerId, $document) : $document;
    }

    /**
     * @param  array<int, array{0:string,1:string}>  $sections
     */
    private function render(?Document $existing, string $title, string $heading, string $parties, array $sections, int $organizationId, int $ownerId, bool $isDemo): Document
    {
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('Aurevia Intranet');
        $pdf->SetTitle($title);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(20, 20, 20);
        $pdf->SetAutoPageBreak(true, 22);
        $pdf->AddPage();

        // Kopf im Aurevia-Design
        $pdf->SetFillColor(14, 42, 71);
        $pdf->Rect(0, 0, 210, 26, 'F');
        $pdf->SetTextColor(245, 242, 235);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetXY(20, 8);
        $pdf->Cell(0, 6, 'AUREVIA FACTORING', 0, 1);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetX(20);
        $pdf->Cell(0, 4, 'Aurevia Factoring AG (Arbeitsname) · Projektgesellschaft in Vorbereitung · Ein Produkt der Müller Holding AG', 0, 1);

        $pdf->SetTextColor(30, 30, 30);
        $pdf->SetY(34);
        $pdf->SetFont('helvetica', 'B', 13);
        $pdf->MultiCell(0, 6, $heading, 0, 'L');
        $pdf->Ln(1);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(0, 5, $parties, 0, 'L');
        $pdf->Ln(3);

        foreach ($sections as [$sectionTitle, $body]) {
            $pdf->SetFont('helvetica', 'B', 10.5);
            $pdf->SetTextColor(14, 42, 71);
            $pdf->MultiCell(0, 5.5, $sectionTitle, 0, 'L');
            $pdf->SetFont('helvetica', '', 10);
            $pdf->SetTextColor(30, 30, 30);
            $pdf->MultiCell(0, 5, $body, 0, 'J');
            $pdf->Ln(2);
        }

        // Signaturblock
        $pdf->Ln(4);
        $pdf->SetFont('helvetica', 'B', 10.5);
        $pdf->SetTextColor(14, 42, 71);
        $pdf->MultiCell(0, 5.5, 'Unterschriften (einfache elektronische Signatur im System)', 0, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(30, 30, 30);

        $company = $existing && $existing->signed_company_at
            ? sprintf('%s · elektronisch bestätigt am %s', $existing->signed_company_name, $existing->signed_company_at->format('d.m.Y H:i'))
            : '________________________ (Name, Datum)';
        $counterparty = $existing && $existing->signed_counterparty_at
            ? sprintf('%s · elektronisch bestätigt am %s', $existing->signed_counterparty_name, $existing->signed_counterparty_at->format('d.m.Y H:i'))
            : '________________________ (Name, Datum)';

        $pdf->Ln(2);
        $pdf->MultiCell(0, 5, 'Für die Gesellschaft: '.$company, 0, 'L');
        $pdf->Ln(2);
        $pdf->MultiCell(0, 5, 'Für die Gegenseite: '.$counterparty, 0, 'L');

        $pdf->Ln(6);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(138, 148, 160);
        $pdf->MultiCell(0, 4, 'MUSTER/ENTWURF – erzeugt über das Aurevia Intranet am '.now()->format('d.m.Y H:i').' – vor Verwendung rechtlich prüfen. Die einfache elektronische Signatur dokumentiert die Zustimmung im System und ersetzt keine qualifizierte elektronische Signatur.', 0, 'L');

        $path = 'documents/'.uniqid('vertrag-').'.pdf';
        Storage::disk('local')->put($path, $pdf->Output('', 'S'));

        if ($existing) {
            // Alte Datei ersetzen, Version hochzaehlen
            if ($existing->storage_path && Storage::disk('local')->exists($existing->storage_path)) {
                Storage::disk('local')->delete($existing->storage_path);
            }
            $existing->update(['storage_path' => $path, 'version' => $existing->version + 1]);

            return $existing->refresh();
        }

        return Document::create([
            'tenant_id' => TenantContext::id(),
            'title' => $title,
            'category' => 'vertrag',
            'related_type' => Organization::class,
            'related_id' => $organizationId,
            'version' => 1,
            'storage_path' => $path,
            'visibility' => 'intern',
            'owner_id' => $ownerId,
            'is_demo' => $isDemo,
        ]);
    }

    private static function num($value): string
    {
        return number_format((float) $value, 2, ',', '.');
    }

    private static function eur($value): string
    {
        return number_format((float) $value, 2, ',', '.');
    }
}
