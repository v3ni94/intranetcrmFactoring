# Benutzerhandbuch — Aurevia Intranet · CRM · Factoring-Plattform

Version 3.00 · Stand 29.08.2026 · Ein Produkt der Müller Holding AG
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
| Administration | Benutzer, Integrationen, Changelog, Demo-Steuerung | Systemadministration, Geschäftsleitung |

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

- **Benutzer**: anlegen, Rolle zuweisen, Startpasswort (Einmal-Anzeige),
  Passwort-Reset, deaktivieren/reaktivieren. Kein Löschen (Audit-Trail).
- **Integrationen**: Status aller Adapter (Bank, KYC, Auskunftei
  Creditreform/SCHUFA vorbereitet, E-Signatur, DATEV, Inkasso,
  Warenkreditversicherung vorbereitet, u. a.).
- **Changelog**: alle Releases mit Datum, Uhrzeit und Verantwortlichem.
- **Backups**: täglich 02:30 Uhr, AES-256-verschlüsselt; Integrität des
  Audit-Logs prüfbar per `php artisan aurevia:audit-verify`.

---

*Dieses Handbuch beschreibt die Systemfunktionen. Rechtliche, steuerliche und
aufsichtsrechtliche Fragen sind durch Rechtsanwalt/Steuerberater zu klären.*
