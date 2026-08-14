<?php

namespace Tests\Feature\Filament;

use App\Filament\Profile\Resources\Donations\Pages\ListDonations;
use App\Models\Donation;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('filament')]
class DonationResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $artist;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artist = User::factory()->artist()->create();

        $this->actingAs($this->artist);
        Filament::setCurrentPanel(Filament::getPanel('profile'));
    }

    public function test_artist_sees_donations_received_on_own_projects(): void
    {
        $project = Project::factory()->for($this->artist)->create();
        $received = Donation::factory()->create(['project_id' => $project->id, 'status' => 'paid']);

        $otherProject = Project::factory()->create();
        Donation::factory()->create(['project_id' => $otherProject->id, 'status' => 'paid']);

        Livewire::test(ListDonations::class)
            ->assertCanSeeTableRecords([$received])
            ->assertCountTableRecords(1);
    }

    public function test_patron_sees_donations_they_made(): void
    {
        $made = Donation::factory()->create(['user_id' => $this->artist->id, 'status' => 'paid']);

        Donation::factory()->create(['status' => 'paid']);

        Livewire::test(ListDonations::class)
            ->assertCanSeeTableRecords([$made])
            ->assertCountTableRecords(1);
    }

    public function test_donation_to_own_project_is_not_duplicated(): void
    {
        $project = Project::factory()->for($this->artist)->create();
        Donation::factory()->create([
            'project_id' => $project->id,
            'user_id' => $this->artist->id,
            'status' => 'paid',
        ]);

        Livewire::test(ListDonations::class)
            ->assertCountTableRecords(1);
    }

    public function test_donations_page_is_registered_in_profile_panel(): void
    {
        $project = Project::factory()->for($this->artist)->create(['title' => 'Унікальний проєкт для донатів']);
        Donation::factory()->create(['project_id' => $project->id, 'status' => 'paid']);

        $this->get('/profile/donations')
            ->assertOk()
            ->assertSee('Унікальний проєкт для донатів');
    }
}
