# Deployment-Anleitung: Aurevia Intranet auf PHP-Webspace

Diese Anleitung führt Schritt für Schritt vom leeren Webspace bis zum
laufenden Intranet unter einer eigenen Subdomain. Sie richtet sich an
klassisches Shared-/Managed-Hosting mit cPanel, Plesk oder einer
vergleichbaren Oberfläche (z. B. IONOS, Strato, All-Inkl, Hetzner Webhosting).
Screenshots unterscheiden sich je Anbieter, die Reihenfolge und Begriffe
sind bei den meisten Anbietern jedoch ähnlich.

**Vorher benötigt:**

- Zugang zum Hosting-Kundencenter (DNS-Verwaltung, Datenbank-Verwaltung)
- FTP-/SFTP-Zugangsdaten (Host, Benutzername, Passwort, Port) für FileZilla
- PHP 8.2 oder höher mit den Erweiterungen: `pdo_mysql`, `mbstring`, `bcmath`,
  `ctype`, `fileinfo`, `openssl`, `tokenizer`, `xml`, `zip`, `gd` (Standard bei
  den meisten Webhostern; bei Unsicherheit beim Support nachfragen)
- SSH-Zugang ist **nicht zwingend** erforderlich (die Anleitung geht vom
  Normalfall ohne SSH aus), beschleunigt aber Schritt 5 und 7 erheblich,
  falls vorhanden
- Die fertig gepackte Datei `aurevia-intranet-deployment.zip` (siehe Anhang
  dieser Konversation) — enthält bereits alle PHP-Abhängigkeiten
  (`vendor/`) und die fertig gebauten Frontend-Assets (`public/build/`), es
  ist **kein** `composer` oder `npm` auf dem Webspace nötig

---

## 1. Subdomain mit dem Webspace verknüpfen

1. Im Hosting-Kundencenter zum Bereich **Domains** bzw. **Subdomains**
   wechseln.
2. Neue Subdomain anlegen, z. B. `intranet.aurevia-factoring.de`
   (Hauptdomain `aurevia-factoring.de` muss dort bereits verwaltet werden).
3. Als Docroot/Verzeichnis einen **neuen, leeren Ordner** angeben, z. B.
   `/intranet` oder `/httpdocs/intranet` — je nach Hoster-Konvention. Diesen
   Ordner braucht FileZilla in Schritt 3 wieder.
4. Speichern und die DNS-Ausbreitung abwarten (bei den meisten Hostern, die
   Subdomain und DNS im selben System verwalten, sofort bis wenige Minuten;
   bei getrennter Nameserver-Verwaltung bis zu 24 Stunden). Prüfen mit:
   ```
   nslookup intranet.aurevia-factoring.de
   ```
5. Sobald die Subdomain angelegt ist, im selben Bereich **SSL/TLS**
   aktivieren (meist "Let's Encrypt" oder "AutoSSL", ein Klick). Ohne
   gültiges SSL-Zertifikat funktionieren Login und Session-Cookies nicht
   sicher — SSL ist für dieses Intranet zwingend, nicht optional
   (`SESSION_SECURE_COOKIE=true` in der `.env`, siehe Schritt 5).
6. Merken: Der **Docroot muss später auf `public/`** innerhalb des
   hochgeladenen Pakets zeigen, nicht auf den Projektordner selbst (siehe
   Schritt 4). Manche Hoster erlauben, das Docroot direkt auf einen
   Unterordner zu legen — falls ja, gleich `.../intranet/public` als Docroot
   eintragen und in Schritt 3 den Projektordner eine Ebene darüber hochladen.

## 2. FileZilla einrichten

1. FileZilla herunterladen und installieren (https://filezilla-project.org,
   nur den **FileZilla Client**, nicht den Server).
2. Datei → Servermanager → **Neue Seite**.
3. Eingeben:
   - **Protokoll:** SFTP, falls vom Hoster angeboten (verschlüsselt),
     ansonsten FTP mit TLS-Verschlüsselung ("FTP über explizites TLS")
   - **Host:** vom Hoster mitgeteilt (z. B. `ftp.aurevia-factoring.de` oder
     eine IP-Adresse)
   - **Port:** üblicherweise 22 (SFTP) oder 21 (FTP)
   - **Benutzername / Passwort:** vom Hoster mitgeteilt
