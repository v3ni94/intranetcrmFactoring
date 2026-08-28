<?php

return [
    // Anwendungsversion, wird im Footer angezeigt. Schema: MAJOR.MINOR —
    // die erste Zahl steigt bei groesseren Umbauten (z.B. v2.10 -> v3.00),
    // die zweite (zweistellig) bei Erweiterungen und Fixes (v3.00 -> v3.01).
    // Chronologie aller Aenderungen: App\Support\Changelog + Seite /hilfe/changelog.
    'version' => '3.00',

    // Steuert den DEMO-Banner und die Demo-Steuerung; produktiv auf false setzen.
    'demo_mode' => env('AUREVIA_DEMO_MODE', true),

    // Schluessel fuer die AES-256-Verschluesselung der taeglichen Backups
    // (php artisan aurevia:backup). Ohne gesetzten Schluessel bleibt das Backup
    // unverschluesselt — fuer Produktivbetrieb zwingend setzen und sicher verwahren.
    'backup_encryption_key' => env('AUREVIA_BACKUP_KEY'),

    // Klumpenrisiko-Schwelle in EUR: aktive Kreditlinien oberhalb dieses Limits
    // sollen ganz oder teilweise ueber die Warenkreditversicherung abgedeckt
    // werden (v3.00). Linien darueber ohne Versicherungsschutz werden in der
    // Kreditlinien-Uebersicht markiert und in der Monatsmeldung gezaehlt.
    'insurance_threshold' => env('AUREVIA_INSURANCE_THRESHOLD', 30000),

    // Kalkulatorische Monatsmarge fuer die Investor-Modellrechnung (v3.00).
    // Reine Illustration im Investor-Dashboard, keine Zusage, keine Prognose.
    'investor_model_margin_percent' => env('AUREVIA_INVESTOR_MODEL_MARGIN', 3.0),
];
