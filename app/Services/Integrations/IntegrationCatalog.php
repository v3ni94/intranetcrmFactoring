<?php

namespace App\Services\Integrations;

/**
 * Registrierbare Adapter-Kategorien gemaess Abschnitt 20. Jeder Eintrag ist ein
 * austauschbarer Provider-Slot, kein fest verdrahteter Anbieter.
 */
class IntegrationCatalog
{
    public const PROVIDERS = [
        'bank' => ['category' => 'Bank/EBICS/PSD2', 'name' => 'Dateibasierter Bank-Adapter (Demo)'],
        'kyc_kyb' => ['category' => 'KYC/KYB/Identifikation', 'name' => 'Manuelle KYC/KYB-Pruefung (Sandbox)'],
        'pep_sanctions' => ['category' => 'PEP/Sanktionslisten', 'name' => 'Manuelles PEP-/Sanktionsscreening (Sandbox)'],
        'register_ubo' => ['category' => 'Handelsregister/UBO', 'name' => 'Manueller Registerabgleich (Sandbox)'],
        'credit_bureau' => ['category' => 'Wirtschaftsauskunftei/Kreditversicherung', 'name' => 'Manuelle Bonitaetspruefung (Sandbox)'],
        'esignature' => ['category' => 'E-Signatur', 'name' => 'Manuelle Signatur-Erfassung (Demo)'],
        'praxis_import' => ['category' => 'Praxis-/Abrechnungssystem', 'name' => 'Dateiupload-Import (Demo)'],
        'datev' => ['category' => 'DATEV/ERP', 'name' => 'DATEV-Demo-Export (CSV)'],
        'ocr' => ['category' => 'Dokumenten-OCR', 'name' => 'OCR-Platzhalter (Sandbox)'],
        'collections' => ['category' => 'Inkasso/Rechtsdienstleister', 'name' => 'Manuelle Inkasso-Uebergabe (Demo)'],
        'bi_export' => ['category' => 'BI/Data Warehouse', 'name' => 'CSV-Exportschnittstelle (Demo)'],
    ];
}
