<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * "Schon gewusst?"-Zeile im Footer (v3.02): zeigt zum aktuellen Datum einen
 * bekannten Welt-/Aktionstag oder ein bewegliches Fest (Ostern, Aschermittwoch,
 * Advent). Nur allgemein bekannte Tage, nichts Erfundenes; an Tagen ohne
 * Eintrag erscheint ein neutraler Kalenderfakt (n-ter Tag des Jahres).
 */
class DayFact
{
    /** Feste Tage: 'MM-TT' => [de, en] */
    private const FIXED = [
        '01-01' => ['Neujahr', 'New Year\'s Day'],
        '01-21' => ['Weltknuddeltag', 'International Hug Day'],
        '01-28' => ['der Europäische Datenschutztag', 'European Data Protection Day'],
        '02-02' => ['Welttag der Feuchtgebiete (und Murmeltiertag)', 'World Wetlands Day (and Groundhog Day)'],
        '02-11' => ['der Internationale Tag der Frauen und Mädchen in der Wissenschaft', 'International Day of Women and Girls in Science'],
        '02-14' => ['Valentinstag', 'Valentine\'s Day'],
        '03-08' => ['der Internationale Frauentag', 'International Women\'s Day'],
        '03-15' => ['der Weltverbrauchertag', 'World Consumer Rights Day'],
        '03-20' => ['der Internationale Tag des Glücks', 'International Day of Happiness'],
        '03-22' => ['der Weltwassertag', 'World Water Day'],
        '03-31' => ['der World Backup Day', 'World Backup Day'],
        '04-07' => ['der Weltgesundheitstag', 'World Health Day'],
        '04-22' => ['der Tag der Erde', 'Earth Day'],
        '04-23' => ['der Welttag des Buches', 'World Book Day'],
        '04-28' => ['der Welttag für Sicherheit und Gesundheit am Arbeitsplatz', 'World Day for Safety and Health at Work'],
        '05-01' => ['der Tag der Arbeit', 'Labour Day'],
        '05-04' => ['der Star-Wars-Tag („May the fourth")', 'Star Wars Day ("May the fourth")'],
        '05-12' => ['der Internationale Tag der Pflege', 'International Nurses Day'],
        '05-17' => ['der Welttag der Telekommunikation und Informationsgesellschaft', 'World Telecommunication and Information Society Day'],
        '05-25' => ['der Handtuchtag (Towel Day)', 'Towel Day'],
        '06-01' => ['der Internationale Kindertag', 'International Children\'s Day'],
        '06-05' => ['der Weltumwelttag', 'World Environment Day'],
        '06-08' => ['der Welttag der Ozeane', 'World Oceans Day'],
        '06-14' => ['der Weltblutspendetag', 'World Blood Donor Day'],
        '06-21' => ['der Weltyogatag (und kalendarischer Sommeranfang)', 'International Yoga Day (and the start of astronomical summer)'],
        '06-27' => ['der Internationale Tag der kleinen und mittleren Unternehmen', 'Micro-, Small and Medium-sized Enterprises Day'],
        '07-20' => ['der Internationale Schachtag', 'International Chess Day'],
        '07-30' => ['der Internationale Tag der Freundschaft', 'International Day of Friendship'],
        '08-08' => ['der Internationale Katzentag', 'International Cat Day'],
        '08-13' => ['der Internationale Linkshändertag', 'International Left-Handers Day'],
        '09-21' => ['der Internationale Tag des Friedens', 'International Day of Peace'],
        '09-22' => ['der Autofreie Tag', 'Car-Free Day'],
        '09-27' => ['der Welttourismustag', 'World Tourism Day'],
        '10-01' => ['der Internationale Kaffeetag', 'International Coffee Day'],
        '10-04' => ['der Welttierschutztag', 'World Animal Day'],
        '10-05' => ['der Weltlehrertag', 'World Teachers\' Day'],
        '10-10' => ['der Welthundetag', 'World Dog Day'],
        '10-31' => ['der Weltspartag (und Halloween)', 'World Savings Day (and Halloween)'],
        '11-11' => ['Martinstag und Karnevalsauftakt um 11:11 Uhr', 'St. Martin\'s Day and the start of carnival season at 11:11'],
        '11-19' => ['der Internationale Männertag', 'International Men\'s Day'],
        '11-21' => ['der Welttag des Fernsehens', 'World Television Day'],
        '12-04' => ['der Internationale Tag der Banken', 'International Day of Banks'],
        '12-05' => ['der Internationale Tag des Ehrenamtes', 'International Volunteer Day'],
        '12-06' => ['Nikolaus', 'St. Nicholas Day'],
        '12-24' => ['Heiligabend', 'Christmas Eve'],
        '12-25' => ['der erste Weihnachtsfeiertag', 'Christmas Day'],
        '12-26' => ['der zweite Weihnachtsfeiertag', 'Boxing Day'],
        '12-31' => ['Silvester', 'New Year\'s Eve'],
    ];

