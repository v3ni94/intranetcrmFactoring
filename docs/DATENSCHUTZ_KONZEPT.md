# Datenschutzkonzept (Entwurf) — Aurevia Factoring-Plattform

Version 3.00 · Stand 29.08.2026 · **ENTWURF — durch Datenschutzbeauftragten /
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

## 3. Technische und organisatorische Maßnahmen (Art. 32 DSGVO)

- Zugriffskontrolle: Rollenmodell (14 Rollen), 2FA-Pflicht intern,
  Deaktivierung statt Löschung, Startpasswörter mit Einmal-Anzeige.
- Übertragungskontrolle: TLS; keine öffentlichen Registrierungen.
- Eingabekontrolle: hash-verkettetes Audit-Log (wer, was, wann, von wo).
- Verfügbarkeitskontrolle: tägliche verschlüsselte Backups, Restore-Prozess.
- Trennungsgebot: Mandantenmodell, Medical Data Firewall, Datenraum mit
  Freigabesteuerung, Ablaufdaten und Wasserzeichen bei Dokumenten.
- Datenminimierung: Pseudonym-IDs, keine Patientenklarnamen, keine
  Steuernummern/Bankdaten in Dokumenten ohne Erfordernis.

## 4. Offene Punkte (vor Produktivbetrieb)

- Benennung Datenschutzbeauftragter, Verzeichnis von Verarbeitungstätigkeiten,
  Datenschutz-Folgenabschätzung (Gesundheitsbezug!), Lösch-/Aufbewahrungs-
  konzept (HGB/AO-Fristen vs. DSGVO), AV-Verträge (Hosting, Versicherer,
  Auskunfteien, Inkasso), Informationspflichten (Art. 13/14),
  Betroffenenrechte-Prozess, Meldewege Art. 33/34.
