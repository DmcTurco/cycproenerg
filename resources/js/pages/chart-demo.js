import Chart from 'chart.js/auto';

const sharedTicks = { font: { family: 'Inter', size: 12 }, color: '#6b7280' };
const sharedGrid = { color: '#e5e7eb', drawTicks: false };

function makeChart(id, config) {
    const canvas = document.getElementById(id);
    if (!canvas) return;
    new Chart(canvas.getContext('2d'), config);
}

document.addEventListener('DOMContentLoaded', () => {
    makeChart('chart-bars', {
        type: 'bar',
        data: {
            labels: ['L', 'M', 'X', 'J', 'V', 'S', 'D'],
            datasets: [{
                label: 'Ventas',
                data: [50, 20, 10, 22, 50, 10, 40],
                backgroundColor: '#3b82f6',
                borderRadius: 4,
                maxBarThickness: 24,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: sharedGrid, ticks: { ...sharedTicks, beginAtZero: true } },
                x: { grid: { display: false }, ticks: sharedTicks },
            },
        },
    });

    makeChart('chart-line', {
        type: 'line',
        data: {
            labels: ['Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
            datasets: [{
                label: 'Ventas mensuales',
                data: [50, 40, 300, 320, 500, 350, 200, 230, 500],
                borderColor: '#22c55e',
                backgroundColor: 'rgba(34,197,94,0.1)',
                borderWidth: 3,
                pointRadius: 3,
                tension: 0.35,
                fill: true,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: sharedGrid, ticks: sharedTicks },
                x: { grid: { display: false }, ticks: sharedTicks },
            },
        },
    });

    makeChart('chart-line-tasks', {
        type: 'line',
        data: {
            labels: ['Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
            datasets: [{
                label: 'Tareas completadas',
                data: [50, 40, 300, 220, 500, 250, 400, 230, 500],
                borderColor: '#111827',
                backgroundColor: 'rgba(17,24,39,0.06)',
                borderWidth: 3,
                pointRadius: 3,
                tension: 0.35,
                fill: true,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: sharedGrid, ticks: sharedTicks },
                x: { grid: { display: false }, ticks: sharedTicks },
            },
        },
    });
});
