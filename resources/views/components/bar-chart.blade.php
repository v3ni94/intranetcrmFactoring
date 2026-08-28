@props(['chartId', 'labels', 'values', 'format' => 'eur', 'height' => 220])

{{--
    Einzelserien-Balkendiagramm im Aurevia-Stil: eine Farbe (Navy), schmale
    Balken mit abgerundeten Datenenden, zurueckhaltendes Raster, Tooltip beim
    Hover. Die Zahlen selbst stehen zusaetzlich in Tabellen-/Kartensicht auf
    der Seite (Barrierefreiheit: nie nur die Grafik).
--}}
<div style="position: relative; height: {{ $height }}px;">
    <canvas id="{{ $chartId }}"></canvas>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fmt = @js($format) === 'eur'
        ? (v) => new Intl.NumberFormat(document.documentElement.lang || 'de', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(v)
        : (v) => new Intl.NumberFormat(document.documentElement.lang || 'de').format(v);

    new window.Chart(document.getElementById(@js($chartId)), {
        type: 'bar',
        data: {
            labels: @js($labels),
            datasets: [{
                data: @js($values),
                backgroundColor: '#0E2A47',
                borderRadius: { topLeft: 4, topRight: 4 },
                borderSkipped: 'bottom',
                barPercentage: 0.55,
                categoryPercentage: 0.8,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { grid: { display: false } },
                y: {
                    beginAtZero: true,
                    grid: { color: '#EDEFF2' },
                    ticks: { callback: (v) => fmt(v), maxTicksLimit: 5 },
                },
            },
            plugins: {
                tooltip: {
                    backgroundColor: '#0E2A47',
                    callbacks: { label: (ctx) => fmt(ctx.parsed.y) },
                },
            },
        },
    });
});
</script>
