<?php

namespace App\Support;

/**
 * Chronologie aller Releases (v3.00): Version, Datum/Uhrzeit, Verantwortlicher
 * und Aenderungen. Wird auf der Changelog-Seite angezeigt. Neue Releases oben
 * ergaenzen und config('aurevia.version') mitziehen.
 */
class Changelog
{
    public const ENTRIES = [
        [
            'version' => '3.00',
            'date' => '29.08.2026 14:00',
            'author' => 'Timo Müller',
            'changes' => [
                'Internes Rating (AAA bis C) für Kunden und Investoren mit ratingabhängigem Gebührenaufschlag beim Ankauf',
                'Branchensegmente Medizin (Arzt, Zahnarzt, Apotheke, Dentallabor, Tierarzt, Heilberufe, Pflege, MVZ/Klinik) und B2B/B2C-Kennzeichnung',
                'Fazilitäten: Sonderkündigungsrecht, Kündigungsfrist, Kündigung mit Grund (ordentlich, Sonderkündigung, Insolvenz des Investors)',
                'Support-Ticketsystem: Kunden und Investoren stellen Anfragen, interne Bearbeitung mit Status und internen Notizen',
                'Neue Rolle Controlling mit Kostenerfassung (Monats- und Kategoriesicht)',
                'Investor-Dashboard: Rendite-Übersicht und Anlage-Staffeln als gekennzeichnete Modellrechnung',
                'Warenkreditversicherung vorbereitet: Versicherungsfelder je Kreditlinie, Klumpenrisiko-Schwelle, Adapter für monatliche Linienmeldung',
                'Creditreform/SCHUFA als vorbereitete Auskunftei-Adapter benannt',
                'Navigation in Gruppen gegliedert, Hilfe & FAQ mit Prozessanleitungen, Changelog-Seite',
                'Footer: Ein Produkt der Müller Holding AG; Dokumentation: Benutzerhandbuch, Prozessleitfaden, BaFin-Vorbereitung, Datenschutzkonzept',
            ],
        ],
        [
            'version' => '2.1',
            'date' => '28.08.2026 23:30',
            'author' => 'Timo Müller',
            'changes' => [
                'Benutzerverwaltung: Anlegen aller Rollen, Kunden-Nutzer mit Organisationsbindung, Startpasswort mit Einmal-Anzeige, Deaktivieren mit Login-Sperre',
                '2FA-Einrichtung mit scanbarem QR-Code zusätzlich zum manuellen Schlüssel',
                'Sprachumschalter Deutsch/Englisch oben rechts',
                'Mobil-optimiertes Layout (Overlay-Navigation)',
                'Dashboard-Diagramme: Altersstruktur des Portfolios, Ankaufsvolumen',
            ],
        ],
        [
            'version' => '2.0',
            'date' => '28.08.2026 21:00',
            'author' => 'Timo Müller',
            'changes' => [
                'Sicherheits- und Stabilitäts-Härtung nach vollständigem Codereview (39 verifizierte Funde)',
                '2FA-Brute-Force-Schutz, Replay-Sperre, gehashte Wiederherstellungscodes',
                'Strikte Dokumentsichtbarkeit (default-deny) inkl. Freigabe-Ablauf',
                'Transaktionsschutz für Ankauf, Auszahlung und Zahlungszuordnung; kumulierte Teilzahlungen',
                'Produktions-Init ohne SSH, automatische Überfälligkeits-Markierung, Audit-Ketten-Prüfung',
            ],
        ],
        [
            'version' => '1.0',
            'date' => '24.08.2026 02:00',
            'author' => 'Timo Müller',
            'changes' => [
                'Kern-Prototyp: Datenmodell, 13 Rollen, sechs Dashboards, End-to-End-Forderungsprozess mit Vier-Augen-Prinzip',
                'CRM, Onboarding, Kreditlinien, Investoren/Fazilitäten, DMS, Mahnwesen, Journal (Doppik), Audit-Log mit Hash-Kette',
                'MFA (TOTP), Medical Data Firewall, Wasserzeichen, verschlüsseltes Backup, Deployment-Paket für PHP-Webspace',
            ],
        ],
    ];
}
