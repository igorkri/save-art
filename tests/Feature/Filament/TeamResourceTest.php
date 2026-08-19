<?php

namespace Tests\Feature\Filament;

use App\Filament\Profile\Resources\Teams\Pages\CreateTeam;
use App\Filament\Profile\Resources\Teams\Pages\ListTeams;
use App\Filament\Profile\Resources\Teams\TeamResource;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('filament')]
class TeamResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->artist()->profileCompleted()->create();

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('profile'));
    }

    private function validFormData(array $overrides = []): array
    {
        return array_merge([
            'name.uk' => 'Моя команда',
            'name.en' => 'My team',
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
            'country.uk' => 'Україна',
            'country.en' => 'Ukraine',
            'city.uk' => 'Київ',
            'city.en' => 'Kyiv',
            'region.uk' => 'Київська',
            'region.en' => 'Kyiv region',
            'zip.uk' => '01001',
            'zip.en' => '01001',
            'specialization.uk' => 'Живопис',
            'specialization.en' => 'Painting',
            'description.uk' => 'Опис команди',
            'description.en' => 'Team description',
            'teamMembers' => [],
        ], $overrides);
    }

    public function test_creating_team_makes_creator_the_owner(): void
    {
        Livewire::test(CreateTeam::class)
            ->fillForm($this->validFormData())
            ->call('create')
            ->assertHasNoErrors();

        $team = Team::first();
        $this->assertNotNull($team);
        $this->assertDatabaseHas('team_members', [
            'team_id' => $team->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
        ]);
    }

    public function test_user_sees_only_teams_they_belong_to(): void
    {
        $ownTeam = Team::factory()->create();
        $ownTeam->teamMembers()->create(['user_id' => $this->user->id, 'role' => 'owner', 'sort_order' => 0]);

        $foreignTeam = Team::factory()->create();
        $foreignTeam->teamMembers()->create(['user_id' => User::factory()->create()->id, 'role' => 'owner', 'sort_order' => 0]);

        Livewire::test(ListTeams::class)
            ->assertCanSeeTableRecords([$ownTeam])
            ->assertCanNotSeeTableRecords([$foreignTeam]);
    }

    public function test_non_owner_cannot_edit_team(): void
    {
        $team = Team::factory()->create();
        $owner = User::factory()->create();
        $team->teamMembers()->create(['user_id' => $owner->id, 'role' => 'owner', 'sort_order' => 0]);
        $team->teamMembers()->create(['user_id' => $this->user->id, 'role' => 'member', 'sort_order' => 1]);

        $this->get(TeamResource::getUrl('edit', ['record' => $team]))
            ->assertForbidden();
    }

    public function test_owner_can_access_edit_page(): void
    {
        $team = Team::factory()->create();
        $team->teamMembers()->create(['user_id' => $this->user->id, 'role' => 'owner', 'sort_order' => 0]);

        $this->get(TeamResource::getUrl('edit', ['record' => $team]))
            ->assertOk();
    }

    public function test_member_can_leave_team(): void
    {
        $team = Team::factory()->create();
        $owner = User::factory()->create();
        $team->teamMembers()->create(['user_id' => $owner->id, 'role' => 'owner', 'sort_order' => 0]);
        $team->teamMembers()->create(['user_id' => $this->user->id, 'role' => 'member', 'sort_order' => 1]);

        Livewire::test(ListTeams::class)
            ->callTableAction('leave', $team);

        $this->assertDatabaseMissing('team_members', [
            'team_id' => $team->id,
            'user_id' => $this->user->id,
        ]);
        $this->assertDatabaseHas('team_members', [
            'team_id' => $team->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_owner_cannot_leave_team(): void
    {
        $team = Team::factory()->create();
        $team->teamMembers()->create(['user_id' => $this->user->id, 'role' => 'owner', 'sort_order' => 0]);

        Livewire::test(ListTeams::class)
            ->assertTableActionHidden('leave', $team);
    }
}
