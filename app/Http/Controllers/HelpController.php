<?php

namespace App\Http\Controllers;

use App\Support\Changelog;

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
}
