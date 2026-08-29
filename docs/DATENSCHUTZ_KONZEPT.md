# Datenschutzkonzept (Entwurf) — Aurevia Factoring-Plattform

Version 3.03 · Stand 29.08.2026 · **ENTWURF — durch Datenschutzbeauftragten /
Rechtsberatung zu prüfen und zu beschließen**

## 1. Besonderheit: Gesundheitsbezug

Forderungen von Medizinern können Rückschlüsse auf Gesundheitsdaten von
Patienten zulassen (Art. 9 DSGVO, § 203 StGB). Grundsätze der Plattform:

- **Pseudonymisierung**: private Rechnungsempfänger werden ausschließlich mit
  Pseudonym-IDs geführt; Klarnamen von Patienten werden nicht benötigt und
  nicht gespeichert.
- **Medical Data Firewall**: Investoren und Beirat sehen ausschließlich
  aggregierte bzw. pseudonymisierte Kennzahlen; Kunden nur die eigenen Daten;
  serverseitig erzwungen.
- **Abtretung**: Bei B2C-/Patientenforderungen ist die Einwilligung in die
  Abtretung (§ 203 StGB) zwingend einzuholen; die Plattform kennzeichnet
  B2B/B2C je Organisation. Rechtliche Ausgestaltung durch Kanzlei.

## 2. Verarbeitungen (Auszug, Verzeichnis nach Art. 30 DSGVO zu erstellen)

| Verarbeitung | Betroffene | Rechtsgrundlage (zu verifizieren) |
|---|---|---|
| Kundenstammdaten, Verträge | Kunden, Ansprechpartner | Art. 6 Abs. 1 b DSGVO |
| KYC/GwG-Prüfungen, UBO, PEP | wirtschaftlich Berechtigte | Art. 6 Abs. 1 c DSGVO i. V. m. GwG |
| Bonitätsauskünfte | Kunden/Debitoren | Art. 6 Abs. 1 b, f DSGVO |
| Forderungsdaten (pseudonymisiert) | Debitoren/Patienten | Art. 6 Abs. 1 b, f; Art. 9 nur mit Einwilligung |
| Benutzerkonten, Audit-Log | Beschäftigte, Nutzer | Art. 6 Abs. 1 b, f DSGVO |
| Investorendaten | Investoren | Art. 6 Abs. 1 b DSGVO |
| Personalakte (Position, Kontakt, Adresse, Geburtsdatum, Steuer-ID, Ausweis-Nr., Nachweise) | Beschäftigte | Beschäftigtendatenschutz i. V. m. Art. 6 Abs. 1 b, c DSGVO (Einschätzung, zu verifizieren) |

## 3. Technische und organisatorische Maßnahmen (Art. 32 DSGVO)

- Zugriffskontrolle: Rollenmodell (14 Rollen), 2FA-Pflicht intern,
  Deaktivierung als Regelfall (Löschung nur ohne Historie), Startpasswörter
  mit Einmal-Anzeige, automatische Zugriffssperre außerhalb des in der
  Personalakte gepflegten Beschäftigungszeitraums (seit Version 3.02).
- Übertragungskontrolle: TLS; keine öffentlichen Registrierungen.
- Eingabekontrolle: hash-verkettetes Audit-Log (wer, was, wann, von wo).
- Verfügbarkeitskontrolle: tägliche verschlüsselte Backups, Restore-Prozess.
- Trennungsgebot: Mandantenmodell, Medical Data Firewall, Datenraum mit
  Freigabesteuerung, Ablaufdaten und Wasserzeichen bei Dokumenten; Testdaten
  für Vorführzwecke sind über das Kennzeichen `is_demo` von echten Daten
  getrennt (seit Version 3.03, siehe Prozessleitfaden P14).
- Datenminimierung: Pseudonym-IDs, keine Patientenklarnamen, keine
  Steuernummern/Bankdaten in Dokumenten ohne Erfordernis; Steuer-ID und
  Personalausweis-Nummer der Personalakte sind verschlüsselt gespeichert und
  ausschließlich für Systemadministration/Geschäftsleitung einsehbar.

## 4. HR-Daten der Personalakte (seit Version 3.02)

Die Personalakte je Benutzerkonto (Administration → Benutzer) führt neben den
Kontodaten auch Position, Berichtslinien, private und geschäftliche
Kontaktdaten, Adresse, Geburtsdatum, Steuer-ID, Personalausweis-Nummer sowie
Nachweise (Führungszeugnis, SCHUFA-Auskunft, Führerschein) und freie
HR-Notizen.

- **Rechtsgrundlage**: Nach unserer Einschätzung stützt sich die Verarbeitung
  auf den Beschäftigtendatenschutz im Rahmen des Beschäftigungsverhältnisses,
  regelmäßig i. V. m. Art. 6 Abs. 1 b bzw. c DSGVO. Die im Einzelfall
  einschlägige Rechtsgrundlage ist durch Rechtsberatung zu verifizieren.
- **Verschlüsselung**: Steuer-ID und Personalausweis-Nummer werden auf
  Datenbankebene verschlüsselt gespeichert und stehen im Klartext nur
  innerhalb der Anwendung zur Verfügung.
- **Zugriff**: ausschließlich Systemadministration, Geschäftsleitung und
  Superadmin (Demo-Steuerung), also die Rollen, die auch die
  Benutzerverwaltung bedienen dürfen; für andere Rollen serverseitig
  gesperrt.
- **Keine Aufnahme in Exporte oder Dokumente**: Steuer-ID und
  Personalausweis-Nummer erscheinen in keinem CSV-Export, DATEV-Buchungsstapel
  oder erzeugten Dokument (z. B. Musterverträgen); sie werden zudem bei der
  Serialisierung des Benutzerdatensatzes grundsätzlich ausgeblendet.
- **Löschkonzept nach Austritt** (offener Punkt): Nach Austritt bleibt das
  Benutzerkonto zunächst gesperrt bzw. deaktiviert erhalten, sofern es nicht
  historienfrei gelöscht werden kann (Audit-Trail-Schutz, siehe
  Prozessleitfaden P12). Eine eigenständige, fristengesteuerte Löschung oder
  Anonymisierung der Personalakte nach Ablauf gesetzlicher bzw. arbeits-/
  steuerrechtlicher Aufbewahrungsfristen ist noch nicht abgebildet und vor
  Produktivbetrieb mit Rechtsberatung und Datenschutzbeauftragtem festzulegen.

## 5. Offene Punkte (vor Produktivbetrieb)

- Benennung Datenschutzbeauftragter, Verzeichnis von Verarbeitungstätigkeiten,
  Datenschutz-Folgenabschätzung (Gesundheitsbezug!), Lösch-/Aufbewahrungs-
  konzept (HGB/AO-Fristen vs. DSGVO), AV-Verträge (Hosting, Versicherer,
  Auskunfteien, Inkasso), Informationspflichten (Art. 13/14),
  Betroffenenrechte-Prozess, Meldewege Art. 33/34.
