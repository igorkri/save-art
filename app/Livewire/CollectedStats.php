<?php

namespace App\Livewire;

use Livewire\Component;


class CollectedStats extends Component
{
    public $homePage;
    public $chartPeriod = 'month';
    public $chartData = [];

    public function mount()
    {
        $this->homePage = \App\Models\HomePage::getActive();
        $this->updateChartData();
    }

    public function setChartPeriod($period)
    {
        $this->chartPeriod = $period;
        $this->updateChartData();
    }

    public function updateChartData()
    {
        // Здесь должна быть логика получения данных для графика по $this->chartPeriod
        // Пример заглушки:
        $this->chartData = match($this->chartPeriod) {
            'day' => [100, 120, 90, 130, 110, 140, 150],
            'week' => [700, 800, 750, 900, 850, 950, 1000],
            'month' => [3000, 3200, 3100, 3500, 3400, 3600, 3700],
            'year' => [40000, 42000, 41000, 45000, 44000, 46000, 47000],
            default => [0,0,0,0,0,0,0],
        };
    $this->dispatch('update-chart', $this->chartData);
    }

    public function render()
    {
        return view('livewire.collected-stats');
    }
}
