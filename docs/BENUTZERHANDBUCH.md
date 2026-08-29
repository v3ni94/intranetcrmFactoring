# Benutzerhandbuch — Aurevia Intranet · CRM · Factoring-Plattform

Version 3.03 · Stand 29.08.2026 · Ein Produkt der Müller Holding AG
Gilt für die Plattform unter intranet.aurevia-factoring.de.

---

## 1. Anmeldung und Sicherheit

1. **Login**: E-Mail-Adresse und Passwort unter `/login`. Nach fünf Fehlversuchen
   greift eine Sperrzeit.
2. **Zwei-Faktor-Authentifizierung (2FA)**: Für alle internen Rollen sowie
   Investoren und Beiräte verpflichtend. Beim ersten Login führt das System durch
   die Einrichtung: QR-Code mit einer Authenticator-App scannen (Google
   Authenticator, Microsoft Authenticator, Authy) oder den manuellen Schlüssel
   eintippen, dann den 6-stelligen Code bestätigen.
3. **Wiederherstellungscodes**: Werden genau einmal angezeigt — sicher
   verwahren. Jeder Code funktioniert nur einmal.
4. **Sprache**: Umschalter DE | EN oben rechts.
5. **Passwort ändern**: Einstellungen → Profil.
6. Bei Problemen: Support-Ticket erstellen oder die Systemadministration
   kontaktieren (kann Passwörter zurücksetzen und Konten reaktivieren).

## 2. Navigation

Die Navigation ist in Gruppen gegliedert; sichtbar ist nur, was die eigene Rolle
nutzen darf (serverseitig erzwungen):

| Gruppe | Inhalte | Typische Rollen |
|---|---|---|
| Allgemein | Start, Verträge & Dokumente, Support, Hilfe & FAQ, Einstellungen | alle |
| Kundenportal | Meine Forderungen | Kunde |
| Investorenportal | Meine Kapitalbeziehung | Investor |
| Betrieb | Forderungen, Ankauf & Auszahlungen, Zahlungseingänge, Mahnwesen, Aufgaben | Operations, Risiko, Buchhaltung, Treasury |
| Vertrieb & Kunden | CRM, Kunden, Debitoren, Onboarding | Vertrieb, Operations, Compliance |
| Treasury & Finanzen | Kreditlinien, Bankkonten, Fazilitäten, Controlling & Kosten | Treasury, Controlling, Risiko |
| Steuerung & Aufsicht | Risiko & Compliance, Reporting, Audit, Projekt, Cap-Table | Geschäftsleitung, Compliance |

Seit Version 3.02 liegt **Administration** (Benutzer, Integrationen, Changelog,
Demo-Steuerung) nicht mehr in der Seitennavigation, sondern als Dropdown oben
rechts im Kopfbereich, sichtbar für Systemadministration, Geschäftsleitung und
Superadmin (Demo-Steuerung ist Superadmin vorbehalten). Details siehe Kapitel 11
und 14.

Gruppen mit Überschrift sind in der Seitennavigation standardmäßig eingeklappt;
ein Klick auf die Überschrift klappt die Gruppe auf oder zu. Die gewählte
Einstellung wird je Gruppe und Nutzer im Browser gemerkt und beim nächsten
Besuch wiederhergestellt (befindet sich die aktuelle Seite in einer Gruppe,
öffnet sie sich unabhängig von der gemerkten Wahl automatisch).

## 3. Rollen im Überblick

- **Kunde (Admin/Sachbearbeitung)**: reicht Forderungen ein, sieht ausschließlich
  Daten der eigenen Organisation, erstellt Support-Tickets. Keine 2FA-Pflicht.
- **Investor**: sieht die eigene Kapitalbeziehung (Zusagen, Ziehungen,
  Zinszahlungen), den eigenen Anteil am Plattformkapital und gekennzeichnete
  Modellrechnungen. Kein Zugriff auf Kunden- oder Debitorendaten.
- **Beirat/Aufsichtsrat**: eigenes Kennzahlen-Dashboard und freigegebene
  Board-Dokumente. Bewusst nicht operativ; interne Daten sind gesperrt.
- **Vertrieb/CRM**: Leads, Opportunities, Kundenanlage, Vertragsanbahnung.
- **Operations**: Forderungsprüfung, Ankaufsvorbereitung, Onboarding.
- **Kredit/Risiko (Marktfolge)**: Risiko-/Limitprüfung, Rating, Zweitvoten.
- **Debitorenbuchhaltung**: Zahlungszuordnung, Mahnwesen, Inkasso-Übergabe.
- **Treasury/Finance**: Auszahlungen, Bankkonten, Fazilitäten, Reports.
- **Controlling**: Kostenerfassung und Kostensicht.
- **Compliance**: KYC/GwG, Audit-Einsicht, Risiko-Übersicht.
- **Geschäftsleitung/Vorstand**: Vollzugriff inkl. aller Dashboards und
  letzter Eskalationsinstanz.
