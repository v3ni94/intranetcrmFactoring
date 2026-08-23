<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Abschnitt 22.3: taegliches verschluesseltes Backup von Datenbank und storage/app.
 */
class BackupCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        File::delete(File::glob(storage_path('app/backups/aurevia-backup-*')));
        parent::tearDown();
    }

    public function test_backup_creates_an_archive(): void
    {
        config(['aurevia.backup_encryption_key' => null]);

        $this->artisan('aurevia:backup')->assertExitCode(0);

        $files = File::glob(storage_path('app/backups/aurevia-backup-*.zip'));
        $this->assertNotEmpty($files, 'Es sollte mindestens ein Backup-Archiv erzeugt worden sein.');
    }

    public function test_backup_is_encrypted_when_key_is_configured(): void
    {
        config(['aurevia.backup_encryption_key' => 'test-encryption-key']);

        $this->artisan('aurevia:backup')->assertExitCode(0);

        $encrypted = File::glob(storage_path('app/backups/aurevia-backup-*.zip.enc'));
        $this->assertNotEmpty($encrypted, 'Bei gesetztem Schlüssel sollte eine verschlüsselte Datei entstehen.');
    }

    public function test_old_backups_are_pruned(): void
    {
        config(['aurevia.backup_encryption_key' => null]);

        $backupDir = storage_path('app/backups');
        File::ensureDirectoryExists($backupDir);
        $oldFile = "{$backupDir}/aurevia-backup-2000-01-01_000000.zip";
        File::put($oldFile, 'dummy');
        touch($oldFile, now()->subDays(30)->getTimestamp());

        $this->artisan('aurevia:backup', ['--keep-days' => 14])->assertExitCode(0);

        $this->assertFileDoesNotExist($oldFile);
    }
}
