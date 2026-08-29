<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Organization;
use App\Services\WatermarkService;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $documents = Document::visibleTo($request->user())->with('owner')->latest('id')->paginate(25);

        return view('documents.index', compact('documents'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|in:vertrag,onboarding,kyc,rechnung,board_pack,sonstiges',
            'visibility' => 'required|in:intern,vertraulich,externe_freigabe_ausstehend,extern_freigegeben,gesperrt',
            'organization_id' => 'nullable|exists:organizations,id',
            'release_audience' => 'nullable|in:investor,beirat',
            'file' => 'nullable|file|max:20480|mimes:pdf,doc,docx,xls,xlsx,csv,txt,png,jpg,jpeg',
        ]);

        // Externe Freigabe erfordert eine Zielbindung, sonst waere das Dokument fuer
        // saemtliche externen Nutzer sichtbar (Medical Data Firewall, default-deny).
        if ($data['visibility'] === 'extern_freigegeben'
            && empty($data['organization_id']) && empty($data['release_audience'])
            && $data['category'] !== 'board_pack') {
            return back()->withErrors([
                'visibility' => __('Externe Freigabe erfordert eine Zielbindung: Organisation, Zielgruppe (Investor/Beirat) oder Kategorie Board Pack.'),
            ])->withInput();
        }

        $path = null;
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('documents', 'local');
        }

        $document = Document::create([
            'tenant_id' => TenantContext::id(),
            'title' => $data['title'],
            'category' => $data['category'],
            'visibility' => $data['visibility'],
            'related_type' => ! empty($data['organization_id']) ? Organization::class : null,
            'related_id' => $data['organization_id'] ?? null,
            'release_audience' => $data['release_audience'] ?? null,
            'storage_path' => $path,
            'owner_id' => $request->user()->id,
        ]);

        AuditLogger::log('create', Document::class, $document->id, [], $document->toArray());

        return back()->with('status', __('Dokument abgelegt: :title', ['title' => $document->title]));
    }

    public function download(Request $request, Document $document, WatermarkService $watermark)
    {
        abort_unless(
            Document::visibleTo($request->user())->whereKey($document->id)->exists(),
            403,
            __('Dieses Dokument ist für Ihre Rolle nicht freigegeben.')
        );
        abort_if($document->export_locked, 403, __('Sperrvermerk: Export dieses Dokuments ist technisch gesperrt.'));
        abort_unless($document->storage_path && Storage::disk('local')->exists($document->storage_path), 404);

        AuditLogger::log('export', Document::class, $document->id, [], [], 'Download durch '.$request->user()->name);

        $absolutePath = Storage::disk('local')->path($document->storage_path);

        // Sensible Dokumente (alles ausser rein internen Arbeitsdokumenten) erhalten ein
        // Wasserzeichen mit Status, Version und Empfaenger (Abschnitt 14).
        if ($document->visibility !== 'intern' && $watermark->isSupported($absolutePath)) {
            $stampedPath = $watermark->stamp($absolutePath, $document, $request->user()->name);

            return response()->download($stampedPath, $document->title.'.pdf')->deleteFileAfterSend(true);
        }

        return Storage::disk('local')->download($document->storage_path, $document->title);
    }
}
