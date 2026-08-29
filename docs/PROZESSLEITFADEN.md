# Prozessleitfaden — Aurevia Factoring-Plattform

Version 3.03 · Stand 29.08.2026 · ENTWURF (Arbeitsgrundlage, nicht beschlossen)

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
- Benutzer werden vorrangig deaktiviert; eine Löschung ist nur ohne verknüpfte
  Historie möglich (siehe P12) und bleibt die Ausnahme.
- Versionierung mit Changelog (Version, Zeitstempel, Verantwortlicher).

## P12 Personalpflege (HR) mit Ein- und Austritt

**Beteiligte**: Systemadministration, Geschäftsleitung; Superadmin-Rollen
zusätzlich nur durch Superadmin.

1. Konto anlegen: Name, geschäftliche E-Mail, Rolle, ggf. Kundenorganisation;
   Zugangsdaten gehen als Willkommens-Mail mit Passwort-Setz-Link hinaus.
2. Personalakte pflegen: Position, Berichtslinien (fachlich/disziplinarisch),
   Kontaktdaten, Adresse, Geburtsdatum, Steuer-ID und Ausweisnummer
   (verschlüsselt gespeichert), Nachweise (Führungszeugnis, SCHUFA,
   Führerschein), Ein- und Austrittsdatum.
3. **Beschäftigungsfenster**: Das Konto ist erst ab dem Eintrittsdatum nutzbar
   und wird nach dem Austrittsdatum automatisch für Login und
   2FA-Freischaltung gesperrt, ohne dass ein gesonderter Vorgang ausgelöst
   werden muss. Für den fristgerechten Zugriffsentzug bei Austritt bleibt die
   rechtzeitige Pflege des Austrittsdatums maßgeblich (organisatorische
   Verantwortung der Personalpflege, nicht allein technische Kontrolle).
4. Rollentausch: Kundenorganisation wird nur bei Kunden-Rollen weitergeführt;
   die Superadmin-Rolle darf ausschließlich durch einen bestehenden Superadmin
   vergeben oder entzogen werden.
5. Austritt/Beendigung: Austrittsdatum setzen (automatische Sperre) und, wenn
   keine Historie (Vorgänge, Freigaben, Tickets) entgegensteht, Konto löschen;
   andernfalls deaktivieren, damit der Audit-Trail erhalten bleibt.

**Kontrollen**: jede Anlage, Änderung, Rollenvergabe und Löschung/Deaktivierung
wird im Audit-Log protokolliert; Steuer-ID und Ausweisnummer erscheinen in
keinen Dokumenten oder Exporten.

## P13 Vertragserzeugung und elektronische Signatur

**Beteiligte**: zuständige interne Fachrolle (Erzeugung), Geschäftsleitung/
Superadmin (Signatur Gesellschaft), Kunde bzw. Investor (Signatur Gegenseite).

1. Mustervertrag erzeugen: aus einem Kundenvertrag bzw. einer Fazilität wird
   ein Factoring- bzw. Fazilitätsvertrag als PDF direkt aus den hinterlegten
   Systemdaten (Konditionen, Linien, Laufzeiten) erstellt, deutlich als
   MUSTER/ENTWURF gekennzeichnet.
2. Automatische Freigabe: das Dokument wird mit der Erzeugung sofort an die
   Gegenseite (Kundenorganisation bzw. Investor) freigegeben.
3. Signatur Gesellschaft: durch Geschäftsleitung oder Superadmin, mit Namen
   und Zeitstempel.
4. Signatur Gegenseite: durch den an die Organisation gebundenen externen
   Nutzer oder, zur Erfassung einer anderweitig schriftlich vorliegenden
   Zustimmung, durch Geschäftsleitung/Superadmin; ebenfalls mit Namen und
   Zeitstempel.
5. Nach jeder Signatur wird der Signaturblock im PDF neu gerendert; sobald
   beide Seiten unterzeichnet haben, gilt der Vertrag als vollständig
   signiert.

**Kontrollen**: jede Signatur ist auditiert (Person, Seite, Zeitstempel); eine
bereits geleistete Signatur kann nicht erneut abgegeben werden. Es handelt
sich um eine einfache elektronische Signatur im System, keine qualifizierte
elektronische Signatur; die rechtliche Verwendbarkeit ist vor
Produktivnutzung mit Rechtsberatung zu klären (siehe BaFin-Dokumentation).

## P14 Testdaten-Lebenszyklus

**Beteiligte**: ausschließlich Superadmin (Demo-Steuerung).

1. Einspielen: ein vollständig fiktiver Vorführ-Datensatz (100 Medizin-Kunden,
   drei Investoren mit Ausschüttungshistorie seit 2025, Forderungen, Kosten,
   Abwicklungskonten, signierte Musterverträge) wird auf dem aktuellen
   Mandanten erzeugt; alle Datensätze sind als Testdaten gekennzeichnet. Nur
   möglich, solange noch keine Testdaten vorhanden sind.
2. Betrieb: Testdaten stehen für Vorführungen, Schulungen und
   Funktionsprüfungen zur Verfügung, ohne echte Geschäftsvorfälle zu
   berühren.
3. Löschen der Testdaten: entfernt ausschließlich als Testdaten gekennzeichnete
   Datensätze, eigene Daten bleiben erhalten; erfordert die erneute Eingabe
   des Passworts; endgültig und unwiderruflich.
4. Alles löschen (Sonderfall): entfernt sämtliche Bewegungs- und Stammdaten des
   Mandanten einschließlich selbst angelegter Daten, Nutzer/Rollen/Mandant
   bleiben erhalten; erfordert zusätzlich zum Passwort die Bestätigungsphrase
   „ALLES LÖSCHEN"; endgültig und unwiderruflich.

**Kontrollen**: jeder Einspiel- und Löschvorgang wird protokolliert (Aktion,
Person, Anzahl Datensätze, Zeitpunkt) und im Audit-Log vermerkt; die
Trennung über das Kennzeichen `is_demo` ist Voraussetzung für die
rückstandslose Löschbarkeit.

## P15 Monatliche Zins-Fortschreibung (Fazilitäten)

**Beteiligte**: Scheduler (technisch), Treasury/Finance (Kontrolle).

1. Am 1. eines jeden Monats, 05:30 Uhr, schreibt der Scheduler
   (`aurevia:accrue-interest`) für jede aktive Fazilität mit gezogenem Kapital
   die monatliche, nachschüssige Zinsausschüttung für den Vormonat fort,
   sofern für den Zeitraum noch kein Zinszahlungs-Ereignis besteht
   (Doppelausführung ausgeschlossen).
2. Die erzeugten Ereignisse erscheinen im Investor-Reporting und in den
   Fazilitätenübersichten wie manuell erfasste Zinszahlungen.
3. Treasury/Finance prüft die fortgeschriebenen Beträge im Rahmen der
   turnusmäßigen Kontrolle der Investorenbeziehungen.
