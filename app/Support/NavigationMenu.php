<?php

namespace App\Support;

class NavigationMenu
{
    /**
     * @return array<int, array{label:string, route:string, roles:array<string>|null}>
     */
    public static function items(): array
    {
        return [
            ['label' => 'Start', 'route' => 'dashboard', 'roles' => null],
            ['label' => 'CRM / Vertrieb', 'route' => 'crm.leads.index', 'roles' => ['vertrieb_crm', 'geschaeftsleitung', 'superadmin_demo']],
            ['label' => 'Kunden', 'route' => 'organizations.index', 'roles' => ['vertrieb_crm', 'operations', 'kredit_risiko', 'geschaeftsleitung', 'compliance', 'superadmin_demo']],
            ['label' => 'Debitoren', 'route' => 'debtors.index', 'roles' => ['operations', 'kredit_risiko', 'debitorenbuchhaltung', 'geschaeftsleitung', 'superadmin_demo']],
            ['label' => 'Onboarding', 'route' => 'onboarding.index', 'roles' => ['vertrieb_crm', 'operations', 'compliance', 'geschaeftsleitung', 'superadmin_demo']],
            ['label' => 'Kreditlinien & Limits', 'route' => 'credit-lines.index', 'roles' => ['kredit_risiko', 'geschaeftsleitung', 'superadmin_demo']],
            ['label' => 'Meine Forderungen', 'route' => 'customer.receivables.index', 'roles' => ['kunde_admin', 'kunde_sachbearbeitung']],
            ['label' => 'Forderungen', 'route' => 'receivables.index', 'roles' => ['operations', 'kredit_risiko', 'debitorenbuchhaltung', 'geschaeftsleitung', 'superadmin_demo']],
            ['label' => 'Zahlungseingänge', 'route' => 'payments.index', 'roles' => ['treasury_finance', 'debitorenbuchhaltung', 'geschaeftsleitung', 'superadmin_demo']],
            ['label' => 'Mahnwesen & Streitfälle', 'route' => 'dunning.index', 'roles' => ['debitorenbuchhaltung', 'geschaeftsleitung', 'superadmin_demo']],
            ['label' => 'Treasury & Bankkonten', 'route' => 'treasury.bank-accounts.index', 'roles' => ['treasury_finance', 'geschaeftsleitung', 'superadmin_demo']],
            ['label' => 'Investoren & Fazilitäten', 'route' => 'facilities.index', 'roles' => ['treasury_finance', 'geschaeftsleitung', 'superadmin_demo']],
            ['label' => 'Meine Kapitalbeziehung', 'route' => 'investor.facilities.index', 'roles' => ['investor']],
            ['label' => 'Verträge & Dokumente', 'route' => 'documents.index', 'roles' => null],
            ['label' => 'Aufgaben', 'route' => 'tasks.index', 'roles' => ['vertrieb_crm', 'operations', 'kredit_risiko', 'debitorenbuchhaltung', 'treasury_finance', 'compliance', 'geschaeftsleitung', 'superadmin_demo']],
            ['label' => 'Risiko & Compliance', 'route' => 'risk.index', 'roles' => ['kredit_risiko', 'compliance', 'geschaeftsleitung', 'superadmin_demo']],
            ['label' => 'Reporting', 'route' => 'reports.index', 'roles' => ['geschaeftsleitung', 'treasury_finance', 'superadmin_demo']],
            ['label' => 'Audit & Freigaben', 'route' => 'audit.index', 'roles' => ['compliance', 'geschaeftsleitung', 'systemadministration', 'superadmin_demo']],
            ['label' => 'Integrationen', 'route' => 'integrations.index', 'roles' => ['systemadministration', 'geschaeftsleitung', 'superadmin_demo']],
            ['label' => 'Projekt & Beschlüsse', 'route' => 'governance.index', 'roles' => ['geschaeftsleitung', 'superadmin_demo']],
            ['label' => 'Cap-Table & Register', 'route' => 'captable.index', 'roles' => ['geschaeftsleitung', 'superadmin_demo']],
            ['label' => 'Demo-Steuerung', 'route' => 'demo.index', 'roles' => ['superadmin_demo']],
            ['label' => 'Einstellungen', 'route' => 'profile.edit', 'roles' => null],
        ];
    }

    public static function forUser($user): array
    {
        $roles = $user->getRoleNames()->all();

        return array_values(array_filter(self::items(), function ($item) use ($roles) {
            if ($item['roles'] === null) {
                return true;
            }

            return count(array_intersect($item['roles'], $roles)) > 0;
        }));
    }
}
