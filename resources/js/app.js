import './bootstrap';
import { initializeNigeriaMap } from './nigeria-map';
import { Chart, registerables } from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';

// Register Chart.js components
Chart.register(...registerables);
Chart.register(ChartDataLabels);

// Make it available globally
window.initializeNigeriaMap = initializeNigeriaMap;
window.Chart = Chart;
