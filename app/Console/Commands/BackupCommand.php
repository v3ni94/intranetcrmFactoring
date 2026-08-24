<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

/**
 * Taegliches verschluesseltes Backup von Datenbank und storage/app (Abschnitt 22.3).
 * Fuer PHP-Webspace ohne Docker/Root-Zugriff ausgelegt: nutzt mysqldump (MariaDB)
 * bzw. eine Dateikopie (SQLite) und OpenSSL fuer die Verschluesselung, keine
 * zusaetzliche Systemsoftware erforderlich ausser den auf jedem Webspace ueblichen
 * Kommandos mysqldump/zip/openssl.
 */
class BackupCommand extends Command
{
    protected $signature = 'aurevia:backup {--keep-days=14 : Anzahl Tage, nach denen alte Backups geloescht werden}';

    protected $description = 'Erstellt ein verschluesseltes Backup von Datenbank und storage/app';

    public function handle(): int
    {
        $backupDir = storage_path('app/backups');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0750, true);
        }

        // uniqid() haengt am Zeitstempel, damit zwei Laeufe innerhalb derselben
        // Sekunde (z.B. in Tests oder bei manuellem Wiederholen) nicht kollidieren.
        $timestamp = now()->format('Y-m-d_His').'_'.substr(uniqid(), -6);
        $workDir = storage_path("app/backups/tmp_{$timestamp}");
        mkdir($workDir, 0750, true);

        $this->info('1/4 Datenbank-Dump erstellen …');
        $dbDumpPath = $this->dumpDatabase($workDir, $timestamp);
        if (! $dbDumpPath) {
            $this->error('Datenbank-Dump fehlgeschlagen. Backup abgebrochen.');

            return self::FAILURE;
        }

        $this->info('2/4 storage/app archivieren …');
        $archivePath = storage_path("app/backups/aurevia-backup-{$timestamp}.zip");
        if (! $this->createZip($archivePath, $dbDumpPath, storage_path('app'))) {
            $this->error('Archiv konnte nicht erstellt werden.');

            return self::FAILURE;
        }

        $encryptionKey = config('aurevia.backup_encryption_key');
        $finalPath = $archivePath;

        if ($encryptionKey) {
            $this->info('3/4 Archiv verschlüsseln (AES-256) …');
            $encryptedPath = $archivePath.'.enc';
            $process = new Process([
                'openssl', 'enc', '-aes-256-cbc', '-pbkdf2', '-salt',
                '-in', $archivePath, '-out', $encryptedPath, '-pass', 'pass:'.$encryptionKey,
            ]);
            $process->run();

            if ($process->isSuccessful()) {
                unlink($archivePath);
                $finalPath = $encryptedPath;
            } else {
                $this->warn('Verschlüsselung fehlgeschlagen, Backup bleibt unverschlüsselt: '.$process->getErrorOutput());
            }
        } else {
            $this->warn('AUREVIA_BACKUP_KEY nicht gesetzt — Backup bleibt unverschlüsselt. Für Produktivbetrieb zwingend setzen.');
        }

        $this->deleteDirectory($workDir);

        $this->info('4/4 Alte Backups aufräumen …');
        $this->pruneOldBackups($backupDir, (int) $this->option('keep-days'));

        $this->info('Backup abgeschlossen: '.basename($finalPath).' ('.$this->humanSize(filesize($finalPath)).')');

        return self::SUCCESS;
    }

    private function dumpDatabase(string $workDir, string $timestamp): ?string
    {
        $connection = config('database.default');
        $dumpPath = "{$workDir}/database_{$timestamp}.sql";

        if ($connection === 'sqlite') {
            $sqlitePath = config('database.connections.sqlite.database');

            // Eine echte Datenbankdatei (lokale Entwicklung/Demo) wird 1:1 kopiert.
            if ($sqlitePath && $sqlitePath !== ':memory:' && file_exists($sqlitePath)) {
                copy($sqlitePath, "{$workDir}/database_{$timestamp}.sqlite");

                return "{$workDir}/database_{$timestamp}.sqlite";
            }

            // In-Memory-SQLite (z. B. in der Testumgebung) laesst sich nicht kopieren,
            // daher wird stattdessen ein textueller SQL-Dump ueber PDO erzeugt.
            return $this->dumpSqliteViaPdo($dumpPath);
        }

        $config = config("database.connections.{$connection}");
        // Passwort ueber MYSQL_PWD statt --password=, damit es auf Shared Hosting
        // nicht in der Prozessliste (ps) fuer andere Nutzer sichtbar ist.
        $process = new Process([
            'mysqldump',
            '--host='.$config['host'],
            '--port='.($config['port'] ?? 3306),
            '--user='.$config['username'],
            '--single-transaction',
            '--routines',
            $config['database'],
        ], null, ['MYSQL_PWD' => $config['password']]);
        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error($process->getErrorOutput());

            return null;
        }

        file_put_contents($dumpPath, $process->getOutput());

        return $dumpPath;
    }

    private function dumpSqliteViaPdo(string $dumpPath): ?string
    {
        $pdo = DB::connection()->getPdo();

        $tables = $pdo->query(
            "SELECT name, sql FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"
        )->fetchAll(\PDO::FETCH_ASSOC);

        $sql = '';

        foreach ($tables as $table) {
            $sql .= $table['sql'].";\n";

            $rows = $pdo->query('SELECT * FROM '.$table['name'])->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $columns = implode(', ', array_map(fn ($c) => "\"{$c}\"", array_keys($row)));
                $values = implode(', ', array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), array_values($row)));
                $sql .= "INSERT INTO \"{$table['name']}\" ({$columns}) VALUES ({$values});\n";
            }
        }

        if (file_put_contents($dumpPath, $sql) === false) {
            $this->error('SQL-Dump konnte nicht geschrieben werden.');

            return null;
        }

        return $dumpPath;
    }

    private function createZip(string $archivePath, string $dbDumpPath, string $storageAppPath): bool
    {
        $zip = new \ZipArchive;
        if ($zip->open($archivePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        $zip->addFile($dbDumpPath, 'database/'.basename($dbDumpPath));

        // storage/app ohne den eigenen backups-Ordner und ohne temporaere Dateien sichern.
        $documentsPath = "{$storageAppPath}/documents";
        $sepaPath = "{$storageAppPath}/sepa";

        foreach ([$documentsPath, $sepaPath] as $dir) {
            if (! is_dir($dir)) {
                continue;
            }
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
            foreach ($files as $file) {
                if ($file->isFile()) {
                    $zip->addFile($file->getPathname(), 'app/'.substr($file->getPathname(), strlen($storageAppPath) + 1));
                }
            }
        }

        $zip->close();

        return true;
    }

    private function pruneOldBackups(string $backupDir, int $keepDays): void
    {
        $threshold = now()->subDays($keepDays)->getTimestamp();

        foreach (glob("{$backupDir}/aurevia-backup-*.zip*") ?: [] as $file) {
            if (filemtime($file) < $threshold) {
                unlink($file);
                $this->line('Gelöscht (älter als '.$keepDays.' Tage): '.basename($file));
            }
        }
    }

    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (glob("{$dir}/*") as $file) {
            is_dir($file) ? $this->deleteDirectory($file) : unlink($file);
        }
        rmdir($dir);
    }

    private function humanSize(int $bytes): string
    {
        return $bytes >= 1048576 ? round($bytes / 1048576, 1).' MB' : round($bytes / 1024, 1).' KB';
    }
}