- **Systemadministration**: Benutzerverwaltung, Integrationen, technische Pflege.

## 4. Kundenpflege (Mediziner)

1. **Anlegen**: CRM → Lead erfassen → qualifizieren → Onboarding-Pipeline.
2. **Stammdaten**: Kunden → Detailseite: Adresse, Fachrichtung,
   **Branchensegment** (Arzt, Zahnarzt, Apotheke, Dentallabor, Tierarzt,
   Heilberufe, Pflege, MVZ/Klinik), **Kundentyp B2B/B2C**.
3. **KYC** (Pflicht vor Aktivierung): KYC/KYB-Prüfung, wirtschaftlich
   Berechtigte inkl. PEP-/Sanktionsscreening, Registerabgleich, Bonitätsauskunft.
4. **Rating**: Punkte 0–100 vergeben; das System leitet die Stufe (AAA–C) ab.
   Die Stufe bestimmt den Gebührenaufschlag beim Ankauf.
5. **Vertrag**: individuelle Konditionen (Bevorschussung, Gebühr, Zins,
   Höchstlaufzeit), Produktwahl inkl. echtes/unechtes und offenes/stilles
   Factoring; E-Signatur über den vorbereiteten Adapter.
6. **Kreditlinien**: Ankaufs-/Auszahlungslinie, Debitorenlimits; Linien über
   der Klumpenrisiko-Schwelle (Standard 30.000 EUR) werden zur Versicherung
   markiert.
7. **Kundenzugang**: Administration → Benutzer → Rolle Kunde + Organisation.

## 5. Investorenpflege

1. Organisation vom Typ Investor anlegen, Rating pflegen.
2. Fazilität anlegen: Zusage, Zinssatz, Rang, **Sonderkündigungsrecht ja/nein**
   und Kündigungsfrist.
3. Kapitalbewegungen als Fazilitätsereignisse erfassen (Ziehung, Zins, Tilgung).
4. Kündigung: ordentlich, per Sonderkündigungsrecht (nur wenn vereinbart) oder
   wegen Insolvenz des Investors — stets mit Grund, Frist und Audit-Eintrag.
5. Investorzugang über die Benutzerverwaltung.

## 6. Factoring-Prozess (Kurzfassung)

Einreichung (Kunde) → formale Prüfung → Risiko-/Limitprüfung → Ankauf berechnen
(inkl. Rating-Aufschlag) → **Zweitfreigabe durch andere Person (Vier-Augen)** →
Auszahlungsbatch → Erst- und Zweitfreigabe → SEPA → Bankbestätigung →
Zahlungseingang (auch Teilzahlungen) → Abrechnung mit Reservefreigabe.
Details: Prozessleitfaden.

## 7. Eskalation bei Ablehnung (Markt/Marktfolge)

Abgelehnte Forderung → „Zweitvotum Marktfolge anfordern" (mit Begründung) →
Marktfolge gibt frei oder lehnt ab → bei Ablehnung automatisch Vorlage beim
Vorstand → Vorstand entscheidet endgültig. Jede Stufe mit Begründungspflicht
und Audit-Protokoll.

## 8. Support-Tickets

Menü Support: Ticket mit Betreff, Kategorie und Beschreibung erstellen.
Kunden/Investoren sehen nur eigene Tickets. Interne Bearbeiter antworten,
nutzen interne Notizen (für Externe unsichtbar) und pflegen den Status
(offen → in Bearbeitung → beantwortet → geschlossen).

## 9. Reporting

- CSV-Exporte: Forderungen, Journal, DATEV-Buchungsstapel (alle auditiert).
- KPI-Report per E-Mail: sofort senden oder als Abo (täglich, wöchentlich,
  monatlich) einrichten; Versand morgens über den Scheduler.
- Dashboards mit Diagrammen (Altersstruktur, Ankaufsvolumen, Kosten).

## 10. Administration

- **Benutzer**: anlegen, bearbeiten (Personalakte, Rollentausch), Startpasswort/
  Zugangslink per E-Mail, Passwort-Reset, deaktivieren/reaktivieren sowie
  löschen, sofern keine Historie entgegensteht. Details siehe Kapitel 11.
- **Integrationen**: Status aller Adapter (Bank, KYC, Auskunftei
  Creditreform/SCHUFA vorbereitet, E-Signatur, DATEV, Inkasso,
  Warenkreditversicherung vorbereitet, u. a.).
