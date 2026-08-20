<?php

namespace Tests\Feature\Filament;

use Filament\Enums\ThemeMode;
use Filament\Facades\Filament;
use Tests\TestCase;

class ProfileThemeTest extends TestCase
{
    public function test_profile_panel_has_one_forced_dark_theme(): void
    {
        $panel = Filament::getPanel('profile');

        $this->assertTrue($panel->hasDarkMode());
        $this->assertTrue($panel->hasDarkModeForced());
        $this->assertFalse($panel->hasThemeSwitcher());
        $this->assertSame(ThemeMode::Dark, $panel->getDefaultThemeMode());
    }
}
