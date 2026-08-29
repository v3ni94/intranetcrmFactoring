<div style="font-family: ui-sans-serif, system-ui, sans-serif; color: #1C1C1C; max-width: 560px;">
    <div style="background: #0E2A47; color: #fff; padding: 14px 18px; border-radius: 8px 8px 0 0;">
        <strong>AUREVIA FACTORING</strong> · {{ __('KPI-Report vom :date', ['date' => $reportDate]) }}
    </div>
    <table style="width: 100%; border-collapse: collapse; font-size: 14px; border: 1px solid #D9DDE3; border-top: none;">
        @foreach($kpis as $label => $value)
            <tr>
                <td style="padding: 8px 18px; border-bottom: 1px solid #EDEFF2; color: #8A94A0;">{{ $label }}</td>
                <td style="padding: 8px 18px; border-bottom: 1px solid #EDEFF2; text-align: right; font-weight: 600;">{{ $value }}</td>
            </tr>
        @endforeach
    </table>
    <p style="font-size: 11px; color: #8A94A0; padding: 10px 2px;">
        {{ __('Automatisch erzeugter Bericht des Aurevia Intranets · Ein Produkt der Müller Holding AG · Alle Kennzahlen ohne Gewähr. Vertraulich, nicht weiterleiten.') }}
    </p>
</div>
