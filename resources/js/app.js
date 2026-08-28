import './bootstrap';

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

// Aurevia-Grundeinstellungen fuer alle Diagramme: zurueckhaltendes Raster,
// Text in Grautoenen, Identitaet traegt die Marke (Navy), nicht der Text.
Chart.defaults.font.family = getComputedStyle(document.documentElement).getPropertyValue('font-family') || 'ui-sans-serif, system-ui, sans-serif';
Chart.defaults.color = '#8A94A0';
Chart.defaults.borderColor = '#D9DDE3';
Chart.defaults.plugins.legend.display = false;

Alpine.start();