4. **Verbinden.**

## 3. Projektdateien hochladen

1. Das mitgelieferte `aurevia-intranet-deployment.zip` lokal entpacken.
2. In FileZilla rechts (Server) in den in Schritt 1.3 angelegten Ordner
   navigieren (z. B. `/intranet`).
3. Links (lokal) in den entpackten Projektordner navigieren.
4. **Alle Dateien und Ordner** markieren (inkl. versteckter Dateien wie
   `.env.example` und `.gitignore` — in FileZilla unter Server →
   "Versteckte Dateien anzeigen" aktivieren) und per Rechtsklick →
   **Hochladen** übertragen.
5. Das dauert je nach Verbindung 10–30 Minuten (rund 16.000 Dateien,
   ca. 145 MB entpackt, hauptsächlich PHP-Bibliotheken unter `vendor/`).
   FileZilla im Reiter **Warteschlange** beobachten; bei Abbrüchen bietet
   FileZilla automatisch einen Wiederaufnahme-Button an.
6. Nach Abschluss prüfen: Serverseitig muss die Ordnerstruktur `app/`,
   `bootstrap/`, `config/`, `database/`, `public/`, `resources/`, `routes/`,
   `storage/`, `vendor/` direkt im hochgeladenen Ordner liegen.

### Docroot korrekt setzen

Der Webserver darf **niemals** auf den Projektordner selbst zeigen, sondern
ausschließlich auf den darin liegenden Unterordner `public/` — sonst sind
`.env`, `app/`, `vendor/` etc. über das Internet lesbar.

- **Bevorzugt:** Docroot im Hosting-Kundencenter direkt auf
  `.../intranet/public` stellen (siehe Schritt 1.6).
- **Falls der Hoster das nicht erlaubt** (Docroot fest auf den
  Subdomain-Ordner selbst): eine Ebene höher hochladen und im
  Subdomain-Ordner eine `index.php` mit Weiterleitung sowie eine `.htaccess`
  einrichten, die alle Aufrufe an `intranet/public` durchreicht. Das ist
  fehleranfälliger — falls möglich, immer die erste Variante wählen.

## 4. Dateiberechtigungen setzen

Über FileZilla per Rechtsklick → **Dateiberechtigungen**, oder falls
vorhanden über den Datei-Manager des Hosting-Kundencenters:

- `storage/` und alle Unterordner: **775** (rekursiv)
- `bootstrap/cache/`: **775**
- Alle übrigen Dateien/Ordner: Standard (meist bereits korrekt aus dem
  ZIP-Export)

Falls der PHP-Prozess unter einem anderen Benutzer läuft als der
FTP-Benutzer (beim Hoster erfragen), ggf. **777** für `storage/` und
`bootstrap/cache/` setzen — nur wenn 775 nicht ausreicht, da 777 großzügiger
ist als nötig.

## 5. Datenbank anlegen

1. Im Hosting-Kundencenter zum Bereich **Datenbanken** (MySQL/MariaDB)
   wechseln.
2. Neue Datenbank anlegen, z. B. `aurevia_intranet`. **Zeichensatz:
   `utf8mb4`, Kollation `utf8mb4_unicode_ci`** auswählen (wichtig für
   Umlaute und Emojis in Freitextfeldern).
3. Neuen Datenbankbenutzer mit einem **starken, generierten Passwort**
   anlegen und der Datenbank mit vollen Rechten zuordnen.
4. Host, Datenbankname, Benutzername und Passwort notieren — diese werden
   in Schritt 6 in die `.env` eingetragen. Der Host ist bei den meisten
   Shared-Hostern `localhost` oder `127.0.0.1`, bei manchen ein eigener
   Datenbankserver-Hostname (im Kundencenter angegeben).
5. Datenbank leer lassen — die Tabellen werden in Schritt 7 automatisch
   per Migration angelegt, kein manuelles SQL-Import nötig.

## 6. `.env` konfigurieren

