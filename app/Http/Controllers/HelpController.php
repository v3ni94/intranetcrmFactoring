<?php

namespace App\Http\Controllers;

use App\Support\Changelog;
use App\Support\RoleCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Hilfe-Bereich (v3.00): FAQ mit Prozessanleitungen und Changelog-Chronologie.
 */
class HelpController extends Controller
{
    public function faq()
    {
        return view('help.faq');
    }

    public function changelog()
    {
        return view('help.changelog', ['entries' => Changelog::ENTRIES]);
    }

    /**
     * Onboarding-Leitfaden (v3.02): gefuehrter Durchklick durch alle Module.
     */
    public function onboarding()
    {
        return view('help.onboarding');
    }

    /**
     * Wissensdatenbank (v3.02): Handbuecher aus docs/ als lesbare Seiten.
     * BaFin- und Datenschutz-Dokumente sind internen Rollen vorbehalten.
     */
    public function knowledge(Request $request, string $doc)
    {
        $docs = [
            'handbuch' => ['file' => 'BENUTZERHANDBUCH.md', 'title' => 'Benutzerhandbuch', 'internal' => false],
            'prozesse' => ['file' => 'PROZESSLEITFADEN.md', 'title' => 'Prozessleitfaden', 'internal' => true],
            'bafin' => ['file' => 'BAFIN_DOKUMENTATION.md', 'title' => 'BaFin-Vorbereitungsdokumentation', 'internal' => true],
            'datenschutz' => ['file' => 'DATENSCHUTZ_KONZEPT.md', 'title' => 'Datenschutzkonzept', 'internal' => true],
        ];

        $meta = $docs[$doc];

        if ($meta['internal']) {
            abort_unless($request->user()->hasAnyRole(RoleCatalog::INTERNAL_ROLES), 403);
        }

        $path = base_path('docs/'.$meta['file']);
        abort_unless(file_exists($path), 404);

        // Markdown serverseitig rendern (CommonMark), HTML wird escaped ausgegeben.
        $html = Str::markdown(file_get_contents($path), [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]);

        return view('help.knowledge', [
            'title' => $meta['title'],
            'html' => $html,
            'docs' => collect($docs)->filter(fn ($d) => ! $d['internal'] || $request->user()->hasAnyRole(RoleCatalog::INTERNAL_ROLES)),
            'current' => $doc,
        ]);
    }
}
