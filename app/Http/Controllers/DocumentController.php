<?php

namespace App\Http\Controllers;

use App\Models\Document;
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
            'file' => 'nullable|file|max:20480',
        ]);

        $path = null;
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('documents', 'local');
        }

        $document = Document::create([
            'tenant_id' => TenantContext::id(),
            'title' => $data['title'],
            'category' => $data['category'],
            'visibility' => $data['visibility'],
            'storage_path' => $path,
            'owner_id' => $request->user()->id,
        ]);

        AuditLogger::log('create', Document::class, $document->id, [], $document->toArray());

        return back()->with('status', 'Dokument abgelegt: '.$document->title);
    }

    public function download(Request $request, Document $document, WatermarkService $watermark)
    {
        abort_unless(
            Document::visibleTo($request->user())->whereKey($document->id)->exists(),
            403,
            'Dieses Dokument ist für Ihre Rolle nicht freigegeben.'
        );
        abort_if($document->export_locked, 403, 'Sperrvermerk: Export dieses Dokuments ist technisch gesperrt.');
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
