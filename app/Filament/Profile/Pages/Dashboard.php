<?php

namespace App\Filament\Profile\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    /**
     * @return array<string>
     */
    public function getPageClasses(): array
    {
        return ['profile-dashboard-page'];
    }
}
