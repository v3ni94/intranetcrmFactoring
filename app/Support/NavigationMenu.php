<?php

namespace App\Support;

/**
 * Gruppierte Hauptnavigation (v3.00): Ueberschriften buendeln die Menuepunkte
 * nach Arbeitsbereich. Sichtbarkeit ist reine Anzeige-Logik — die eigentliche
 * Zugriffskontrolle erzwingen die Route-Middleware serverseitig.
 */
class NavigationMenu
{
    /**
     * @return array<int, array{heading: ?string, items: array<int, array{label:string, route:string, roles:array<string>|null}>}>
     */
    public static function groups(): array
    {
        $admin = ['systemadministration', 'geschaeftsleitung', 'superadmin_demo'];

        return [
            ['heading' => null, 'items' => [
                ['label' => 'Start', 'route' => 'dashboard', 'roles' => null],
                ['label' => 'Verträge & Dokumente', 'route' => 'documents.index', 'roles' => null],
                ['label' => 'Support', 'route' => 'tickets.index', 'roles' => null],
                ['label' => 'Hilfe & FAQ', 'route' => 'help.faq', 'roles' => null],
                ['label' => 'Einstellungen', 'route' => 'profile.edit', 'roles' => null],
            ]],
            ['heading' => 'Kundenportal', 'items' => [
                ['label' => 'Meine Forderungen', 'route' => 'customer.receivables.index', 'roles' => ['kunde_admin', 'kunde_sachbearbeitung']],
            ]],
            ['heading' => 'Investorenportal', 'items' => [
                ['label' => 'Meine Kapitalbeziehung', 'route' => 'investor.facilities.index', 'roles' => ['investor']],
            ]],
            ['heading' => 'Betrieb', 'items' => [
                ['label' => 'Forderungen', 'route' => 'receivables.index', 'roles' => ['operations', 'kredit_risiko', 'debitorenbuchhaltung', 'geschaeftsleitung', 'superadmin_demo']],
                ['label' => 'Ankauf & Auszahlungen', 'route' => 'payouts.index', 'roles' => ['operations', 'treasury_finance', 'geschaeftsleitung', 'superadmin_demo']],
                ['label' => 'Zahlungseingänge', 'route' => 'payments.index', 'roles' => ['treasury_finance', 'debitorenbuchhaltung', 'geschaeftsleitung', 'superadmin_demo']],
                ['label' => 'Mahnwesen & Streitfälle', 'route' => 'dunning.index', 'roles' => ['debitorenbuchhaltung', 'geschaeftsleitung', 'superadmin_demo']],
                ['label' => 'Aufgaben', 'route' => 'tasks.index', 'roles' => ['vertrieb_crm', 'operations', 'kredit_risiko', 'debitorenbuchhaltung', 'treasury_finance', 'compliance', 'controlling', 'geschaeftsleitung', 'superadmin_demo']],
            ]],
            ['heading' => 'Vertrieb & Kunden', 'items' => [
                ['label' => 'CRM / Vertrieb', 'route' => 'crm.leads.index', 'roles' => ['vertrieb_crm', 'geschaeftsleitung', 'superadmin_demo']],
                ['label' => 'Kunden', 'route' => 'organizations.index', 'roles' => ['vertrieb_crm', 'operations', 'kredit_risiko', 'geschaeftsleitung', 'compliance', 'superadmin_demo']],
                ['label' => 'Debitoren', 'route' => 'debtors.index', 'roles' => ['operations', 'kredit_risiko', 'debitorenbuchhaltung', 'geschaeftsleitung', 'superadmin_demo']],
                ['label' => 'Onboarding', 'route' => 'onboarding.index', 'roles' => ['vertrieb_crm', 'operations', 'compliance', 'geschaeftsleitung', 'superadmin_demo']],
            ]],
            ['heading' => 'Treasury & Finanzen', 'items' => [
                ['label' => 'Kreditlinien & Limits', 'route' => 'credit-lines.index', 'roles' => ['kredit_risiko', 'geschaeftsleitung', 'superadmin_demo']],
                ['label' => 'Treasury & Bankkonten', 'route' => 'treasury.bank-accounts.index', 'roles' => ['treasury_finance', 'geschaeftsleitung', 'superadmin_demo']],
                ['label' => 'Investoren & Fazilitäten', 'route' => 'facilities.index', 'roles' => ['treasury_finance', 'geschaeftsleitung', 'superadmin_demo']],
                ['label' => 'Controlling & Kosten', 'route' => 'costs.index', 'roles' => ['controlling', 'treasury_finance', 'geschaeftsleitung', 'superadmin_demo']],
            ]],
            ['heading' => 'Steuerung & Aufsicht', 'items' => [
                ['label' => 'Risiko & Compliance', 'route' => 'risk.index', 'roles' => ['kredit_risiko', 'compliance', 'geschaeftsleitung', 'superadmin_demo']],
                ['label' => 'Reporting', 'route' => 'reports.index', 'roles' => ['geschaeftsleitung', 'treasury_finance', 'controlling', 'superadmin_demo']],
                ['label' => 'Audit & Freigaben', 'route' => 'audit.index', 'roles' => ['compliance', 'geschaeftsleitung', 'systemadministration', 'superadmin_demo']],
                ['label' => 'Projekt & Beschlüsse', 'route' => 'governance.index', 'roles' => ['geschaeftsleitung', 'superadmin_demo']],
                ['label' => 'Cap-Table & Register', 'route' => 'captable.index', 'roles' => ['geschaeftsleitung', 'superadmin_demo']],
            ]],
            ['heading' => 'Administration', 'items' => [
                ['label' => 'Benutzer', 'route' => 'users.index', 'roles' => $admin],
                ['label' => 'Integrationen', 'route' => 'integrations.index', 'roles' => $admin],
                ['label' => 'Changelog', 'route' => 'help.changelog', 'roles' => array_merge($admin, ['compliance'])],
                ['label' => 'Demo-Steuerung', 'route' => 'demo.index', 'roles' => ['superadmin_demo']],
            ]],
        ];
    }

    /**
     * Gruppen mit den fuer die Rollen des Nutzers sichtbaren Eintraegen;
     * leere Gruppen entfallen.
     */
    public static function forUser($user): array
    {
        $roles = $user->getRoleNames()->all();

        $groups = [];
        foreach (self::groups() as $group) {
            $items = array_values(array_filter($group['items'], function ($item) use ($roles) {
                return $item['roles'] === null || count(array_intersect($item['roles'], $roles)) > 0;
            }));

            if ($items !== []) {
                $groups[] = ['heading' => $group['heading'], 'items' => $items];
            }
        }

        return $groups;
    }
}
