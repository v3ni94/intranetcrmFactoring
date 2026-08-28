# BaFin-Vorbereitungsdokumentation — Aurevia Factoring-Plattform

Version 3.00 · Stand 29.08.2026 · **ENTWURF — NICHT BESCHLOSSEN**

**Zweck und rechtlicher Hinweis:** Dieses Dokument bereitet die Unterlagen für
ein mögliches Erlaubnisverfahren und spätere Prüfungen vor. Es beschreibt den
Ist-Stand der Plattform (Funktionen, Kontrollen, IT-Sicherheit). Es ist eine
interne Arbeitsunterlage und ersetzt keine Rechtsberatung. Der vollständige
Erlaubnisantrag, die rechtliche Würdigung und alle aufsichtsrechtlichen
Auslegungsfragen sind zwingend mit einer auf Aufsichtsrecht spezialisierten
Kanzlei und dem Wirtschaftsprüfer zu erarbeiten.

---

## 1. Einordnung des Geschäftsmodells

- Gegenstand: laufender Ankauf von Forderungen aus Lieferungen und Leistungen
  von Medizinern/Heilberuflern (Factoring), echtes und unechtes Factoring,
  offen und still.
- Nach unserer Einschätzung ist Factoring als Finanzdienstleistung nach
  § 1 Abs. 1a Satz 2 Nr. 9 KWG einzuordnen und bedarf der Erlaubnis nach
  § 32 KWG (durch Rechtsberatung zu bestätigen; Umfang, Ausnahmen und
  Übergangsfragen sind zu prüfen).
- Zu klärende Nebenthemen (Kanzlei): Abtretbarkeit ärztlicher Honorarforderungen
  (§ 203 StGB, Einwilligungserfordernis der Patienten), B2C-Konstellationen
  (Verbraucherschutz), Auslagerungssachverhalte, GwG-Verpflichtetenstatus.

## 2. Gesellschafts- und Governance-Struktur (geplant)

- Projektgesellschaft in Vorbereitung (Arbeitsname Aurevia Factoring AG);
  Software ist ein Produkt der Müller Holding AG.
- Organe: Vorstand (Geschäftsleiter i. S. d. KWG, fachliche Eignung und
  Zuverlässigkeit nachzuweisen), Aufsichtsrat als Überwachungsorgan ohne
  operative Kreditentscheidungen, Beirat beratend.
