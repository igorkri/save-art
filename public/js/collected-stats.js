console.log('collected-stats.js loaded');

function initIncomeChart() {
    if (!window.Livewire) {
        setTimeout(initIncomeChart, 50);
        return;
    }
    console.log('loaded');
    const canvas = document.getElementById('incomeChart');
    if (!canvas) {
        console.warn('incomeChart canvas not found!');
        return;
    }
    const ctx = canvas.getContext('2d');
    const initial = JSON.parse(canvas.dataset.initial);

    console.log(initial);

    function createChart(configData) {
        if (window.incomeChart instanceof Chart) {
            window.incomeChart.destroy();
        }

        window.incomeChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: configData.labels,
                datasets: [{
                    label: 'Доход, грн',
                    data: configData.data,
                    backgroundColor: '#272727',
                    borderRadius: 8,
                    hoverBackgroundColor: "#FECC39",
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: "#FECC39",
                        titleFont: { size: 12, family: "Wix Madefor Display", weight: 'bold' },
                        bodyFont: { size: 12, family: "Wix Madefor Display" },
                        titleColor: "#272727",
                        bodyColor: "#272727",
                        displayColors: false,
                        callbacks: {
                            title: () => "",
                            label: (ctx) => `${ctx.raw} ₴`
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: '#fff', font: { size: 12, family: "Wix Madefor Display" }},
                        grid: { color: '#343434' }
                    },
                    y: {
                        ticks: { color: '#fff', font: { size: 12, family: "Wix Madefor Display" }},
                        grid: { color: '#343434' }
                    }
                }
            }
        });
    }

    createChart(initial);

    Livewire.on('income-chart:update', (payload) => {
        const data = Array.isArray(payload) ? payload[0] : payload;
        console.log('🔥 Отримано дані від Livewire:', data);
        if (!data || !data.data) return;
        // Пересоздаём график полностью, чтобы Chart.js корректно обработал изменение количества точек
        createChart(data);
        const updatedEl = document.getElementById('income-updated');
        const now = new Date();
        updatedEl.textContent = now.toLocaleDateString('uk-UA') + ' ' + now.toLocaleTimeString('uk-UA', {
            hour: '2-digit',
            minute: '2-digit'
        });
    });
}

document.addEventListener('DOMContentLoaded', initIncomeChart);
