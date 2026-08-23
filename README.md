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

1. ✅ Informationsarchitektur und Datenmodell (18 Migrationen, ~32 Entitäten)
2. ✅ Rollen (13), Rechte (Spatie Permission) und Demo-Nutzer je Rolle
3. ✅ Aurevia-Designsystem (CI-Farben, Logo-Platzhalter, Login, Navigation)
4. ✅ Sechs Kern-Dashboards: Kunde, Mitarbeiter, Risiko, Geschäftsleitung, Beirat, Investor
5. ✅ End-to-End-Forderungsprozess inkl. Vier-Augen-Prinzip, SEPA-Demo-Export, camt-Demo-Import
6. ✅ CRM-Basis, Onboarding-Pipeline, Kreditlinien, Investoren/Fazilitäten, DMS-Basis, Aufgaben
7. ✅ Realistisch verknüpfte Demo-Daten (Abschnitt 21) inkl. Demo-Reset/-Löschen
7a. ✅ Medical Data Firewall serverseitig erzwungen: Kunden-/Debitorenlisten und interne
    Prozessrouten sind für Kunde/Investor/Beirat per Route-Middleware gesperrt (nicht nur
    aus der Navigation ausgeblendet); Dokumentsichtbarkeit wird nach `visibility` und
    Kunden-Zugehörigkeit gefiltert (`Document::scopeVisibleTo()`)
7b. ✅ Governance-Cockpit erweitert um Workstreams A–J und Risk Log (Abschnitt 14.1),
    inkl. Demo-Decision-Log
7c. ✅ Provider-Adapter-Architektur (Abschnitt 20): Registry + Ereignisprotokoll für
    11 Adapter-Kategorien (Bank, KYC/KYB, PEP/Sanktionen, Handelsregister/UBO,
    Bonität, E-Signatur, Praxisimport, DATEV, OCR, Inkasso, BI), Statusseite unter
    Einstellungen → Integrationen
7d. ✅ MFA (TOTP) verpflichtend für alle Rollen außer Kunde (Abschnitt 18):
    Einrichtungszwang, echte Challenge beim Login, Wiederherstellungscodes
7e. ✅ Cap-Table-Modul, Related-Party- und Auslagerungsregister (Abschnitt 14.1/19),
    streng geschützt auf Geschäftsleitung/Superadmin
7f. ✅ Automatisches Wasserzeichen (Status, Version, Empfänger) beim Download
    sensibler PDF-Dokumente (Abschnitt 14), echte Stempelung mit FPDI/TCPDF
7g. ✅ Tägliches verschlüsseltes Backup (Abschnitt 22.3): `php artisan
    aurevia:backup` sichert Datenbank + `storage/app` als AES-256-verschlüsseltes
    ZIP, per Scheduler täglich um 02:30 Uhr, alte Backups werden automatisch
    aufgeräumt
8. ✅ Automatisierte Tests (51 Feature-Tests, u. a. Vier-Augen-Prinzip, RBAC,
   Medical-Data-Firewall-Zugriffskontrolle, MFA-Erzwingung, Journal-Bilanz,
   Wasserzeichen-Nachweis, Backup/Verschlüsselung/Aufräumen)
9. ✅ Diese Anleitung, Testzugänge, Datenmodellübersicht, offene Punkte (unten)
10. ✅ Schritt-für-Schritt-Deployment-Anleitung für PHP-Webspace inkl.
    Subdomain-Verknüpfung, FileZilla-Upload, Datenbank-Einrichtung und
    Cronjob, siehe `DEPLOYMENT.md`. Das produktionsfertige ZIP mit `vendor/`
    und gebauten Frontend-Assets wird bei Bedarf separat bereitgestellt
    (nicht Teil des Git-Repositories, da es kompilierte Abhängigkeiten
    enthält)

