<?php

namespace Tests\Feature\Filament;

use App\Filament\Profile\Resources\Projects\ProjectResource;
use App\Filament\Profile\Widgets\ProfileDonationsChartWidget;
use App\Filament\Profile\Widgets\ProfileOverviewStatsWidget;
use App\Filament\Profile\Widgets\ProfileRecentMessagesWidget;
use App\Filament\Profile\Widgets\ProfileRecentProjectsWidget;
use App\Models\Donation;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class ProfileDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_artist_sees_branded_profile_dashboard_with_all_widgets(): void
    {
        $user = User::factory()
            ->artist()
            ->profileCompleted()
            ->create();

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('profile'));

        $this->get('/profile')
            ->assertOk()
            ->assertSee('profile-dashboard-page', escape: false);

        Livewire::test(ProfileOverviewStatsWidget::class)
            ->assertSee(__('profile_dashboard.stats.projects'))
            ->assertSee('href="'.ProjectResource::getUrl(panel: 'profile').'"', escape: false);

        Livewire::test(ProfileDonationsChartWidget::class)
            ->assertSee(__('profile_dashboard.donations_chart.heading'))
            ->assertSee(__('profile_dashboard.donations_chart.dataset_made'))
            ->assertSee(__('profile_dashboard.donations_chart.dataset_received'));

        Livewire::test(ProfileRecentProjectsWidget::class)
            ->assertSee(__('profile_dashboard.recent_projects.heading'));

        Livewire::test(ProfileRecentMessagesWidget::class)
            ->assertSee(__('profile_dashboard.recent_messages.heading'));
    }

    public function test_donations_chart_separates_made_and_received_donations(): void
    {
        $artist = User::factory()->artist()->profileCompleted()->create();
        $otherUser = User::factory()->create();
        $ownProject = Project::factory()->for($artist)->create();
        $otherProject = Project::factory()->for($otherUser)->create();

        Donation::factory()->paid()->create([
            'project_id' => $otherProject->id,
            'user_id' => $artist->id,
            'amount' => 125,
            'paid_at' => now(),
        ]);

        Donation::factory()->paid()->create([
            'project_id' => $ownProject->id,
            'user_id' => $otherUser->id,
            'amount' => 350,
            'paid_at' => now(),
        ]);

        Donation::factory()->create([
            'project_id' => $ownProject->id,
            'user_id' => $otherUser->id,
            'amount' => 999,
            'paid_at' => null,
        ]);

        $this->actingAs($artist);
        Filament::setCurrentPanel(Filament::getPanel('profile'));

        $widget = Livewire::test(ProfileDonationsChartWidget::class)->instance();
        $getData = new ReflectionMethod($widget, 'getData');
        $data = $getData->invoke($widget);

        $this->assertSame(__('profile_dashboard.donations_chart.dataset_made'), $data['datasets'][0]['label']);
        $this->assertSame(125.0, $data['datasets'][0]['data'][5]);
        $this->assertSame(__('profile_dashboard.donations_chart.dataset_received'), $data['datasets'][1]['label']);
        $this->assertSame(350.0, $data['datasets'][1]['data'][5]);
    }
}
