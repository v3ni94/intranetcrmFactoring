<?php

namespace App\Support;

class RoleCatalog
{
    public const ROLES = [
        'kunde_admin' => 'Kunde – Administrator',
        'kunde_sachbearbeitung' => 'Kunde – Sachbearbeitung',
        'investor' => 'Investor / finanzierende Bank',
        'beirat' => 'Beirat / Aufsichtsrat',
        'vertrieb_crm' => 'Vertrieb / CRM',
        'operations' => 'Operations / Factoring-Sachbearbeitung',
        'kredit_risiko' => 'Kredit / Risiko',
        'debitorenbuchhaltung' => 'Debitorenbuchhaltung / Collections',
        'treasury_finance' => 'Treasury / Finance',
        'compliance' => 'Compliance / Geldwäsche / Datenschutz',
        'geschaeftsleitung' => 'Geschäftsleitung / Vorstand',
        'systemadministration' => 'Systemadministration',
        'superadmin_demo' => 'Superadmin (Demo-Steuerung)',
    ];

    public const EXTERNAL_ROLES = ['kunde_admin', 'kunde_sachbearbeitung', 'investor', 'beirat'];

    public const INTERNAL_ROLES = [
        'vertrieb_crm', 'operations', 'kredit_risiko', 'debitorenbuchhaltung',
        'treasury_finance', 'compliance', 'geschaeftsleitung', 'systemadministration', 'superadmin_demo',
    ];

    /** Rollen, deren Startseite eines der sechs Kern-Dashboards ist. */
    public const DASHBOARD_ROUTE = [
        'kunde_admin' => 'dashboard.kunde',
        'kunde_sachbearbeitung' => 'dashboard.kunde',
        'investor' => 'dashboard.investor',
        'beirat' => 'dashboard.beirat',
        'vertrieb_crm' => 'dashboard.mitarbeiter',
        'operations' => 'dashboard.mitarbeiter',
        'kredit_risiko' => 'dashboard.risiko',
        'debitorenbuchhaltung' => 'dashboard.mitarbeiter',
        'treasury_finance' => 'dashboard.mitarbeiter',
        'compliance' => 'dashboard.risiko',
        'geschaeftsleitung' => 'dashboard.geschaeftsleitung',
        'systemadministration' => 'dashboard.mitarbeiter',
        'superadmin_demo' => 'dashboard.geschaeftsleitung',
    ];

    public static function label(string $slug): string
    {
        return self::ROLES[$slug] ?? $slug;
    }
}
