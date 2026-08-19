<?php

namespace Tests\Feature\Filament;

use App\Filament\Profile\Resources\Services\Pages\CreateService;
use App\Filament\Profile\Resources\Services\Pages\ListServices;
use App\Models\Service;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('filament')]
class ServiceResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->artist()->create();

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('profile'));
    }

    public function test_artist_can_create_personal_service(): void
    {
        Livewire::test(CreateService::class)
            ->fillForm([
                'owner_type' => 'personal',
                'title' => 'Реставрація картин',
                'currency' => 'UAH',
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('services', [
            'serviceable_type' => User::class,
            'serviceable_id' => $this->user->id,
            'title' => 'Реставрація картин',
        ]);
    }

    public function test_artist_can_create_service_for_own_team(): void
    {
        $team = Team::factory()->create();
        $team->teamMembers()->create(['user_id' => $this->user->id, 'role' => 'member', 'sort_order' => 0]);

        Livewire::test(CreateService::class)
            ->fillForm([
                'owner_type' => 'team',
                'team_id' => $team->id,
                'title' => 'Послуга команди',
                'currency' => 'UAH',
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('services', [
            'serviceable_type' => Team::class,
            'serviceable_id' => $team->id,
            'title' => 'Послуга команди',
        ]);
    }

    public function test_own_team_service_is_visible_in_list(): void
    {
        $team = Team::factory()->create();
        $team->teamMembers()->create(['user_id' => $this->user->id, 'role' => 'member', 'sort_order' => 0]);
        $teamService = Service::factory()->create([
            'serviceable_type' => Team::class,
            'serviceable_id' => $team->id,
        ]);

        Livewire::test(ListServices::class)
            ->assertCanSeeTableRecords([$teamService]);
    }

    public function test_foreign_team_service_is_not_visible(): void
    {
        $foreignTeam = Team::factory()->create();
        $foreignTeam->teamMembers()->create(['user_id' => User::factory()->create()->id, 'role' => 'owner', 'sort_order' => 0]);
        $foreignService = Service::factory()->create([
            'serviceable_type' => Team::class,
            'serviceable_id' => $foreignTeam->id,
        ]);

        Livewire::test(ListServices::class)
            ->assertCanNotSeeTableRecords([$foreignService]);
    }

    public function test_own_personal_service_still_visible_regression(): void
    {
        $personalService = Service::factory()->create([
            'serviceable_type' => User::class,
            'serviceable_id' => $this->user->id,
        ]);

        Livewire::test(ListServices::class)
            ->assertCanSeeTableRecords([$personalService]);
    }
}