1. Auf dem Server liegt bereits `.env.example`. Diese Datei über den
   Datei-Manager des Hosting-Kundencenters (oder FileZilla:
   herunterladen → bearbeiten → wieder hochladen) zu `.env` kopieren.
2. Folgende Werte eintragen bzw. anpassen:

   ```env
   APP_NAME="Aurevia Intranet"
   APP_ENV=production
   APP_KEY=                          # wird in Schritt 7 automatisch erzeugt
   APP_DEBUG=false
   APP_URL=https://intranet.aurevia-factoring.de

   AUREVIA_DEMO_MODE=false           # produktiv immer false

   DB_CONNECTION=mysql
   DB_HOST=localhost                 # siehe Schritt 5.4
   DB_PORT=3306
   DB_DATABASE=aurevia_intranet
   DB_USERNAME=<Datenbankbenutzer aus Schritt 5>
   DB_PASSWORD=<Datenbankpasswort aus Schritt 5>

   MAIL_MAILER=smtp
   MAIL_HOST=<SMTP-Server des Hosters oder Mailanbieters>
   MAIL_PORT=465
   MAIL_USERNAME=<Postfach>
   MAIL_PASSWORD=<Passwort>
   MAIL_FROM_ADDRESS="intranet@aurevia-factoring.de"

   AUREVIA_BACKUP_KEY=<langer, zufällig generierter Schlüssel>
   ```

   Für `AUREVIA_BACKUP_KEY` z. B. lokal `openssl rand -base64 32` ausführen
   und den erzeugten Wert eintragen. Diesen Schlüssel zusätzlich außerhalb
   des Servers sicher verwahren (Passwortmanager) — ohne ihn lassen sich
   spätere Backups nicht entschlüsseln.

3. **Wichtig:** `APP_DEBUG=false` und `AUREVIA_DEMO_MODE=false` müssen für
   den Produktivbetrieb zwingend so gesetzt sein. `APP_DEBUG=true` zeigt bei
   Fehlern Stacktraces samt Datenbankzugangsdaten öffentlich an.

## 7. Migrationen ausführen

Alle folgenden Befehle müssen im Projektordner ausgeführt werden. Zwei
Wege, je nachdem was der Hoster anbietet:

### Variante A: SSH-Zugang vorhanden

