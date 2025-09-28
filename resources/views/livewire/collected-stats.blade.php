<section class="collected">
    <div class="block">
        <div class="title">
            <p>Зібрано за весь час</p>
            <h2><span>{{ number_format($homePage->getTotalCollected(), 0, '.', ' ') }}</span> ₴</h2>
        </div>
        <div class="graph">
            <div class="buttons">
                <button type="button" wire:click="setChartPeriod('day')" @if($chartPeriod==='day') class="active" @endif>Д</button>
                <button type="button" wire:click="setChartPeriod('week')" @if($chartPeriod==='week') class="active" @endif>Т</button>
                <button type="button" wire:click="setChartPeriod('month')" @if($chartPeriod==='month') class="active" @endif>М</button>
                <button type="button" wire:click="setChartPeriod('year')" @if($chartPeriod==='year') class="active" @endif>Р</button>
            </div>
            <p>Оновлено: <span>{{ $homePage->updated_at ? $homePage->updated_at->format('d.m.Y H:i') : '' }}</span></p>
            <div class="chart_container" wire:ignore>
                <canvas id="incomeChart"></canvas>
            </div>
        </div>
        <div class="text">
            <p>
                Загальна сума зібраних коштів є відкритою інформацією. Модератори платформи пильно контролюють їх використання
                та унеможливлюють випадки шахрайства.
            </p>
            <p>
                Невикористані кошти із завершених проєктів надходять до фонду платформи.
            </p>
        </div>
        <div class="total">
            <div>
                <p>Оголошених проєктів</p>
                <h2>{{ $homePage->declared_projects }}</h2>
            </div>
            <div>
                <p>Проєктів в роботі</p>
                <h2>{{ $homePage->active_projects }}</h2>
            </div>
            <div>
                <p>Завершених проєктів</p>
                <h2>{{ $homePage->completed_projects }}</h2>
            </div>
            <div>
                <p>Проданих проєктів</p>
                <h2>{{ $homePage->sold_projects }}</h2>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let chart;
        function renderChart(data) {
            const ctx = document.getElementById('incomeChart').getContext('2d');
            if (chart) chart.destroy();
            chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map((_, i) => i + 1),
                    datasets: [{
                        label: 'Сума',
                        data: data,
                        borderColor: '#36a2eb',
                        backgroundColor: 'rgba(54,162,235,0.1)',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } }
                }
            });
        }

        document.addEventListener('livewire:load', function () {
            renderChart(@json($chartData));
            Livewire.on('update-chart', function(data) {
                renderChart(data);
            });
        });
    </script>
</section>
