# Prozessleitfaden — Aurevia Factoring-Plattform

Version 3.00 · Stand 29.08.2026 · ENTWURF (Arbeitsgrundlage, nicht beschlossen)

Dieser Leitfaden beschreibt die Soll-Prozesse der Plattform mit Rollen,
Kontrollen und Systemunterstützung. Er orientiert sich an bankenüblichen
Organisationsanforderungen (insbesondere der Trennung von Markt und Marktfolge
sowie durchgängigen Funktionstrennungen); die verbindliche Ausgestaltung ist
vor Aufnahme des erlaubnispflichtigen Geschäfts mit Rechtsberatung und
Wirtschaftsprüfer festzulegen.

---

## P1 Kunden-Onboarding und KYC

**Beteiligte**: Vertrieb (Markt), Operations, Compliance.

1. Lead-Erfassung und Qualifizierung (CRM).
2. Datenaufnahme: Stammdaten, Branchensegment, B2B/B2C, Unterlagen.
3. **KYC/GwG-Strecke** (Compliance, vor Aktivierung zwingend):
   - Identifizierung der Organisation und der handelnden Personen,
   - Erhebung und Prüfung der wirtschaftlich Berechtigten (UBO),
   - PEP-/Sanktionslistenscreening je UBO,
   - Register-/Berufsregisterabgleich,
   - Bonitätsauskunft (Adapter Creditreform/SCHUFA, derzeit Sandbox),
   - Risikoklassifizierung und Festlegung des Überprüfungsturnus.
4. Internes Rating (Marktfolge): Punkte 0–100 → Stufe AAA–C.
5. Vertrag (individuelle Konditionen, Produkt echtes/unechtes, offen/still),
   E-Signatur, Kreditlinien-Einrichtung mit Limitbegründung.
6. Kundenzugang anlegen; Startpasswort sicher übergeben.

**Kontrollen**: kein aktiver Vertrag ohne abgeschlossenes KYC; alle Schritte
auditiert; Limitvergabe nur durch Marktfolge/Geschäftsleitung.

## P2 Forderungsankauf

**Beteiligte**: Kunde, Operations (Markt), Kredit/Risiko (Marktfolge).

1. Einreichung durch den Kunden (nur eigene, aktive Verträge referenzierbar;
   Rechnungsnummer je Kunde eindeutig).
2. Formale Prüfung (Vollständigkeit, Plausibilität).
3. Automatische Risiko-/Limitprüfung (Regeln: Linien, Debitorenlimits,
   Laufzeiten, Konzentration).
4. Ankaufsberechnung: Bevorschussung, Sicherheitseinbehalt, Factoringgebühr
   **inklusive ratingabhängigem Aufschlag**, Zinsschätzung. Negative
   Auszahlungen werden systemseitig abgewiesen.
5. **Vier-Augen-Prinzip**: Zweitfreigabe zwingend durch eine andere Person;
   technisch erzwungen und gegen parallele Doppelfreigabe gesperrt.
6. Buchung im doppischen Nebenbuch (ausgeglichene Journalbuchung, Korrektur
   nur durch Storno).

## P3 Eskalation bei Ablehnung (Markt/Marktfolge-Prinzip)

1. Ablehnung (Regelprüfung, Bonität oder manuell) mit dokumentiertem Grund.
2. Der Markt kann mit Begründung ein **Zweitvotum der Marktfolge** anfordern.
3. Marktfolge (Kredit/Risiko) entscheidet mit Begründungspflicht:
   - Freigabe → weiter im Regelprozess (Vier-Augen beim Ankauf bleibt),
   - Ablehnung → automatische **Eskalation an den Vorstand**.
4. Vorstand entscheidet endgültig (Freigabe oder endgültige Ablehnung).
5. Der Aufsichtsrat entscheidet nicht operativ mit (Überwachungsorgan);
   Eskalationen und Überstimmungen werden ihm aggregiert berichtet.

Alle Voten mit Zeitstempel, Person und Begründung in der revisionssicheren
Audit-Kette.

## P4 Auszahlung

1. Treasury bildet Auszahlungsbatch aus freigegebenen Ankäufen.
2. Erstfreigabe → Zweitfreigabe (andere Person, Vier-Augen) → SEPA-Datei
   (pain.001, derzeit Demo-Datei) → Bankbestätigung → Buchung.
3. Idempotenzschlüssel je Auszahlung verhindert Doppelanweisungen.

## P5 Zahlungseingang und Abrechnung

1. Kontoumsatz-Import (camt, derzeit Demo) mit Matching-Vorschlägen.
2. Manuelle Bestätigung der Zuordnung; Teilzahlungen kumulieren korrekt;
   eine Banktransaktion ist nur einmal zuordenbar.
3. Nach vollständiger Zahlung: Abrechnung, Reservefreigabe.

## P6 Mahnwesen, Streitfälle, Ausfall

1. Überfälligkeits-Markierung automatisch (täglich 03:00 Uhr).
2. Fallanlage nur bei offenem Zahlungsanspruch; ein offener Fall je Forderung.
3. Ausfall angekaufter Forderungen wird über das Verlustkonto ausgebucht.
4. Inkasso-Übergabe über den vorbereiteten Adapter.

## P7 Investoren- und Fazilitätenverwaltung

1. Fazilität mit Zusage, Zins, Rang, Sonderkündigungsrecht und Frist.
2. Ereignisse: Ziehung, Zinszahlung, Tilgung, Covenant-Check, Kündigung.
3. Kündigungsgründe: ordentlich, Sonderkündigung (nur wenn vereinbart),
   Insolvenz des Investors — jeweils protokolliert.
4. Investor-Reporting nach vereinbarter Detailtiefe; Modellrechnungen sind
   gekennzeichnet (keine Zusage, keine Anlageberatung).

## P8 Klumpenrisiko und Warenkreditversicherung

1. Schwelle (Standard 30.000 EUR, konfigurierbar): Kreditlinien darüber gelten
   als Klumpenrisiko und werden in der Übersicht markiert.
2. Je Linie: Versicherer, versicherte Summe, Status (nicht versichert,
   beantragt, versichert, abgelehnt).
3. Zielprozess mit dem Versicherer (Adapter vorbereitet): monatliche
   Linienmeldung (Betrag, internes Rating, pseudonymisierter Debitor),
   Rückmeldung Annahme/Prämie; konkrete Ausgestaltung ist zu verhandeln.

## P9 Controlling

Kostenerfassung nach Kategorien (Personal, IT, Refinanzierung, …) durch
Controlling/Treasury; Monats- und Kategoriesichten; Grundlage für
Deckungsbeitrags- und Wirtschaftlichkeitsbetrachtung der Geschäftsleitung.

## P10 Berichtswesen

- Dashboards je Rolle (Kunde, Mitarbeiter, Risiko, Vorstand, Beirat, Investor).
- CSV-Exporte und DATEV-Buchungsstapel (auditiert).
- KPI-Report per E-Mail manuell und automatisch (täglich/wöchentlich/monatlich).

## P11 IT-Betrieb und Notfall

- Tägliches AES-256-verschlüsseltes Backup (02:30 Uhr), Aufbewahrung 14 Tage,
  dokumentierter Restore-Prozess (DEPLOYMENT.md).
- Audit-Kette täglich prüfbar (`aurevia:audit-verify`).
- Benutzer werden deaktiviert, nie gelöscht (Nachvollziehbarkeit).
- Versionierung mit Changelog (Version, Zeitstempel, Verantwortlicher).