- **Changelog**: alle Releases mit Datum, Uhrzeit und Verantwortlichem.
- **Demo-Steuerung**: Testdaten für Vorführzwecke einspielen/löschen sowie
  vollständiges Löschen aller Mandantendaten. Details siehe Kapitel 14.
- **Backups**: täglich 02:30 Uhr, AES-256-verschlüsselt; Integrität des
  Audit-Logs prüfbar per `php artisan aurevia:audit-verify`.

## 11. Benutzerverwaltung & Personalakte

Zugriff über Administration → Benutzer (Systemadministration, Geschäftsleitung;
Löschen und Superadmin-Rollenvergabe zusätzlich eingeschränkt, siehe unten).

1. **Anlegen**: Name, geschäftliche E-Mail (Login), Rolle, bei Kunden-Rollen
   zusätzlich die Kundenorganisation. Der Zugang wird als Willkommens-Mail mit
   zeitlich begrenztem Passwort-Setz-Link versendet; ist der Mailversand nicht
   konfiguriert, zeigt das System das Startpasswort einmalig an.
2. **Personalakte bearbeiten** (Administration → Benutzer → Bearbeiten):
   - *Konto & Rolle*: Name, E-Mail, Rolle, Kundenorganisation, **Eintritts-
     und Austrittsdatum**.
   - *Position & Kontakt*: Position, Abteilung, fachlicher und disziplinarischer
     Vorgesetzter, Telefon geschäftlich/privat, E-Mail privat.
   - *Person & Adresse*: Straße, PLZ, Ort, Geburtsdatum, **Steuer-ID**.
   - *Nachweise*: Personalausweis-Nummer und Gültigkeit, Datum der Vorlage von
     Führungszeugnis und SCHUFA-Auskunft, Führerschein-Klasse und Gültigkeit,
     freie HR-Notizen.
   - Steuer-ID und Personalausweis-Nummer werden **verschlüsselt** gespeichert
     und erscheinen in keinen Dokumenten oder Exporten. Original-Scans gehören
     in die geschützte Personalakte im Dokumentenmanagement, nicht in dieses
     Formular.
3. **Beschäftigungsfenster**: Ein Konto ist erst ab dem Eintrittsdatum nutzbar
   und wird nach dem Austrittsdatum automatisch gesperrt (Login und
   2FA-Freischaltung prüfen dies bei jedem Anmeldeversuch, ohne dass ein
   gesonderter Lauf nötig ist). Ergänzend bleibt die manuelle
   Deaktivierung/Reaktivierung verfügbar.
4. **Rollentausch**: Beim Ändern der Rolle wird die Kundenorganisation nur für
   Kunden-Rollen weitergeführt. Die Rolle „Superadmin (Demo-Steuerung)" darf
   nur durch einen bestehenden Superadmin vergeben oder entzogen werden.
5. **Löschen**: möglich, solange keine Vorgänge, Freigaben oder Tickets mit dem
   Konto verknüpft sind; besteht Historie, meldet das System dies und empfiehlt
   stattdessen die Deaktivierung, damit der Audit-Trail erhalten bleibt. Das
   eigene Konto sowie ein Superadmin-Konto (außer durch einen anderen
   Superadmin) können nicht gelöscht oder deaktiviert werden.
6. Jede Änderung (Anlage, Bearbeitung, Rollentausch, Löschung, Aktivierung/
   Deaktivierung, Passwort-Reset) wird im Audit-Log protokolliert.

## 12. Wissensdatenbank & Onboarding

- **Wissensdatenbank** (Hilfe & FAQ → Wissensdatenbank, `/hilfe/wissen/…`):
  zeigt Benutzerhandbuch, Prozessleitfaden, BaFin-Vorbereitungsdokumentation
  und Datenschutzkonzept unmittelbar aus den Markdown-Dokumenten des
  Repositorys als lesbare Seite an. Das Benutzerhandbuch ist für alle
  angemeldeten Rollen sichtbar; Prozessleitfaden, BaFin-Dokumentation und
  Datenschutzkonzept sind internen Rollen vorbehalten (Kunde, Investor und
  Beirat sehen diese Dokumente nicht).
- **Onboarding-Leitfaden** (Hilfe & FAQ → Onboarding, `/hilfe/onboarding`):
  geführter Durchklick durch alle Module mit Direktlinks, damit neue
  Mitarbeiter und Nutzer sich selbständig einarbeiten können.
- Die gesamte Oberfläche inkl. FAQ und Onboarding-Leitfaden liegt seit
  Version 3.02 vollständig zweisprachig vor (Umschalter DE | EN oben rechts).

## 13. Musterverträge & elektronische Signatur

