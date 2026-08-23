# Aurevia Intranet · CRM · Factoring-Plattform (Kick-off-Prototyp)

Klickbarer Prototyp für **Aurevia Factoring** (Arbeitsname; Projektgesellschaft in
Vorbereitung, Registerangaben folgen nach Gründung) gemäß dem Master-Prompt
„Aurevia Factoring – Master-Prompt für Intranet, CRM und Factoring-Plattform"
(Version 0.2, Stand 23.08.2026).

**Alle Daten in diesem Repository sind synthetisch.** Es finden keine echten
Zahlungen statt. Das System enthält ausdrücklich ein „ENTWURF – NICHT
BESCHLOSSEN"-Banner sowie einen „DEMO"-Banner, solange `AUREVIA_DEMO_MODE=true`
gesetzt ist.

## Umsetzungsstand: Kern-Prototyp

Dieser Durchlauf implementiert den in Abschnitt 25 des Master-Prompts
beschriebenen Kern-Prototyp:

1. ✅ Informationsarchitektur und Datenmodell (32 Migrationen, ~30 Entitäten)
2. ✅ Rollen (13), Rechte (Spatie Permission) und Demo-Nutzer je Rolle
3. ✅ Aurevia-Designsystem (CI-Farben, Logo-Platzhalter, Login, Navigation)
4. ✅ Sechs Kern-Dashboards: Kunde, Mitarbeiter, Risiko, Geschäftsleitung, Beirat, Investor
5. ✅ End-to-End-Forderungsprozess inkl. Vier-Augen-Prinzip, SEPA-Demo-Export, camt-Demo-Import
6. ✅ CRM-Basis, Onboarding-Pipeline, Kreditlinien, Investoren/Fazilitäten, DMS-Basis, Aufgaben
7. ✅ Realistisch verknüpfte Demo-Daten (Abschnitt 21) inkl. Demo-Reset/-Löschen
8. ✅ Automatisierte Tests (30 Feature-Tests, u. a. Vier-Augen-Prinzip, RBAC, Journal-Bilanz)
9. ⚠️ Diese Anleitung, Testzugänge, Datenmodellübersicht, offene Punkte (unten)
10. ⚠️ Webspace-Deploymentpaket: Migrationen/Seeder sind produktionsreif; ein
    fertig gepacktes ZIP mit `vendor/` ist nicht Teil dieses Commits (siehe
    „Deployment auf PHP-Webspace" unten für die nötigen Schritte)

Nicht bzw. nur ansatzweise umgesetzt (siehe „Offene Punkte"): vollständige
feldbezogene Berechtigungen, alle Adapter-Stubs (KYC/Bonität/E-Signatur/DATEV
etc.), Cap-Table-Modul, PSD2/EBICS-Anbindung, vollständige Wasserzeichen-/
Sperrvermerk-Durchsetzung im Download, mehrsprachige Oberfläche.

## Demo starten

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # nur für lokale Demo ohne MariaDB
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

Aufrufbar unter `http://127.0.0.1:8000/login`.

### Testzugänge (Demo-Mandant)

Passwort für **alle** Demo-Zugänge: `Aurevia-Demo-2026!`
(Login-Seite bietet im Demo-Modus eine Rollenauswahl, die E-Mail und Passwort
automatisch einträgt.)

| Rolle | E-Mail |
|---|---|
| Kunde – Administrator | demo.kunde_admin@aurevia-factoring.de |
| Kunde – Sachbearbeitung | demo.kunde_sachbearbeitung@aurevia-factoring.de |
| Investor / finanzierende Bank | demo.investor@aurevia-factoring.de |
| Beirat / Aufsichtsrat | demo.beirat@aurevia-factoring.de |
| Vertrieb / CRM | demo.vertrieb_crm@aurevia-factoring.de |
| Operations / Factoring-Sachbearbeitung | demo.operations@aurevia-factoring.de |
| Kredit / Risiko | demo.kredit_risiko@aurevia-factoring.de |
| Debitorenbuchhaltung / Collections | demo.debitorenbuchhaltung@aurevia-factoring.de |
| Treasury / Finance | demo.treasury_finance@aurevia-factoring.de |
| Compliance / Geldwäsche / Datenschutz | demo.compliance@aurevia-factoring.de |
| Geschäftsleitung / Vorstand | demo.geschaeftsleitung@aurevia-factoring.de |
| Systemadministration | demo.systemadministration@aurevia-factoring.de |
| Superadmin (Demo-Steuerung) | demo.superadmin_demo@aurevia-factoring.de |

Zusätzlicher Zugang für den Kick-off: `timo.mueller@aurevia-factoring.de`
(Rollen Geschäftsleitung + Superadmin).

### Geführter Klickpfad (angelehnt an Abschnitt 21.2)

1. Login als `demo.kunde_admin` → Kundendashboard „MedFlow Simple"
2. „Neue Forderung einreichen" → 3-Schritte-Assistent → einreichen
3. Login als `demo.operations` → „Forderungen" → Forderung öffnen → Formale
   Prüfung → Risiko-/Limitprüfung → „Ankauf berechnen"
4. Login als `demo.kredit_risiko` → dieselbe Forderung → „Zweite Freigabe
   erteilen (Vier-Augen)" — die erste Person kann nicht selbst zweitfreigeben
5. Login als `demo.treasury_finance` → „Ankauf & Auszahlungen" → Batch bilden
   → Erstfreigabe; zweite Freigabe durch eine andere Person (z. B.
   `demo.geschaeftsleitung`) → SEPA-Demo-Datei wird erzeugt → Bankbestätigung
6. „Zahlungseingänge" → „Demo-Kontoauszug importieren" → Vorschlag bestätigen
7. Login als `demo.geschaeftsleitung` → Vorstandsdashboard mit KPIs und
   Finanzszenarien (Konservativ/Base/Wachstum/Stress)
8. Login als `demo.investor` → „Meine Kapitalbeziehung"
9. Login als `demo.superadmin_demo` → „Demo-Steuerung" → Reset/Löschen testen

## Datenmodell (Kurzübersicht)

Zentrale Migrationsgruppen (`database/migrations/2026_08_23_*`):

- **Stammdaten**: `tenants`, `organizations` (Kunde/Debitor/Investor über
  `org_type`), `contacts`, `beneficial_owners`
- **CRM**: `leads`, `opportunities`, `crm_activities`, `tasks`
- **Compliance**: `kyc_cases`
- **Factoring-Kern**: `factoring_products`, `contracts`, `credit_lines`, `debtor_limits`
- **Forderungsprozess**: `receivables` (21 Status gemäß Abschnitt 11),
  `purchases`
- **Treasury/Zahlungsverkehr**: `bank_accounts`, `payout_batches`, `payouts`,
  `bank_transactions`, `payments`
- **Mahnwesen**: `dunning_cases`
- **Nebenbuch**: `journal_entries`, `journal_lines` (append-only, siehe
  `App\Services\JournalService`)
- **Investoren**: `facilities`, `facility_events`
- **DMS**: `documents` (Sperrvermerk, Sichtbarkeitsstufen)
- **Governance**: `audit_events` (hash-verkettet), `approval_requests`,
  `decisions`, `financial_scenarios`
- **Demo**: `demo_seeds`, `demo_reset_logs`

Alle Geldbeträge sind `DECIMAL(19,4)`, niemals `FLOAT`/`DOUBLE`. Jede
mandantenbezogene Tabelle trägt `tenant_id`; die Mandantentrennung wird
zentral über `App\Models\Concerns\BelongsToTenant` (Eloquent Global Scope) und
`App\Http\Middleware\IdentifyTenant` erzwungen, nicht durch clientseitige
Filter.

## Wichtige Architekturentscheidungen

- **Framework**: Laravel 11 (PHP 8.2+), Blade + Alpine.js (über Breeze),
  Tailwind CSS. Kein Node-Prozess zur Laufzeit auf dem Webspace nötig — `npm
  run build` erzeugt statische Assets unter `public/build`.
- **RBAC**: `spatie/laravel-permission`, 13 Rollen gemäß Abschnitt 5. Grobe
  Rollenprüfung über Route-Middleware (`role:`); feldbezogene Berechtigungen
  sind ein offener Punkt (siehe unten).
- **Mandantentrennung**: Global Scope + Middleware statt Datenbank-RLS (siehe
  Abschnitt 22.2), passend zu MariaDB 10 ohne PostgreSQL-RLS.
- **Nebenbuch**: `App\Services\JournalService::post()` verweigert
  unausgeglichene Buchungen (`Soll == Haben`) und bucht ausschließlich additiv;
  Korrekturen erfolgen über `reverse()` als Gegenbuchung.
- **Vier-Augen-Prinzip**: Erst-/Zweitfreigabe für Ankauf (`Purchase`) und
  Auszahlungsbatch (`PayoutBatch`) sind serverseitig erzwungen — dieselbe
  Person kann keine Zweitfreigabe für die eigene Erstfreigabe erteilen
  (`abort_if($approved_by_first === $request->user()->id, 403, ...)`).
- **SEPA/camt**: `App\Services\SepaExportService` erzeugt eine schema-nahe
  `pain.001`-Demo-Datei im internen Storage (kein Bankversand).
  `App\Services\PaymentMatcher` simuliert `camt.053`-Import und schlägt
  Zahlungszuordnungen mit Konfidenz/Begründung vor.
- **Demo-Trennung**: `is_demo`-Flag auf allen Bewegungsdaten, eigener
  Demo-Mandant (`tenants.type = demo`), `App\Services\DemoResetService`
  verweigert jede Löschung außerhalb eines als Demo gekennzeichneten Mandanten
  (`assertDemoTenant()`).

## Verbindliche KPI-Formeln

Implementiert in `App\Services\KpiService`, angezeigt mit Formel-Tooltip auf
jeder KPI-Karte (Abschnitt 15.5): Auslastung Kreditlinie/Investorenlinie,
Bruttoertrag, Refinanzierungskosten, Deckungsbeitrag, Verwässerungsquote,
Überfälligkeitsquote, Top-10-Konzentration, DSO, Altersstruktur.

## Tests

```bash
php artisan test
```

30 Feature-Tests, u. a.:

- `RoleDashboardTest` – jede der 13 Rollen erreicht ihr eigenes, ladbares Dashboard
- `ReceivableWorkflowTest` – vollständiger Prozess Einreichen → Prüfen →
  Freigeben → Ankauf, inkl. Nachweis, dass die Vier-Augen-Regel dieselbe
  Person als Zweitfreigeber ablehnt (403) und die Journalbuchung ausgeglichen ist
- `DemoResetTest` – Reset/Löschen nur für Superadmin, nur im Demo-Mandanten
  (Löschversuch an einem Produktivmandanten wirft eine Exception)

## Deployment auf PHP-Webspace (Abschnitt 22)

1. Auf einem Rechner mit Internetzugang: `composer install --no-dev
   --optimize-autoloader` und `npm run build` ausführen, damit `vendor/` und
   `public/build/` befüllt sind.
2. Gesamtes Verzeichnis außer `.env`, `.git`, `node_modules` auf den Webspace
   übertragen; `public/` als Docroot konfigurieren (Abschnitt 22.3).
3. `.env` aus `.env.example` ableiten, echte MariaDB-10-Zugangsdaten (`DB_*`)
   eintragen, `APP_DEBUG=false`, `APP_KEY` per `php artisan key:generate`
   erzeugen, `AUREVIA_DEMO_MODE=false` für Produktivbetrieb.
4. `php artisan migrate --force` (Demo-Daten **nicht** in Produktion seeden —
   `AureviaDemoDataSeeder` nur im geschützten Demo-Mandanten verwenden).
5. Cronjob für `php artisan schedule:run` einrichten, sofern der Webspace
   Cron anbietet (Abschnitt 22.3); andernfalls verbleibt synchrone
   Verarbeitung als dokumentierte Einschränkung.
6. Tägliches verschlüsseltes Backup von Datenbank und `storage/app` einrichten
   und Restore testen (noch nicht automatisiert, siehe offene Punkte).

## Offene Punkte / Roadmap Demo → Produktion

Blockieren den Prototyp nicht, sind aber vor Produktivbetrieb zu klären
(siehe auch Abschnitt 26 im Master-Prompt und `/projekt` im Intranet):

- Rechtsform, Registerangaben, endgültige Organbesetzung
- Erlaubnispflicht nach KWG, GwG-Prozesse, DSGVO-Freigabe für Gesundheitsdaten
  (aktuell technisch vorbereitet, rechtlich nicht geprüft)
- Echte Bank-/PSD2-/EBICS-Anbindung statt Demo-Dateien
- Vollständige Adapter für KYC/KYB, PEP/Sanktionen, Bonität, E-Signatur,
  DATEV/ERP-Export, Praxissoftware-Schnittstellen
- Feldbezogene Berechtigungen (aktuell nur Rollen-/Routenebene)
- Wasserzeichen und technisch erzwungene Exportsperre für sensible
  Board-/Investorendokumente (Sperrvermerk-Feld existiert, Durchsetzung im
  DMS ist minimal)
- Cap-Table-/Beteiligungsmodul, vollständiges Governance-Cockpit
  (Workstreams, Risk Log, Related-Party-Register)
- MFA/Passkeys, SSO (OIDC/SAML), automatisierte Backups/Restore-Tests
- Mehrsprachigkeit (Englisch technisch vorbereiten)

## Sicherheitsleitplanken (bereits umgesetzt)

- Keine öffentliche Registrierung (Route entfernt)
- Keine echten Zahlungen (SEPA/camt ausschließlich als Demo-Datei)
- Pseudonyme IDs für private Rechnungsempfänger (`PAT-DEMO-…`)
- Vier-Augen-Prinzip für Ankauf, Auszahlung und Demo-Löschung
- Append-only, hash-verkettetes Audit-Log (`App\Support\AuditLogger`)
- Mandantentrennung über Global Scope statt Client-Filter
