<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Abschnitt 14: automatisches Wasserzeichen mit Status, Version und Empfaenger
 * fuer sensible (nicht rein interne) Dokumente.
 */
class DocumentWatermarkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(DemoUserSeeder::class);
        Storage::fake('local');
    }

    private function makeMinimalPdf(): string
    {
        $tcpdf = new \TCPDF;
        $tcpdf->AddPage();
        $tcpdf->SetFont('helvetica', '', 12);
        $tcpdf->Cell(0, 10, 'Aurevia Testdokument', 0, 1);

        $path = 'documents/test-'.uniqid().'.pdf';
        Storage::disk('local')->put($path, $tcpdf->Output('', 'S'));

        return $path;
    }

    public function test_externally_released_pdf_is_watermarked_on_download(): void
    {
        $tenant = Tenant::where('slug', 'aurevia-demo')->firstOrFail();
        TenantContext::set($tenant->id);

        $path = $this->makeMinimalPdf();
        $originalSize = Storage::disk('local')->size($path);

        $document = Document::create([
            'tenant_id' => $tenant->id,
            'title' => 'Board Pack Q3',
            'category' => 'board_pack',
            'visibility' => 'extern_freigegeben',
            'storage_path' => $path,
            'version' => 2,
        ]);

        $investor = User::where('email', 'demo.investor@aurevia-factoring.de')->firstOrFail();

        $response = $this->actingAs($investor)->get(route('documents.download', $document));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertNotSame($originalSize, (int) $response->headers->get('content-length'), 'Wasserzeichen-Kopie sollte sich von der Originaldatei unterscheiden.');
    }

    public function test_internal_working_document_is_not_watermarked(): void
    {
        $tenant = Tenant::where('slug', 'aurevia-demo')->firstOrFail();
        TenantContext::set($tenant->id);

        $path = $this->makeMinimalPdf();
        $originalSize = Storage::disk('local')->size($path);

        $document = Document::create([
            'tenant_id' => $tenant->id,
            'title' => 'Internes Arbeitsdokument',
            'category' => 'sonstiges',
            'visibility' => 'intern',
            'storage_path' => $path,
        ]);

        $operations = User::where('email', 'demo.operations@aurevia-factoring.de')->firstOrFail();

        $response = $this->actingAs($operations)->get(route('documents.download', $document));

        $response->assertOk();
        $this->assertSame($originalSize, (int) $response->headers->get('content-length'));
    }
}