    /**
     * Liefert die fertige Footer-Zeile fuer das heutige Datum in der aktiven
     * Sprache, z. B. "Schon gewusst? Heute ist der Weltspartag."
     */
    public static function line(?Carbon $date = null): string
    {
        $date = $date ?? Carbon::today();
        $isEn = app()->getLocale() === 'en';

        $label = self::movable($date, $isEn) ?? self::fixed($date, $isEn);

        if ($label !== null) {
            return $isEn
                ? 'Did you know? Today is '.$label.'.'
                : 'Schon gewusst? Heute ist '.$label.'.';
        }

        // Neutraler, korrekter Kalenderfakt als Fallback
        $dayOfYear = $date->dayOfYear;
        $remaining = $date->copy()->endOfYear()->dayOfYear - $dayOfYear;

        return $isEn
            ? sprintf('Did you know? Today is day %d of the year — %d days left until New Year\'s Eve.', $dayOfYear, $remaining)
            : sprintf('Schon gewusst? Heute ist der %d. Tag des Jahres, noch %d Tage bis Silvester.', $dayOfYear, $remaining);
    }

    private static function fixed(Carbon $date, bool $isEn): ?string
    {
        $entry = self::FIXED[$date->format('m-d')] ?? null;

        return $entry ? $entry[$isEn ? 1 : 0] : null;
    }

    /** Bewegliche Feste rund um Ostern sowie Muttertag und 1. Advent. */
    private static function movable(Carbon $date, bool $isEn): ?string
    {
        $easter = self::easterSunday($date->year);
        $diff = $easter->diffInDays($date, false);

        $easterRelative = [
            -48 => ['Rosenmontag', 'Rose Monday (carnival)'],
            -47 => ['Faschingsdienstag', 'Shrove Tuesday'],
            -46 => ['Aschermittwoch', 'Ash Wednesday'],
            -2 => ['Karfreitag', 'Good Friday'],
            0 => ['Ostersonntag', 'Easter Sunday'],
            1 => ['Ostermontag', 'Easter Monday'],
            39 => ['Christi Himmelfahrt', 'Ascension Day'],
            49 => ['Pfingstsonntag', 'Whit Sunday'],
            50 => ['Pfingstmontag', 'Whit Monday'],
            60 => ['Fronleichnam', 'Corpus Christi'],
        ];

        if (isset($easterRelative[$diff])) {
            return $easterRelative[$diff][$isEn ? 1 : 0];
        }

        // Muttertag: zweiter Sonntag im Mai. Start am 30.04., da next() den
        // Starttag selbst ausschliesst — faellt der 1. Mai auf einen Sonntag,
        // ist er bereits der erste Sonntag im Mai.
        $mothersDay = Carbon::create($date->year, 4, 30)->next(Carbon::SUNDAY)->addWeek();
        if ($date->isSameDay($mothersDay)) {
            return $isEn ? 'Mother\'s Day' : 'Muttertag';
        }

        // 1. Advent: vierter Sonntag vor dem 25.12.
        $firstAdvent = Carbon::create($date->year, 12, 25)->previous(Carbon::SUNDAY)->subWeeks(3);
        if ($date->isSameDay($firstAdvent)) {
            return $isEn ? 'the first Sunday of Advent' : 'der erste Advent';
        }

        return null;
    }

    /** Ostersonntag nach der anonymen Gregorianischen Berechnung (ohne ext-calendar). */
    private static function easterSunday(int $year): Carbon
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::create($year, $month, $day);
    }
}
