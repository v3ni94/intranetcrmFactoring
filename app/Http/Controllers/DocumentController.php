<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index()
    {
        $documents = Document::with('owner')->latest('id')->paginate(25);

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

    public function download(Document $document)
    {
        abort_if($document->export_locked, 403, 'Sperrvermerk: Export dieses Dokuments ist technisch gesperrt.');
        abort_unless($document->storage_path && Storage::disk('local')->exists($document->storage_path), 404);

        AuditLogger::log('export', Document::class, $document->id, [], []);

        return Storage::disk('local')->download($document->storage_path, $document->title);
    }
}