```bash
ssh <benutzer>@<host>
cd /pfad/zum/intranet-ordner
php artisan key:generate --force
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

`--force` ist nötig, weil `APP_ENV=production` sonst interaktiv
nachfragt. `migrate` legt alle Tabellen an — **ohne** Demodaten, da
`AureviaDemoDataSeeder` bewusst nicht automatisch mitläuft (Abschnitt 22).

### Variante B: Kein SSH, nur PHP über Cronjob/Web

Viele Hoster bieten im Kundencenter einen Menüpunkt **Cronjobs** an, über
den sich einmalige PHP-CLI-Befehle ausführen lassen, auch ohne dauerhaften
SSH-Zugang. Dort einmalig ausführen (danach den Cronjob wieder löschen,
er wird nur einmal gebraucht):

```
php /pfad/zum/intranet-ordner/artisan key:generate --force
php /pfad/zum/intranet-ordner/artisan migrate --force
php /pfad/zum/intranet-ordner/artisan config:cache
```

### Variante B-IONOS: Cronjob-Feld erlaubt keine Leerzeichen

Bei IONOS (und einigen anderen Hostern) erlaubt das UnixCron-Feld „Befehl"
nur die Zeichen `a-z A-Z 0-9 - _ . /` — also **keine Leerzeichen** und damit
keine direkten artisan-Befehle. Lösung: die Befehle in ein Shell-Skript
packen und im Cronjob nur den Skript-Pfad eintragen.

1. Vorlage `deploy/ionos-deploy.sh.example` aus diesem Repository nehmen,
   den `BASE`-Pfad sowie E-Mail/Passwort für den Admin anpassen.
2. Als `deploy.sh` in den Projekt-Hauptordner hochladen (gleiche Ebene wie
   `artisan`), per FileZilla Rechtsklick → Dateiberechtigungen → **755**.
3. Im Cronjob-Formular: Typ **UnixCron**, als Befehl **nur den Pfad**
   `/homepages/XX/dXXXXXXXXX/htdocs/intranet/deploy.sh`, Intervall täglich
   mit dem nächstgelegenen Zeitfenster.
4. Nach dem Lauf: Cronjob löschen und `deploy.sh` vom Server entfernen
   (sie enthält das initiale Admin-Passwort).

Der absolute Pfad (`/homepages/…`) steht im IONOS-Datei-Manager in der
Pfadanzeige bzw. bei den FTP-Zugangsdaten.

Falls der Hoster überhaupt keine CLI-Ausführung erlaubt (reines
FTP-only-Hosting ohne Cron/SSH), ist dieses Projekt auf diesem Tarif nicht
lauffähig — Laravel benötigt zwingend PHP-CLI-Zugriff für Migrationen und
den Scheduler. In dem Fall beim Hoster auf einen Tarif mit Cronjob-Funktion
wechseln.

## 8. Rollen, Mandant und ersten Benutzer anlegen

Da produktiv **keine** Demodaten geseedet werden, existieren nach Schritt 7
weder Rollen noch ein Mandant noch ein Login. Dafür gibt es den
idempotenten Init-Befehl (per SSH oder als Zeile im `deploy.sh`-Skript,
siehe Variante B-IONOS oben — dort ist er bereits enthalten):

```bash
php artisan aurevia:init --admin-email=timo@muellerhv.de --admin-password='<STARKES-PASSWORT-HIER>'
```

Der Befehl legt in einem Durchgang an: alle 13 Rollen, den
Produktiv-Mandanten (`aurevia-produktiv`) und den ersten Admin-Benutzer mit
den Rollen Geschäftsleitung und Systemadministration. Wird
`--admin-password` weggelassen, erzeugt der Befehl ein sicheres
Zufallspasswort und gibt es einmalig in der Ausgabe aus (bei Cron-Ausführung
landet die Ausgabe in der Cron-Benachrichtigungs-E-Mail des Hosters).
Mehrfaches Ausführen ist unschädlich — bestehende Benutzer werden nie
überschrieben.

Beim ersten Login erzwingt die Anwendung die Einrichtung der
Zwei-Faktor-Authentifizierung (MFA) — sie ist für alle internen Rollen
verpflichtend.

Das anfängliche Passwort sollte einmalig, individuell und stark sein
(mindestens 12 Zeichen, der Befehl lehnt kürzere ab) und nach der ersten
Anmeldung geändert werden, wenn es in einer Skriptdatei stand.

## 9. Cronjob für Scheduler und Backup einrichten

Im Hosting-Kundencenter unter **Cronjobs** folgenden Job anlegen, der
**jede Minute** läuft:

```
* * * * * php /pfad/zum/intranet-ordner/artisan schedule:run >> /dev/null 2>&1
```

Dieser eine Cronjob deckt sowohl das tägliche verschlüsselte Backup ab
(`aurevia:backup`, läuft automatisch 02:30 Uhr, siehe `routes/console.php`)
als auch alle künftigen geplanten Aufgaben. Falls der Hoster keine
minütlichen Cronjobs erlaubt, den kleinstmöglichen verfügbaren Abstand
wählen (z. B. alle 5 Minuten) — der Scheduler prüft bei jedem Aufruf, ob
eine Aufgabe fällig ist, und führt sie dann nach.

## 10. Funktionsprüfung (Checkliste)

- [ ] `https://intranet.aurevia-factoring.de/login` lädt ohne Fehler, mit
      gültigem SSL-Zertifikat (Schloss-Symbol im Browser)
- [ ] Login mit dem in Schritt 8 angelegten Konto funktioniert, inkl.
      MFA-Einrichtungszwang beim ersten Login
- [ ] `php artisan about` (per SSH/Cronjob) zeigt `Environment: production`,
      `Debug Mode: OFF`, Datenbankverbindung `OK`
- [ ] Ein Dokument lässt sich hochladen und wieder herunterladen
      (Dokumente liegen geschützt unter `storage/app`, die Auslieferung
      läuft über die Anwendung selbst — kein `storage:link` nötig)
