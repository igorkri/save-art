<?php

namespace Tests\Feature\Filament;

use App\Filament\Profile\Resources\Services\Pages\CreateService;
use App\Filament\Profile\Resources\Services\Pages\ListServices;
use App\Models\ArtCategory;
use App\Models\Service;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('filament')]
class ServiceResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ArtCategory $artCategory;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->user = User::factory()->artist()->create();
        $this->artCategory = ArtCategory::factory()->create();

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('profile'));
    }

    public function test_artist_can_create_personal_service(): void
    {
        Livewire::test(CreateService::class)
            ->fillForm([
                'owner_type' => 'personal',
                'title' => 'Реставрація картин',
                'art_category_id' => $this->artCategory->id,
                'image' => UploadedFile::fake()->image('restoration.jpg'),
                'description' => 'Професійна реставрація творів.',
                'price' => 1000,
                'currency' => 'UAH',
                'options' => [
                    ['name' => ['uk' => 'Базова реставрація']],
                ],
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
                'art_category_id' => $this->artCategory->id,
                'image' => UploadedFile::fake()->image('team-service.jpg'),
                'description' => 'Професійна послуга команди.',
                'price' => 1000,
                'currency' => 'UAH',
                'options' => [
                    ['name' => ['uk' => 'Базова опція']],
                ],
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('services', [
            'serviceable_type' => Team::class,
            'serviceable_id' => $team->id,
            'title' => 'Послуга команди',
        ]);
    }

    public function test_artist_can_create_service_with_options_and_negotiable_price(): void
    {
        Livewire::test(CreateService::class)
            ->fillForm([
                'owner_type' => 'personal',
                'title' => 'Написання портрета',
                'art_category_id' => $this->artCategory->id,
                'image' => UploadedFile::fake()->image('portrait.jpg'),
                'description' => 'Портрет на замовлення.',
                'price' => 5000,
                'currency' => 'UAH',
                'negotiable' => true,
                'options' => [
                    ['name' => ['uk' => 'Термінове виконання']],
                ],
            ])
            ->call('create')
            ->assertHasNoErrors();

        $service = Service::query()->where('title', 'Написання портрета')->firstOrFail();

        $this->assertNull($service->price);
        $this->assertFalse($service->price_from);
        $this->assertSame('Термінове виконання', $service->options[0]['name']['uk']);
    }

    public function test_price_is_required_when_negotiable_price_is_not_selected(): void
    {
        Livewire::test(CreateService::class)
            ->fillForm([
                'owner_type' => 'personal',
                'title' => 'Консультація',
                'art_category_id' => $this->artCategory->id,
                'negotiable' => false,
                'price' => null,
                'currency' => 'UAH',
            ])
            ->call('create')
            ->assertHasFormErrors(['price' => 'required']);
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
