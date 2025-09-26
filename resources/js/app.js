import './bootstrap';
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

// Make Chart.js available globally for Livewire components
window.Chart = Chart;

// Start Alpine.js
window.Alpine = Alpine;
Alpine.start();