- Cap-Table, Related-Party-Register und Auslagerungsregister werden in der
  Plattform geführt (Modul „Cap-Table & Register").

## 3. Systemfunktionen (Übersicht für die Prüfung)

- Kunden-/Debitorenverwaltung mit Branchensegmenten (Medizin) und B2B/B2C.
- KYC/GwG-Strecke: Identifizierung, UBO-Erfassung, PEP-/Sanktionsscreening,
  Registerabgleich, Bonitätsauskunft (Adapter), Risikoklassen mit Turnus.
- Internes Rating AAA–C mit ratingabhängigen Gebührenaufschlägen.
- Forderungsankauf mit automatischer Regel-/Limitprüfung und zwingendem
  Vier-Augen-Prinzip (technisch erzwungen, Race-Condition-gesichert).
- Eskalationsverfahren nach Markt/Marktfolge-Prinzip mit Vorstand als letzter
  Instanz und Begründungspflicht je Votum.
- Kreditlinien und Debitorenlimits mit Klumpenrisiko-Schwelle und
  Versicherungsverwaltung (Warenkreditversicherung, Adapter vorbereitet).
- Auszahlungswesen mit Vier-Augen-Prinzip, Idempotenzschutz, SEPA-Export.
- Doppisches Nebenbuch (ausgeglichene Buchungssätze, Stornoprinzip),
  DATEV-Export.
- Mahnwesen, Streitfälle, Ausfallverbuchung, Inkasso-Schnittstelle.
- Investoren-/Fazilitätenverwaltung inkl. Sonderkündigungsrechten und
  Insolvenzfall des Investors.
- Berichtswesen: Rollen-Dashboards, CSV-Exporte, automatisierte KPI-Reports.
- Support-Ticketsystem, Benutzerhandbuch, Prozessleitfaden, FAQ.

## 4. Interne Kontrollen und Funktionstrennung

| Kontrolle | Umsetzung im System |
|---|---|
| Funktionstrennung Markt/Marktfolge | getrennte Rollen (Vertrieb/Operations vs. Kredit/Risiko); Zweitvoten nur durch Marktfolge/Vorstand |
| Vier-Augen-Prinzip | Ankauf und Auszahlung erfordern zwei verschiedene Personen; technisch erzwungen |
| Eskalationsordnung | Ablehnung → Marktfolge → Vorstand; Aufsichtsrat nur überwachend/berichtsempfangend |
| Berechtigungswesen | 14 Rollen, serverseitige Route-Absicherung, Benutzerverwaltung mit Deaktivierung statt Löschung |
| Revisionssicherheit | Append-only-Audit-Log mit SHA-256-Hash-Kette, Integritätsprüfung per Kommando, Manipulation erkennbar |
| Datenabschottung | „Medical Data Firewall": Kunde/Investor/Beirat erreichen interne Daten auch per direkter URL nicht |
| Limitwesen | Ankaufs-/Auszahlungslinien, Debitorenlimits, Konzentrationsgrenzen, Klumpenrisiko-Markierung |

## 5. IT-Sicherheit (Cybersecurity)

- **Authentifizierung**: Passwort-Login mit Rate-Limiting; verpflichtende
  Zwei-Faktor-Authentifizierung (TOTP) für alle internen Rollen, Investoren
  und Beiräte; Brute-Force-Schutz und Replay-Sperre auf dem zweiten Faktor;
  Wiederherstellungscodes nur gehasht gespeichert.
- **Verschlüsselung**: TLS-Transportverschlüsselung; sensible Felder (z. B.
  TOTP-Geheimnisse) verschlüsselt gespeichert; tägliche Backups AES-256-
  verschlüsselt; Backup-Schlüssel getrennt verwahrt.
- **Härtung**: strikte Eingabevalidierung, Schutz vor Doppelbuchungen über
  Transaktionen/Sperren und Idempotenzschlüssel, Unique-Constraints auf
  Datenbankebene, kein öffentlicher Registrierungsweg.
- **Protokollierung**: alle sicherheits- und geschäftskritischen Aktionen im
  hash-verketteten Audit-Log mit Nutzer, Rolle, IP und Zeitstempel.
- **Betrieb**: dokumentiertes Deployment, Versionierung mit Changelog,
  Schwachstellen-Monitoring der Abhängigkeiten (composer audit; bekannte
  Framework-Advisories sind dokumentiert und vor Produktivbetrieb durch
  Upgrade zu schließen).
- **Offene Punkte** (vor Erlaubnisantrag zu schließen): Penetrationstest durch
  externen Dienstleister, Notfallhandbuch/BCM, Auslagerungsverträge mit
  Hosting-Anbieter, Informationssicherheits-Leitlinie, Restore-Test auf
  Zielumgebung.

## 6. Geldwäscheprävention (GwG)

- KYC-Prozesse systemseitig abgebildet (Abschnitt 3); Bestellung eines
  Geldwäschebeauftragten, Risikoanalyse nach § 5 GwG, interne Sicherungs-
  maßnahmen und Verdachtsmeldewesen sind organisatorisch zu etablieren
  (offener Punkt, nicht Software-seitig lösbar).

## 7. Auslagerungen (§ 25b KWG / MaRisk AT 9 — zu verifizieren)

Im Auslagerungsregister der Plattform zu führen und vertraglich zu
unterlegen: Hosting (IONOS), Warenkreditversicherer, Auskunfteien
(Creditreform/SCHUFA), Inkassodienstleister, E-Signatur-Anbieter,
DATEV/Steuerberater. Wesentlichkeitsanalysen und Exit-Strategien sind zu
erstellen.

## 8. Anlagen (in der Plattform bzw. im Repository)

- Benutzerhandbuch (docs/BENUTZERHANDBUCH.md)
- Prozessleitfaden (docs/PROZESSLEITFADEN.md)
- Datenschutzkonzept (docs/DATENSCHUTZ_KONZEPT.md)
- Deployment-/Betriebsdokumentation (DEPLOYMENT.md)
- Changelog/Versionshistorie (in der Anwendung, /hilfe/changelog)
- Master-Prompt/Fachkonzept (Projektunterlage)
