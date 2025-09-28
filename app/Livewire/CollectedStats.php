<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Log;
use Livewire\Component;

class CollectedStats extends Component
{
    public $homePage;
    public $chartPeriod = 'month';
    public $chartData = [];
    public $isLoading = true;

    public function mount()
    {
        $this->homePage = \App\Models\HomePage::getActive();
        $this->updateChartData();
    }

    public function setChartPeriod($period)
    {
        $this->isLoading = true;
        $this->chartPeriod = $period;
        $this->updateChartData();
    }

    public function updateChartData()
    {
        $this->chartData = match ($this->chartPeriod) {
            'day' => [
                'labels' => ['00:00', '06:00', '12:00', '18:00', '24:00'],
                'data' => [501, 1200, 8000, 3200, 5000],
            ],
            'week' => [
                'labels' => ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Нд'],
                'data' => [7000, 8200, 5400, 11000, 9200, 15000, 12300],
            ],
            'month' => [
                'labels' => range(1, 30),
                'data' => array_map(fn() => rand(3000, 23000), range(1, 30)),
            ],
            'year' => [
                'labels' => ['Січ', 'Лют', 'Бер', 'Кві', 'Тра', 'Чер', 'Лип', 'Сер', 'Вер', 'Жов', 'Лис', 'Гру'],
                'data' => array_map(fn() => rand(10000, 60000), range(1, 12)),
            ],
            default => ['labels' => [], 'data' => []],
        };

        $this->isLoading = false;

        // ✅ Надсилаємо подію у браузер (аналог dispatchBrowserEvent)
        $this->dispatch('income-chart:update', [
            'labels' => $this->chartData['labels'],
            'data' => $this->chartData['data'],
            'period' => $this->chartPeriod,
        ]);

    }

    public function render()
    {
        return view('livewire.collected-stats', [
            'chartData' => $this->chartData,
            'chartPeriod' => $this->chartPeriod,
        ]);
    }
}
