<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Taegliches verschluesseltes Backup (Abschnitt 22.3). Setzt einen funktionierenden
// Cronjob "php artisan schedule:run" auf dem Webspace voraus, siehe README.
Schedule::command('aurevia:backup')->dailyAt('02:30')->onOneServer();

// Faellige Forderungen taeglich als ueberfaellig markieren (speist Mahnwesen und
// Overdue-KPIs; ohne diesen Job wuerde der Status in Produktion nie gesetzt).
Schedule::command('aurevia:mark-overdue')->dailyAt('03:00')->onOneServer();
