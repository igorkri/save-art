<div>
    <section class="collected">
        <div class="block">
            <div class="title">
                <p>Зібрано за весь час</p>
                <h2><span>2 325 250</span> ₴</h2>
            </div>

            <div class="graph">
                <div class="buttons">
                    <button wire:click="setChartPeriod('day')"  class="{{ $chartPeriod === 'day' ? 'active' : '' }}">Д</button>
                    <button wire:click="setChartPeriod('week')" class="{{ $chartPeriod === 'week' ? 'active' : '' }}">Т</button>
                    <button wire:click="setChartPeriod('month')" class="{{ $chartPeriod === 'month' ? 'active' : '' }}">М</button>
                    <button wire:click="setChartPeriod('year')"  class="{{ $chartPeriod === 'year' ? 'active' : '' }}">Р</button>
                </div>

                <p>Оновлено: <span id="income-updated">12.12.2024 13:00</span></p>

                <div class="chart_container">
                    <canvas id="incomeChart" width="900" height="350" data-initial='@json($chartData)'></canvas>
                </div>
                <style>
                .chart_container {
                    width: 100%;
                    min-width: 600px;
                    max-width: 100%;
                    height: 350px;
                    position: relative;
                }
                #incomeChart {
                    width: 100% !important;
                    height: 100% !important;
                    display: block;
                }
                </style>
            </div>

            <div class="text">
                <p>Загальна сума зібраних коштів є відкритою інформацією...</p>
                <p>Невикористані кошти із завершених проєктів надходять до фонду платформи.</p>
            </div>

            <div class="total">
                <div>
                    <p>Оголошених проєктів</p>
                    <h2>624</h2>
                </div>
                <div>
                    <p>Проєктів в роботі</p>
                    <h2>387</h2>
                </div>
                <div>
                    <p>Завершених проєктів</p>
                    <h2>1 126</h2>
                </div>
                <div>
                    <p>Проданих проєктів</p>
                    <h2>107</h2>
                </div>
            </div>
        </div>
    </section>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="{{ asset('js/collected-stats.js') }}" defer></script>
    @endpush
</div>
