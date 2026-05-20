import {
    Chart,
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    Filler,
    Tooltip,
} from 'chart.js';

Chart.register(
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    Filler,
    Tooltip
);

const canvas = document.getElementById('growthChart');

if (canvas) {
    const labels = JSON.parse(canvas.dataset.labels);
    const values = JSON.parse(canvas.dataset.values);

    const ctx = canvas.getContext('2d');
    const fill = ctx.createLinearGradient(0, 0, 0, canvas.clientHeight);
    fill.addColorStop(0, 'rgba(167, 139, 250, 0.35)');
    fill.addColorStop(1, 'rgba(167, 139, 250, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                data: values,
                borderColor: '#A78BFA',
                borderWidth: 2.2,
                backgroundColor: fill,
                fill: true,
                tension: 0.35,
                pointBackgroundColor: '#0F1014',
                pointBorderColor: '#A78BFA',
                pointBorderWidth: 1.6,
                pointRadius: 3.2,
                pointHoverRadius: 4.5,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0F1014',
                    borderColor: '#23252C',
                    borderWidth: 1,
                    titleColor: '#F5F5F7',
                    bodyColor: '#B4B5BC',
                    padding: 10,
                    displayColors: false,
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        color: '#6B6C74',
                        font: { size: 10 },
                    },
                    border: { display: false },
                },
                y: {
                    grid: { color: '#23252C', drawTicks: false },
                    ticks: { display: false },
                    border: { display: false },
                    beginAtZero: true,
                },
            },
        },
    });
}
