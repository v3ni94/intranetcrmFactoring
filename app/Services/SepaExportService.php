<?php

namespace App\Services;

use App\Models\PayoutBatch;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Erzeugt eine schema-nahe pain.001-Demo-Datei. Kein produktiver Bankversand:
 * die Datei bleibt im internen Storage und wird nie automatisch uebertragen.
 */
class SepaExportService
{
    public function exportPain001(PayoutBatch $batch): string
    {
        $batch->load('payouts.organization', 'bankAccount');
        $msgId = 'AUREVIA-DEMO-'.$batch->batch_number;
        $createdAt = now()->toIso8601String();

        $transactions = $batch->payouts->map(function ($payout) {
            return <<<XML
                <CdtTrfTxInf>
                    <PmtId><EndToEndId>{$payout->idempotency_key}</EndToEndId></PmtId>
                    <Amt><InstdAmt Ccy="EUR">{$payout->amount}</InstdAmt></Amt>
                    <Cdtr><Nm>{$payout->organization->name}</Nm></Cdtr>
                    <CdtrAcct><Id><IBAN>DEMO-IBAN-MASKIERT</IBAN></Id></CdtrAcct>
                    <RmtInf><Ustrd>Aurevia Factoring Auszahlung {$payout->id} (DEMO, keine echte Zahlung)</Ustrd></RmtInf>
                </CdtTrfTxInf>
                XML;
        })->implode("\n");

        $xml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <!-- DEMO-DATEI: ausschliesslich fiktive Testdaten, keine echte SEPA-Uebertragung -->
            <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.001.09">
              <CstmrCdtTrfInitn>
                <GrpHdr>
                  <MsgId>{$msgId}</MsgId>
                  <CreDtTm>{$createdAt}</CreDtTm>
                  <NbOfTxs>{$batch->item_count}</NbOfTxs>
                  <CtrlSum>{$batch->total_amount}</CtrlSum>
                  <InitgPty><Nm>Aurevia Factoring AG (Demo)</Nm></InitgPty>
                </GrpHdr>
                <PmtInf>
                  <PmtInfId>{$batch->batch_number}</PmtInfId>
                  <DbtrAcct><Id><IBAN>{$batch->bankAccount->iban_masked}</IBAN></Id></DbtrAcct>
                  {$transactions}
                </PmtInf>
              </CstmrCdtTrfInitn>
            </Document>
            XML;

        $filename = 'sepa/pain001_'.$batch->batch_number.'_'.Str::random(6).'.xml';
        Storage::disk('local')->put($filename, $xml);

        return $filename;
    }
}
