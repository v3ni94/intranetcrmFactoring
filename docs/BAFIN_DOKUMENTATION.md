# BaFin-Vorbereitungsdokumentation — Aurevia Factoring-Plattform

Version 3.03 · Stand 29.08.2026 · **ENTWURF — NICHT BESCHLOSSEN**

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
| Berechtigungswesen | 14 Rollen, serverseitige Route-Absicherung, Benutzerverwaltung mit Deaktivierung als Regelfall, Löschung nur ohne verknüpfte Historie |
| Revisionssicherheit | Append-only-Audit-Log mit SHA-256-Hash-Kette, Integritätsprüfung per Kommando, Manipulation erkennbar |
| Datenabschottung | „Medical Data Firewall": Kunde/Investor/Beirat erreichen interne Daten auch per direkter URL nicht |
| Limitwesen | Ankaufs-/Auszahlungslinien, Debitorenlimits, Konzentrationsgrenzen, Klumpenrisiko-Markierung |
| Beschäftigungsfenster (v3.02) | Nutzerkonto ist erst ab Eintrittsdatum nutzbar und wird nach Austrittsdatum automatisch für Login und 2FA gesperrt (zur Laufzeit geprüft, ohne gesonderten Lauf) |
| Testdaten-Trennung (v3.03) | Vorführ-Testdaten sind durchgängig über das Kennzeichen `is_demo` von echten Geschäftsvorfällen getrennt; Löschung nur nach erneuter Passworteingabe, vollständige Löschung zusätzlich nur mit Bestätigungsphrase |

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

## 8. Ergänzende Kontrollen (v3.02/v3.03, zu verifizieren)

- **Einfache elektronische Signatur der Musterverträge**: Seit Version 3.03
  lassen sich Factoring- und Fazilitätsverträge als Muster/Entwurf-PDF aus den
  Systemdaten erzeugen und im System mit Name und Zeitstempel beidseitig
  „unterzeichnen" (einfache elektronische Signatur). Nach unserer Einschätzung
  handelt es sich hierbei ausdrücklich nicht um eine qualifizierte
  elektronische Signatur; die Eignung dieser Signaturform für den jeweiligen
  Vertragstyp und die daraus abzuleitenden Beweiswert- und Formanforderungen
  sind vor Produktivnutzung zwingend durch eine auf Aufsichts- und Zivilrecht
  spezialisierte Kanzlei zu prüfen. Bis zu dieser Prüfung ist die Funktion als
  Vorbereitungs- und Vorführfunktion zu betrachten.
- **Beschäftigungsfenster als IKS-Kontrolle**: Ein- und Austrittsdatum je
  Benutzerkonto (Personalakte, seit Version 3.02) sperren den Systemzugriff
  automatisch außerhalb des Beschäftigungszeitraums (Login und
  2FA-Freischaltung). Dies unterstützt nach unserer Einschätzung die
  Anforderungen an einen zeitnahen Zugriffsentzug ausgeschiedener Mitarbeiter
  im internen Kontrollsystem; ob die Kontrolle für den beabsichtigten Zweck
  ausreicht (insbesondere Zeitpunkt und Vollständigkeit der Pflege des
  Austrittsdatums als organisatorische Restgröße), ist im Rahmen der
  IKS-Dokumentation zu verifizieren.
- **Testdaten-Trennung**: Vorführ-Testdaten (100 fiktive Medizin-Kunden,
  Investoren-Testdatensätze) sind durchgängig mit dem Kennzeichen `is_demo`
  markiert und von echten Geschäftsvorfällen getrennt. Das Löschen von
  Testdaten erfordert die erneute Eingabe des Passworts der ausführenden
  Person; das vollständige Löschen aller Mandantendaten erfordert zusätzlich
  die wörtliche Bestätigungsphrase „ALLES LÖSCHEN" und ist endgültig. Nach
  unserer Einschätzung trägt dies zur Abgrenzung von Test- und
  Produktivdaten bei; eine abschließende Bewertung setzt die Festlegung
  voraus, ob und wie Testdaten auf einem produktiv genutzten Mandanten
  überhaupt zulässig sind (offener Punkt, organisatorisch zu regeln).

## 9. Anlagen (in der Plattform bzw. im Repository)

- Benutzerhandbuch (docs/BENUTZERHANDBUCH.md)
- Prozessleitfaden (docs/PROZESSLEITFADEN.md)
- Datenschutzkonzept (docs/DATENSCHUTZ_KONZEPT.md)
- Deployment-/Betriebsdokumentation (DEPLOYMENT.md)
- Changelog/Versionshistorie (in der Anwendung, /hilfe/changelog)
- Master-Prompt/Fachkonzept (Projektunterlage)
