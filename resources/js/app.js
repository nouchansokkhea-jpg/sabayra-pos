

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
if (!window.Chart) {
    window.Chart = Chart;
}

Alpine.start();
