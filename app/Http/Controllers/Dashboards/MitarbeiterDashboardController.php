<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\BankTransaction;
use App\Models\CreditLine;
use App\Models\DunningCase;
use App\Models\Purchase;
use App\Models\Receivable;
use App\Models\Task;
use Illuminate\Http\Request;

class MitarbeiterDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $myTasks = Task::where('assignee_id', $user->id)
            ->where('status', '!=', 'erledigt')
            ->orderBy('due_date')
            ->limit(10)->get();

        $newSubmissions = Receivable::where('status', 'eingereicht')->count();
        $inReview = Receivable::whereIn('status', ['formale_pruefung', 'risiko_limitpruefung', 'rueckfrage'])->count();
        $payoutsToApprove = Purchase::where('status', 'berechnet')->count();
        $unmatchedPayments = BankTransaction::where('status', 'offen')->count();
        $overdue = Receivable::where('status', 'ueberfaellig')->count();
        $disputes = DunningCase::whereIn('status', ['offen', 'in_klaerung'])->count();
        $watchlist = CreditLine::whereColumn('used_amount', '>=', 'limit_amount')->count();

        return view('dashboards.mitarbeiter', compact(
            'myTasks', 'newSubmissions', 'inReview', 'payoutsToApprove', 'unmatchedPayments', 'overdue', 'disputes', 'watchlist'
        ));
    }
}
