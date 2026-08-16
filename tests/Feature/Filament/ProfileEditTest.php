<?php

namespace Tests\Feature\Filament;

use App\Enums\Currency;
use App\Filament\Profile\Pages\Auth\EditProfile;
use App\Filament\Profile\Resources\Projects\ProjectResource;
use App\Filament\Profile\Resources\Services\ServiceResource;
use App\Models\Project;
use App\Models\Service;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('filament')]
class ProfileEditTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::disk('public')->put('avatars/old.jpg', 'old avatar');

        $this->user = User::factory()->artist()->create([
            'email' => 'artist@example.com',
            'full_name' => 'Старе імʼя',
            'phone' => '+380501234567',
            'avatar' => 'avatars/old.jpg',
        ]);

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('profile'));
    }

    public function test_artist_can_update_own_user_legal_and_social_profile(): void
    {
        Livewire::test(EditProfile::class)
            ->set('data.full_name', 'Нове імʼя')
            ->set('data.profession', 'Художник')
            ->set('data.profileLegal.currency', Currency::USD->value)
            ->set('data.profileLegal.name', 'ТОВ Мистецтво')
            ->set('data.profileLegal.edrpou', '12345678')
            ->set('data.profileSocial.website', 'https://artist.example.com')
            ->set('data.profileSocial.instagram', 'https://instagram.com/artist')
            ->call('save')
            ->assertHasNoErrors();

        $this->user->refresh();

        $this->assertSame('Нове імʼя', $this->user->full_name);
        $this->assertSame('Художник', $this->user->profession);
        $this->assertSame('ТОВ Мистецтво', $this->user->profileLegal->name);
        $this->assertSame(Currency::USD, $this->user->profileLegal->currency);
        $this->assertSame('https://artist.example.com', $this->user->profileSocial->website);
        $this->assertSame('https://instagram.com/artist', $this->user->profileSocial->instagram);
    }

    public function test_saving_profile_marks_it_as_completed(): void
    {
        $this->assertNull($this->user->profile_completed_at);

        Livewire::test(EditProfile::class)
            ->set('data.full_name', 'Нове імʼя')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue($this->user->refresh()->isProfileComplete());
    }

    public function test_saving_profile_again_does_not_move_completed_at_timestamp(): void
    {
        Livewire::test(EditProfile::class)
            ->set('data.full_name', 'Перше збереження')
            ->call('save')
            ->assertHasNoErrors();

        $firstCompletedAt = $this->user->refresh()->profile_completed_at;

        Livewire::test(EditProfile::class)
            ->set('data.full_name', 'Друге збереження')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue($firstCompletedAt->equalTo($this->user->refresh()->profile_completed_at));
    }

    public function test_profile_page_is_registered_in_profile_panel(): void
    {
        $this->get('/profile/profile')
            ->assertOk()
            ->assertSee('Мій профіль')
            ->assertSee('Пошук');
    }

    public function test_global_search_only_returns_current_artists_records(): void
    {
        $otherUser = User::factory()->artist()->create();

        $ownProject = Project::factory()->for($this->user)->create(['title' => 'Унікальний живопис']);
        Project::factory()->for($otherUser)->create(['title' => 'Унікальний живопис іншого автора']);

        $ownService = Service::factory()->create([
            'serviceable_id' => $this->user->id,
            'title' => 'Унікальна консультація',
        ]);
        Service::factory()->create([
            'serviceable_id' => $otherUser->id,
            'title' => 'Унікальна консультація іншого автора',
        ]);

        $projectResults = ProjectResource::getGlobalSearchResults('Унікальний живопис');
        $serviceResults = ServiceResource::getGlobalSearchResults('Унікальна консультація');

        $this->assertCount(1, $projectResults);
        $this->assertSame($ownProject->title, (string) $projectResults->first()->title);
        $this->assertCount(1, $serviceResults);
        $this->assertSame($ownService->title, (string) $serviceResults->first()->title);
    }
}