- [ ] `php artisan aurevia:backup` manuell einmal ausführen und prüfen,
      dass unter `storage/app/backups` eine `.zip.enc`-Datei entsteht
- [ ] Nach 24 Stunden prüfen, ob der Cronjob aus Schritt 9 das Backup
      automatisch um 02:30 Uhr erzeugt hat

## Fehlerbilder und Lösungen

- **„Forbidden – You don't have permission to access this resource"** nach
  der Docroot-Umstellung: Fast immer fehlt die versteckte Datei
  `public/.htaccess` auf dem Server (FileZilla überträgt versteckte Dateien
  nur, wenn unter Server → „Versteckte Dateien anzeigen" aktiviert ist und
  sie beim Upload mitmarkiert wurden). Prüfen: In FileZilla versteckte
  Dateien einblenden, in den `public/`-Ordner auf dem Server wechseln —
  liegen dort `.htaccess` **und** `index.php`? Fehlende `.htaccess` aus dem
  entpackten Deployment-ZIP einzeln nachladen. Zweithäufigste Ursache:
  doppelte Verschachtelung beim Upload (Docroot zeigt auf `…/intranet`, die
  Dateien liegen aber unter `…/intranet/intranet/`) — Docroot exakt auf den
  Ordner stellen, in dem `index.php` liegt.
- **HTTP 500 nach erfolgreichem Seitenaufbau der Domain**: meist fehlende
  oder fehlerhafte `.env` (Datenbankzugangsdaten prüfen) oder Migrationen
  noch nicht gelaufen. Details stehen in `storage/logs/laravel.log` auf dem
  Server — nie `APP_DEBUG=true` in Produktion setzen, um Fehler im Browser
  zu sehen.
- **Login-Seite lädt, aber ohne Styling**: `public/build/` wurde nicht
  (vollständig) hochgeladen — Ordner per FileZilla erneut übertragen.

## Bekannte Einschränkungen vor Produktivstart

Diese Punkte sind technisch vorbereitet, aber vor echtem Produktivbetrieb
zusätzlich zu klären (siehe auch README, Abschnitt „Offene Punkte“):

- Rechtsform, KWG-Erlaubnispflicht, GwG-Prozesse und DSGVO-Freigabe für
  Gesundheitsdaten sind rechtlich nicht geprüft — vor Live-Betrieb mit
  echten Kundendaten zwingend anwaltlich klären.
- Composer-Audit zeigt aktuell mehrere Advisories in `laravel/framework`
  (u. a. CRLF-Injection, Signed-URL-Path-Confusion). Vor Produktivstart
  `composer audit` erneut prüfen und ein Upgrade der Laravel-Version
  einplanen, sofern zwischenzeitlich ein Patch-Release erschienen ist.
- Die Provider-Adapter (KYC/KYB, Bonität, E-Signatur etc.) laufen aktuell
  im Sandbox-/Demo-Modus — für echten Betrieb müssen echte
  API-Zugangsdaten der jeweiligen Anbieter hinterlegt werden.
- Ein Restore aus einem Backup wurde bisher nur lokal getestet, nicht auf
  der tatsächlichen Zielumgebung — vor Produktivstart einmal einen echten
  Restore auf einer Testinstanz durchspielen.

## Restore im Notfall

```bash
# Verschlüsseltes Backup entschlüsseln:
openssl enc -d -aes-256-cbc -pbkdf2 -salt \
  -in aurevia-backup-2026-08-23_023000.zip.enc \
  -out backup.zip -pass pass:<AUREVIA_BACKUP_KEY>

# Entpacken:
unzip backup.zip
# → database/database_....sql   (MariaDB-Dump) oder .sqlite
# → app/documents, app/sepa      (Dokumente/SEPA-Dateien)

# Datenbank zurückspielen (Beispiel MariaDB):
mysql -u <benutzer> -p aurevia_intranet < database/database_....sql

# Dateien zurückspielen:
# Inhalt von app/ nach storage/app/ auf dem Server kopieren.
```
