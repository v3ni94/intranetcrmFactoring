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
            'version' => '3.04',
            'date' => '29.08.2026 23:30',
            'author' => 'Timo Müller',
            'changes' => [
                'Personalakte: Nachweis-Dokumente (Personalausweis, Führerschein, SCHUFA-Auskunft, Führungszeugnis, Sonstiges) können jetzt als PDF oder Bild direkt beim Benutzer hochgeladen, heruntergeladen und gelöscht werden — Ablage geschützt außerhalb des Webverzeichnisses, Zugriff nur für Systemadministration, Geschäftsleitung und Superadmin, jeder Vorgang wird auditiert',
                'Fehlerbehebung Demo-Steuerung: Die Zählung der Testdatensätze fragte die Kennzeichnungsspalte auch auf Tabellen ohne diese Spalte ab, was auf dem Produktivserver (MariaDB) zu einem Serverfehler führte; die Zählung überspringt diese Tabellen jetzt und zählt das Testdaten-Produkt wie beim Löschen über den Namen mit',
                'Seitennavigation: Auf Desktop-Breite ist die Menüleiste jetzt standardmäßig aufgeklappt und bleibt beim Seitenwechsel offen; nur der Menü-Button klappt sie zu, die Wahl wird je Browser gemerkt; der Mobil-Zustand überschreibt die Desktop-Einstellung nicht mehr',
            ],
        ],
        [
            'version' => '3.03',
            'date' => '29.08.2026 21:00',
            'author' => 'Timo Müller',
            'changes' => [
                'Testdaten für Vorführzwecke: 100 fiktive Medizin-Kunden (Ärzte, Zahnärzte, Apotheken, Dentallabore, Tierärzte, Heilberufe, Pflege, MVZ, Kliniken) mit Verträgen, Ratings und Kreditlinien',
                'Investoren-Testdaten: Müller Holding AG (900.000 EUR), Enns Holding GmbH (1.100.000 EUR) und apoBank-Testdatensatz (29.500.000 EUR) mit monatlichen Ausschüttungen seit 2025 (10 % p. a., monatlich nachschüssig)',
                'Forderungen, Ankäufe und Zahlungen über 2025/2026 verteilt, damit Auswertungen und Diagramme in allen Rollen gefüllt sind; Betriebskosten je Monat fürs Controlling; separate Abwicklungskonten (Kunden-/Investorengelder)',
                'Musterverträge (Factoring- und Fazilitätsvertrag) direkt aus den Systemdaten als PDF erzeugbar, mit einfacher elektronischer Signatur beider Seiten und automatischer Freigabe an die Gegenseite',
                'Testdaten-Verwaltung in der Demo-Steuerung: Einspielen, Testdaten löschen und Alles löschen — Löschvorgänge endgültig, unwiderruflich und nur mit erneuter Passworteingabe',
                'Monatliche Fortschreibung der Zinsausschüttungen über den Scheduler (aurevia:accrue-interest)',
                'Mehr Diagramme: Ankaufsvolumen (Aufsichtsrat/Beirat), monatliche Ausschüttungen (Investor), Altersstruktur (Risiko)',
                'Dokumentsichtbarkeit verfeinert: organisationsgebundene Investor-Dokumente sieht nur der Investor der eigenen Organisation',
            ],
        ],
        [
            'version' => '3.02',
            'date' => '29.08.2026 18:30',
            'author' => 'Timo Müller',
            'changes' => [
                'Personalakte je Benutzer: Position, Abteilung, fachlicher und disziplinarischer Vorgesetzter, Kontaktdaten privat/geschäftlich, Adresse, Geburtsdatum, Steuer-ID und Ausweisnummer (verschlüsselt gespeichert), Führungszeugnis, SCHUFA, Führerschein, HR-Notizen',
                'Ein- und Austrittsdatum mit automatischer Kontosteuerung: Login erst ab Eintritt, automatische Sperre nach Austritt',
                'Benutzer bearbeiten, Rollen tauschen und löschen (Löschung nur ohne Historie, sonst Hinweis auf Deaktivierung zum Erhalt des Audit-Trails); Superadmin-Rolle nur durch Superadmin vergeb- und entziehbar',
                'Administration als Dropdown oben rechts im Header statt in der Seitennavigation',
                'Seitennavigation: Gruppen standardmäßig eingeklappt, Auf-/Zuklappen wird je Nutzer im Browser gemerkt',
                'Vollständige englische Übersetzung aller Oberflächen inkl. FAQ und Onboarding-Leitfaden',
                'Wissensdatenbank im Hilfebereich: Benutzerhandbuch für alle, Prozessleitfaden, BaFin-Vorbereitung und Datenschutzkonzept für interne Rollen',
                'Onboarding-Leitfaden zum Durchklicken durch alle Module mit Direktlinks',
                'Favicon (Aurevia-Monogramm) für Browser-Tab und Startbildschirm',
                'Footer-Zeile „Schon gewusst?" mit dem Welt- oder Aktionstag des jeweiligen Datums (inkl. beweglicher Feste wie Aschermittwoch und Ostern, zweisprachig)',
            ],
        ],
        [
            'version' => '3.01',
            'date' => '29.08.2026 15:30',
            'author' => 'Timo Müller',
            'changes' => [
                'Benutzeranlage und Passwort-Reset versenden die Zugangsdaten per E-Mail: Willkommens-Mail mit zeitlich begrenztem Passwort-Setz-Link (kein Klartext-Passwort per Mail)',
                'Fallback bei nicht konfiguriertem Mailversand: Startpasswort wird wie bisher einmalig im System angezeigt',
            ],
        ],
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
