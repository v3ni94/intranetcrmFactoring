<?php

return [
    // Anwendungsversion, wird im Footer angezeigt. Schema: MAJOR.MINOR —
    // die erste Zahl steigt bei groesseren Umbauten (z.B. v1.22 -> v2.0),
    // die zweite bei normalen Erweiterungen und Fixes.
    'version' => '2.0',

    // Steuert den DEMO-Banner und die Demo-Steuerung; produktiv auf false setzen.
    'demo_mode' => env('AUREVIA_DEMO_MODE', true),

    // Schluessel fuer die AES-256-Verschluesselung der taeglichen Backups
    // (php artisan aurevia:backup). Ohne gesetzten Schluessel bleibt das Backup
    // unverschluesselt — fuer Produktivbetrieb zwingend setzen und sicher verwahren.
    'backup_encryption_key' => env('AUREVIA_BACKUP_KEY'),
];