Aus den hinterlegten Vertrags- bzw. Fazilitätsdaten lässt sich je Kunde oder
Investor unmittelbar ein Mustervertrag als PDF erzeugen:

1. **Erzeugen**: Bei einem Kundenvertrag (Verträge & Dokumente) bzw. einer
   Fazilität (Fazilitäten) über die Schaltfläche „Mustervertrag erzeugen" wird
   ein Factoring- bzw. Fazilitätsvertrag im Aurevia-Layout aus den aktuellen
   Systemdaten (Konditionen, Linien, Laufzeiten, Kündigungsregeln) als PDF
   erstellt und deutlich als **MUSTER/ENTWURF** gekennzeichnet. Das Dokument
   wird sofort automatisch an die Gegenseite freigegeben, damit diese es im
   Datenraum einsehen und signieren kann.
2. **Einfache elektronische Signatur**: Unter Verträge & Dokumente zeigt jedes
   Vertragsdokument den Signaturstatus beider Seiten (Gesellschaft/Gegenseite).
   Zum Unterzeichnen gibt die Person ihren Namen ein und bestätigt die
   Erklärung; das System speichert Name und Zeitstempel und rendert den
   Signaturblock im PDF neu. Für die Gesellschaft dürfen ausschließlich
   Geschäftsleitung und Superadmin unterzeichnen; für die Gegenseite der an
   die jeweilige Organisation gebundene Nutzer oder, zur Erfassung einer
   anderweitig schriftlich vorliegenden Zustimmung, ebenfalls Geschäftsleitung
   oder Superadmin. Eine bereits geleistete Signatur einer Seite kann nicht
   erneut abgegeben werden.
3. Sobald beide Seiten unterzeichnet haben, gilt der Vertrag als vollständig
   signiert; jede Signatur wird im Audit-Log protokolliert.
4. **Hinweis**: Es handelt sich um eine einfache elektronische Signatur im
   System (Name und Zeitstempel), nicht um eine qualifizierte elektronische
   Signatur. Die rechtliche Verwendbarkeit für den jeweiligen Zweck ist vor
   Produktivnutzung durch Rechtsberatung zu prüfen; siehe auch
   BaFin-Vorbereitungsdokumentation.

## 14. Testdaten-Verwaltung

Zugriff ausschließlich über die Rolle Superadmin (Demo-Steuerung), Administration
→ Demo-Steuerung.

- **Testdaten für Vorführzwecke einspielen**: erzeugt auf dem aktuellen
  Mandanten einen umfangreichen, vollständig fiktiven Datensatz: 100
  Medizin-Kunden über die Branchensegmente Arzt, Zahnarzt, Apotheke,
  Dentallabor, Tierarzt, Heilberufe, Pflege, MVZ/Klinik und Sonstige, jeweils
  mit Vertrag, Rating und Kreditlinie; drei Investoren mit monatlicher
  Ausschüttungshistorie seit 2025; Forderungen, Ankäufe und Zahlungen über
  2025/2026 verteilt; Betriebskosten je Monat; getrennte Abwicklungskonten für
  Kunden- und Investorengelder sowie unterschriebene Musterverträge für fünf
  Kunden und alle Investoren. Alle erzeugten Datensätze sind durchgängig als
  Testdaten gekennzeichnet. Das Einspielen ist nur möglich, solange noch keine
  Testdaten vorhanden sind (kein doppeltes Einspielen).
- **Testdaten löschen**: entfernt ausschließlich die als Testdaten markierten
  Datensätze; eigene, nicht als Testdaten markierte Daten bleiben erhalten.
  Erfordert die erneute Eingabe des eigenen Passworts. Der Vorgang ist
  endgültig und unwiderruflich; anschließend können die Testdaten jederzeit
  erneut eingespielt werden.
- **Alles löschen**: löscht sämtliche Bewegungs- und Stammdaten des Mandanten,
  einschließlich selbst angelegter Kunden, Verträge und Vorgänge. Nutzer,
  Rollen und der Mandant selbst bleiben erhalten. Erfordert zusätzlich zum
  Passwort die wörtliche Eingabe der Bestätigungsphrase „ALLES LÖSCHEN". Der
  Vorgang ist endgültig und unwiderruflich und lässt sich nicht rückgängig
  machen.
- Jeder Einspiel- und Löschvorgang wird protokolliert (Aktion, ausführende
  Person, Anzahl betroffener Datensätze, Zeitpunkt) und im Audit-Log
  vermerkt.

---

*Dieses Handbuch beschreibt die Systemfunktionen. Rechtliche, steuerliche und
aufsichtsrechtliche Fragen sind durch Rechtsanwalt/Steuerberater zu klären.*
