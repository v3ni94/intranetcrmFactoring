<?php

namespace App\Support;

/**
 * Internes Rating fuer Kunden- und Investor-Organisationen (v3.00).
 *
 * Skala angelehnt an marktuebliche Bonitaetsstufen (AAA bis C). Jede Stufe hat
 * einen Punktekorridor (0-100, hoeher = besser) und einen Gebuehrenaufschlag in
 * Prozentpunkten, der beim Ankauf auf die vertragliche Factoringgebuehr
 * aufgeschlagen wird. Kein externes Rating und keine Bonitaetsauskunft —
 * rein internes Steuerungsinstrument; externe Quellen (Crefo/SCHUFA) koennen
 * ueber die vorbereiteten Adapter angebunden werden.
 */
class RatingCatalog
{
    /**
     * Stufe => [Label, Mindestpunkte, Gebuehrenaufschlag in Prozentpunkten]
     */
    public const GRADES = [
        'AAA' => ['label' => 'Ausgezeichnete Bonität', 'min_points' => 90, 'fee_surcharge_percent' => 0.0],
        'AA' => ['label' => 'Sehr gute Bonität', 'min_points' => 80, 'fee_surcharge_percent' => 0.1],
        'A' => ['label' => 'Gute Bonität', 'min_points' => 70, 'fee_surcharge_percent' => 0.2],
        'BBB' => ['label' => 'Befriedigende Bonität', 'min_points' => 60, 'fee_surcharge_percent' => 0.4],
        'BB' => ['label' => 'Ausreichende Bonität', 'min_points' => 50, 'fee_surcharge_percent' => 0.7],
        'B' => ['label' => 'Schwache Bonität', 'min_points' => 40, 'fee_surcharge_percent' => 1.0],
        'CCC' => ['label' => 'Sehr schwache Bonität', 'min_points' => 25, 'fee_surcharge_percent' => 1.5],
        'C' => ['label' => 'Ausfallgefährdet', 'min_points' => 0, 'fee_surcharge_percent' => 2.5],
    ];

    /** Branchensegmente innerhalb Medizin/Heilberufe. */
    public const SEGMENTS = [
        'arzt' => 'Arztpraxis',
        'zahnarzt' => 'Zahnarztpraxis',
        'apotheke' => 'Apotheke',
        'dentallabor' => 'Dentallabor',
        'tierarzt' => 'Tierarztpraxis',
        'heilberufe' => 'Sonstige Heilberufe (Physio, Logopädie, …)',
        'pflege' => 'Pflegedienst / Pflegeeinrichtung',
        'mvz_klinik' => 'MVZ / Klinik',
        'sonstige' => 'Sonstige Medizin',
    ];

    public static function grades(): array
    {
        return array_keys(self::GRADES);
    }

    /** Stufe aus Punktwert ableiten (0-100). */
    public static function gradeForPoints(int $points): string
    {
        foreach (self::GRADES as $grade => $def) {
            if ($points >= $def['min_points']) {
                return $grade;
            }
        }

        return 'C';
    }

    /** Gebuehrenaufschlag in Prozentpunkten fuer eine Stufe (0.0 wenn ohne Rating). */
    public static function feeSurchargePercent(?string $grade): float
    {
        return (float) (self::GRADES[$grade]['fee_surcharge_percent'] ?? 0.0);
    }

    public static function label(?string $grade): string
    {
        return $grade ? ($grade.' – '.(self::GRADES[$grade]['label'] ?? '')) : 'ohne Rating';
    }
}