Nicht bzw. nur ansatzweise umgesetzt (siehe „Offene Punkte"): vollständige
feldbezogene Berechtigungen unterhalb der Rollenebene, Cap-Table-Modul,
Passkeys/SSO, vollständige Wasserzeichen-Durchsetzung im Download,
mehrsprachige Oberfläche.

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

**Zwei-Faktor-Authentifizierung (MFA):** Für alle Rollen außer Kunde ist MFA
gemäß Abschnitt 18 verpflichtend. Nach Passwort-Login folgt eine TOTP-Abfrage;
im Demo-Modus wird der aktuell gültige Code für vorkonfigurierte Demo-Zugänge
direkt auf der Abfrageseite angezeigt und vorausgefüllt (echte Prüfung, kein
Bypass) — Sie müssen nur noch „Bestätigen“ klicken.

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
  `decisions`, `financial_scenarios`, `workstreams`, `project_risks`
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

51 Feature-Tests, u. a.:

- `RoleDashboardTest` – jede der 13 Rollen erreicht ihr eigenes, ladbares Dashboard
- `ReceivableWorkflowTest` – vollständiger Prozess Einreichen → Prüfen →
  Freigeben → Ankauf, inkl. Nachweis, dass die Vier-Augen-Regel dieselbe
  Person als Zweitfreigeber ablehnt (403) und die Journalbuchung ausgeglichen ist
- `DemoResetTest` – Reset/Löschen nur für Superadmin, nur im Demo-Mandanten
  (Löschversuch an einem Produktivmandanten wirft eine Exception)
- `IntegrationAdapterTest` – Adapter-Registry wird vollständig angelegt, Aufrufe
  protokollieren Ereignisse und Providerstatus, Statusseite nur für interne Rollen
- `TwoFactorAuthenticationTest` – interne Rollen werden ohne MFA zur Einrichtung
  gezwungen, Kunde nicht, Login schlägt bei falschem TOTP-Code fehl und
  gelingt erst mit gültigem Code
- `MedicalDataFirewallTest` – Investor/Beirat/Kunde erreichen interne
  Kunden-/Debitorenlisten auch per direktem URL-Aufruf nicht (403), sehen nur
  extern freigegebene bzw. eigene Dokumente und können keine Dokumente hochladen
- `BackupCommandTest` – `aurevia:backup` erzeugt ein Archiv, verschlüsselt es
  bei gesetztem Schlüssel (AES-256, per OpenSSL entschlüsselbar) und räumt
  Archive auf, die älter als `--keep-days` sind

## Deployment auf PHP-Webspace (Abschnitt 22)

Ausführliche Schritt-für-Schritt-Anleitung inkl. Subdomain-Verknüpfung,
FileZilla-Upload, Datenbank-Einrichtung, `.env`-Konfiguration, Cronjob und
Funktionsprüfung: siehe **`DEPLOYMENT.md`**. Kurzfassung:

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
   Verarbeitung als dokumentierte Einschränkung. Derselbe Cronjob löst auch
   das tägliche Backup aus (`aurevia:backup`, siehe unten).
6. `AUREVIA_BACKUP_KEY` (langer zufälliger Schlüssel) in der `.env` setzen und
   sicher verwahren (z. B. Passwortmanager) — ohne diesen Schlüssel bleibt das
   tägliche Backup unverschlüsselt. Das Backup läuft automatisch täglich um
   02:30 Uhr über den Scheduler und legt verschlüsselte Archive unter
   `storage/app/backups` ab (Aufbewahrung standardmäßig 14 Tage, `php artisan
   aurevia:backup --keep-days=30` zum Anpassen). Restore: Archiv mit `openssl
   enc -d -aes-256-cbc -pbkdf2 -salt -in <datei>.zip.enc -out backup.zip -pass
   pass:<AUREVIA_BACKUP_KEY>` entschlüsseln, entpacken, `database/` enthält
   den Datenbank-Dump (SQL bzw. `.sqlite`-Kopie), `app/` die Dokumente/SEPA-Dateien.
   Empfehlung: Backups zusätzlich regelmäßig extern (z. B. per SFTP/Cloud-
   Speicher) sichern, da sie sonst auf demselben Webspace liegen wie die
   Originaldaten.

## Offene Punkte / Roadmap Demo → Produktion

Blockieren den Prototyp nicht, sind aber vor Produktivbetrieb zu klären
(siehe auch Abschnitt 26 im Master-Prompt und `/projekt` im Intranet):

- Rechtsform, Registerangaben, endgültige Organbesetzung
- Erlaubnispflicht nach KWG, GwG-Prozesse, DSGVO-Freigabe für Gesundheitsdaten
  (aktuell technisch vorbereitet, rechtlich nicht geprüft)
- Echte Bank-/PSD2-/EBICS-Anbindung statt Demo-Dateien (Adapter-Grundgerüst
  steht bereits, siehe Provider-Adapter-Architektur oben)
- Anschluss echter Anbieter hinter den registrierten Adaptern (KYC/KYB,
  PEP/Sanktionen, Bonität, E-Signatur, DATEV/ERP, Praxissoftware) — aktuell
  alle im Sandbox-/Demo-Modus, siehe Einstellungen → Integrationen
- Feldbezogene Berechtigungen unterhalb der Rollen-/Routenebene (z. B. einzelne
  Spalten je nach Feldsensitivität maskieren); Rollen-/Routen- und
  Dokumentensichtbarkeits-Ebene sind bereits serverseitig erzwungen (siehe oben)
- Wasserzeichen ist für PDF-Dokumente bereits umgesetzt (siehe oben); andere
  Dateitypen (DOCX, XLSX, Bilder) werden aktuell unverändert ausgeliefert
- Cap-Table-/Beteiligungsmodul und Related-Party-/Auslagerungsregister sind
  bereits als Grundgerüst umgesetzt (siehe oben); fehlt noch: Versionierung/
  Historie über mehrere Beschlussstände, automatisierte Verwässerungsberechnung
- Passkeys/WebAuthn als MFA-Alternative zu TOTP, SSO (OIDC/SAML) für Banken/
  Partner (MFA per TOTP ist bereits umgesetzt, siehe oben)
- Tägliches verschlüsseltes Backup ist bereits umgesetzt und automatisiert
  getestet (siehe oben); ein Restore auf einer echten Zielumgebung sollte vor
  Produktivbetrieb einmal manuell durchgespielt werden
- Mehrsprachigkeit (Englisch technisch vorbereiten)

## Sicherheitsleitplanken (bereits umgesetzt)

- Keine öffentliche Registrierung (Route entfernt)
- Keine echten Zahlungen (SEPA/camt ausschließlich als Demo-Datei)
- Pseudonyme IDs für private Rechnungsempfänger (`PAT-DEMO-…`)
- Vier-Augen-Prinzip für Ankauf, Auszahlung und Demo-Löschung
- Append-only, hash-verkettetes Audit-Log (`App\Support\AuditLogger`)
- Mandantentrennung über Global Scope statt Client-Filter
