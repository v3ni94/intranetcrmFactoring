<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\CreditLineController;
use App\Http\Controllers\Crm\LeadController;
use App\Http\Controllers\Crm\OpportunityController;
use App\Http\Controllers\Customer\CustomerReceivableController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Dashboards\BeiratDashboardController;
use App\Http\Controllers\Dashboards\GeschaeftsleitungDashboardController;
use App\Http\Controllers\Dashboards\InvestorDashboardController;
use App\Http\Controllers\Dashboards\KundeDashboardController;
use App\Http\Controllers\Dashboards\MitarbeiterDashboardController;
use App\Http\Controllers\Dashboards\RisikoDashboardController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DunningController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\GovernanceController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PayoutBatchController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReceivableController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RiskController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\Treasury\BankAccountController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/dashboard/kunde', KundeDashboardController::class)->name('dashboard.kunde');
    Route::get('/dashboard/mitarbeiter', MitarbeiterDashboardController::class)->name('dashboard.mitarbeiter');
    Route::get('/dashboard/risiko', RisikoDashboardController::class)->name('dashboard.risiko');
    Route::get('/dashboard/geschaeftsleitung', GeschaeftsleitungDashboardController::class)->name('dashboard.geschaeftsleitung');
    Route::get('/dashboard/beirat', BeiratDashboardController::class)->name('dashboard.beirat');
    Route::get('/dashboard/investor', InvestorDashboardController::class)->name('dashboard.investor');

    // Kundenportal: eigene Forderungen
    Route::prefix('meine-forderungen')->name('customer.receivables.')->group(function () {
        Route::get('/', [CustomerReceivableController::class, 'index'])->name('index');
        Route::get('/neu', [CustomerReceivableController::class, 'create'])->name('create');
        Route::post('/vorschau', [CustomerReceivableController::class, 'preview'])->name('preview');
        Route::post('/', [CustomerReceivableController::class, 'store'])->name('store');
        Route::get('/{receivable}', [CustomerReceivableController::class, 'show'])->name('show');
    });

    // Interne Forderungsbearbeitung
    Route::prefix('forderungen')->name('receivables.')->group(function () {
        Route::get('/', [ReceivableController::class, 'index'])->name('index');
        Route::get('/{receivable}', [ReceivableController::class, 'show'])->name('show');
        Route::post('/{receivable}/formale-pruefung', [ReceivableController::class, 'formalCheck'])->name('formal-check');
        Route::post('/{receivable}/risiko-pruefung', [ReceivableController::class, 'riskCheck'])->name('risk-check');
        Route::post('/{receivable}/ablehnen', [ReceivableController::class, 'reject'])->name('reject');
    });

    // Ankauf
    Route::post('/forderungen/{receivable}/ankauf-berechnen', [PurchaseController::class, 'calculate'])->name('purchases.calculate');
    Route::post('/ankauf/{purchase}/zweitfreigabe', [PurchaseController::class, 'approveSecond'])->name('purchases.approve-second');

    // Auszahlungsbatches
    Route::prefix('auszahlungen')->name('payouts.')->group(function () {
        Route::get('/', [PayoutBatchController::class, 'index'])->name('index');
        Route::post('/', [PayoutBatchController::class, 'store'])->name('store');
        Route::post('/{batch}/erstfreigabe', [PayoutBatchController::class, 'approveFirst'])->name('approve-first');
        Route::post('/{batch}/zweitfreigabe', [PayoutBatchController::class, 'approveSecond'])->name('approve-second');
        Route::post('/{batch}/bestaetigen', [PayoutBatchController::class, 'confirm'])->name('confirm');
    });

    // Treasury
    Route::get('/treasury/bankkonten', [BankAccountController::class, 'index'])->name('treasury.bank-accounts.index');

    // Zahlungseingänge & Abstimmung
    Route::prefix('zahlungseingaenge')->name('payments.')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::post('/demo-import', [PaymentController::class, 'importDemo'])->name('import-demo');
        Route::post('/{transaction}/zuordnen', [PaymentController::class, 'match'])->name('match');
    });
    Route::post('/forderungen/{receivable}/abrechnen', [PaymentController::class, 'settle'])->name('payments.settle');

    // Mahnwesen & Streitfälle
    Route::prefix('mahnwesen')->name('dunning.')->group(function () {
        Route::get('/', [DunningController::class, 'index'])->name('index');
        Route::post('/', [DunningController::class, 'store'])->name('store');
        Route::post('/{case}/schliessen', [DunningController::class, 'close'])->name('close');
    });

    // CRM / Vertrieb
    Route::prefix('crm/leads')->name('crm.leads.')->group(function () {
        Route::get('/', [LeadController::class, 'index'])->name('index');
        Route::post('/', [LeadController::class, 'store'])->name('store');
        Route::post('/{lead}/status', [LeadController::class, 'updateStatus'])->name('update-status');
    });
    Route::prefix('crm/opportunities')->name('crm.opportunities.')->group(function () {
        Route::get('/', [OpportunityController::class, 'index'])->name('index');
        Route::post('/', [OpportunityController::class, 'store'])->name('store');
        Route::post('/{opportunity}/stage', [OpportunityController::class, 'updateStage'])->name('update-stage');
    });

    // Kunden, Debitoren, Onboarding
    Route::get('/kunden', [OrganizationController::class, 'index'])->name('organizations.index');
    Route::get('/kunden/{organization}', [OrganizationController::class, 'show'])->name('organizations.show');
    Route::get('/debitoren', [OrganizationController::class, 'debtors'])->name('debtors.index');
    Route::get('/onboarding', [OnboardingController::class, 'index'])->name('onboarding.index');

    // Kreditlinien & Limits
    Route::prefix('kreditlinien')->name('credit-lines.')->group(function () {
        Route::get('/', [CreditLineController::class, 'index'])->name('index');
        Route::post('/', [CreditLineController::class, 'store'])->name('store');
    });

    // Investoren & Fazilitäten
    Route::prefix('fazilitaeten')->name('facilities.')->group(function () {
        Route::get('/', [FacilityController::class, 'index'])->name('index');
        Route::post('/', [FacilityController::class, 'store'])->name('store');
    });
    Route::get('/meine-kapitalbeziehung', InvestorDashboardController::class)->name('investor.facilities.index');

    // Verträge & Dokumente
    Route::prefix('dokumente')->name('documents.')->group(function () {
        Route::get('/', [DocumentController::class, 'index'])->name('index');
        Route::post('/', [DocumentController::class, 'store'])->name('store');
        Route::get('/{document}/download', [DocumentController::class, 'download'])->name('download');
    });

    // Aufgaben
    Route::prefix('aufgaben')->name('tasks.')->group(function () {
        Route::get('/', [TaskController::class, 'index'])->name('index');
        Route::post('/', [TaskController::class, 'store'])->name('store');
        Route::post('/{task}/erledigt', [TaskController::class, 'complete'])->name('complete');
    });

    // Risiko & Compliance / Reporting
    Route::get('/risiko', [RiskController::class, 'index'])->name('risk.index');
    Route::get('/reporting', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reporting/forderungen.csv', [ReportController::class, 'exportReceivables'])->name('reports.receivables');
    Route::get('/reporting/journal.csv', [ReportController::class, 'exportJournal'])->name('reports.journal');

    // Audit & Freigaben (nur Compliance, Geschaeftsleitung, Systemadministration, Superadmin)
    Route::middleware('role:compliance|geschaeftsleitung|systemadministration|superadmin_demo')
        ->get('/audit', [AuditController::class, 'index'])->name('audit.index');

    // Projekt, Annahmen & Beschlüsse (nur Geschaeftsleitung/Superadmin)
    Route::middleware('role:geschaeftsleitung|superadmin_demo')
        ->get('/projekt', [GovernanceController::class, 'index'])->name('governance.index');

    // Demo-Steuerung (nur Superadmin)
    Route::middleware('role:superadmin_demo')->prefix('demo')->name('demo.')->group(function () {
        Route::get('/', [DemoController::class, 'index'])->name('index');
        Route::post('/reset', [DemoController::class, 'reset'])->name('reset');
        Route::post('/delete', [DemoController::class, 'delete'])->name('delete');
    });
});
