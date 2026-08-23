<?php

namespace App\Services;

use App\Models\CreditLine;
use App\Models\DunningCase;
use App\Models\Facility;
use App\Models\FacilityEvent;
use App\Models\Payout;
use App\Models\Purchase;
use App\Models\Receivable;
use Illuminate\Support\Carbon;

/**
 * Zentrale, nachvollziehbare KPI-Berechnungen gemaess Abschnitt 15.5 des Masterprompts.
 * Jede Methode liefert einen Wert, der direkt aus den Bewegungsdaten reproduzierbar ist.
 */
class KpiService
{
    public function customerAvailableToday(int $organizationId): float
    {
        $line = CreditLine::where('organization_id', $organizationId)
            ->where('line_type', 'auszahlung')
            ->where('status', 'aktiv')
            ->first();

        if (! $line) {
            return 0.0;
        }

        return max(0.0, (float) $line->limit_amount - (float) $line->used_amount);
    }

    public function customerPayoutSum(int $organizationId, ?Carbon $from = null, ?Carbon $to = null): float
    {
        $query = Payout::whereHas('purchase.receivable', fn ($q) => $q->where('organization_id', $organizationId))
            ->where('status', 'bestaetigt');

        if ($from) {
            $query->where('confirmed_at', '>=', $from);
        }
        if ($to) {
            $query->where('confirmed_at', '<=', $to);
        }

        return (float) $query->sum('amount');
    }

    public function customerReceivablesInReview(int $organizationId): array
    {
        $q = Receivable::where('organization_id', $organizationId)
            ->whereIn('status', ['eingereicht', 'formale_pruefung', 'risiko_limitpruefung', 'rueckfrage']);

        return ['count' => $q->count(), 'amount' => (float) $q->sum('invoice_amount')];
    }

    public function customerActionRequired(int $organizationId): int
    {
        return Receivable::where('organization_id', $organizationId)
            ->whereIn('status', ['rueckfrage', 'abgelehnt'])
            ->count();
    }

    public function customerCosts(int $organizationId): array
    {
        $purchases = Purchase::whereHas('receivable', fn ($q) => $q->where('organization_id', $organizationId));

        return [
            'fees' => (float) $purchases->clone()->sum('factoring_fee_amount'),
            'interest' => (float) $purchases->clone()->sum('expected_interest_amount'),
        ];
    }

    public function creditLineUtilizationPercent(CreditLine $line): float
    {
        if ((float) $line->limit_amount <= 0) {
            return 0.0;
        }

        return round(((float) $line->used_amount / (float) $line->limit_amount) * 100, 2);
    }

    public function facilityUtilizationPercent(Facility $facility): float
    {
        if ((float) $facility->commitment_amount <= 0) {
            return 0.0;
        }

        return round(((float) $facility->drawn_amount / (float) $facility->commitment_amount) * 100, 2);
    }

    public function freeLiquidity(float $bankBalances, float $securedLines, float $reservedPayouts, float $minLiquidity): float
    {
        return $bankBalances + $securedLines - $reservedPayouts - $minLiquidity;
    }

    public function grossRevenue(?Carbon $from = null, ?Carbon $to = null): float
    {
        $q = Purchase::query();
        if ($from) {
            $q->where('purchased_at', '>=', $from);
        }
        if ($to) {
            $q->where('purchased_at', '<=', $to);
        }

        return (float) $q->sum('factoring_fee_amount') + (float) $q->clone()->sum('expected_interest_amount');
    }

    public function refinancingCost(): float
    {
        return (float) FacilityEvent::where('event_type', 'zinszahlung')->sum('amount');
    }

    public function realizedLosses(): float
    {
        return (float) DunningCase::where('case_type', 'ausfall')->sum('open_amount');
    }

    public function contributionMargin(): float
    {
        return $this->grossRevenue() - $this->refinancingCost() - $this->realizedLosses();
    }

    public function dilutionRatePercent(): float
    {
        $nominal = (float) Purchase::sum('nominal_amount');
        if ($nominal <= 0) {
            return 0.0;
        }

        return round(((float) Purchase::sum('deductions_amount') / $nominal) * 100, 2);
    }

    public function overdueRatioPercent(): float
    {
        $openStatuses = ['angekauft', 'zur_auszahlung', 'zahlung_angewiesen', 'ausgezahlt', 'teilbezahlt', 'ueberfaellig'];
        $totalOpen = (float) Receivable::whereIn('status', $openStatuses)->sum('invoice_amount');
        if ($totalOpen <= 0) {
            return 0.0;
        }
        $overdue = (float) Receivable::where('status', 'ueberfaellig')->sum('invoice_amount');

        return round(($overdue / $totalOpen) * 100, 2);
    }

    public function top10ConcentrationPercent(): float
    {
        $total = (float) Receivable::sum('invoice_amount');
        if ($total <= 0) {
            return 0.0;
        }

        $top10 = Receivable::selectRaw('debtor_organization_id, SUM(invoice_amount) as total')
            ->whereNotNull('debtor_organization_id')
            ->groupBy('debtor_organization_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->sum('total');

        return round(($top10 / $total) * 100, 2);
    }

    public function averageDso(): float
    {
        $paid = Receivable::whereIn('status', ['bezahlt', 'abgerechnet'])
            ->whereHas('payments')
            ->with('payments')
            ->get();

        if ($paid->isEmpty()) {
            return 0.0;
        }

        $days = $paid->map(function (Receivable $r) {
            $lastPayment = $r->payments->max('matched_at');
            if (! $lastPayment) {
                return null;
            }

            return Carbon::parse($r->invoice_date)->diffInDays(Carbon::parse($lastPayment));
        })->filter();

        return $days->isEmpty() ? 0.0 : round($days->avg(), 1);
    }

    public function ageingBuckets(): array
    {
        $openStatuses = ['angekauft', 'zur_auszahlung', 'zahlung_angewiesen', 'ausgezahlt', 'teilbezahlt', 'ueberfaellig'];
        $receivables = Receivable::whereIn('status', $openStatuses)->get(['due_date', 'invoice_amount']);

        $buckets = ['0-30' => 0.0, '31-60' => 0.0, '61-90' => 0.0, '>90' => 0.0];

        foreach ($receivables as $r) {
            $daysOverdue = max(0, Carbon::parse($r->due_date)->diffInDays(now(), false));
            $amount = (float) $r->invoice_amount;

            if ($daysOverdue <= 30) {
                $buckets['0-30'] += $amount;
            } elseif ($daysOverdue <= 60) {
                $buckets['31-60'] += $amount;
            } elseif ($daysOverdue <= 90) {
                $buckets['61-90'] += $amount;
            } else {
                $buckets['>90'] += $amount;
            }
        }

        return $buckets;
    }
}
