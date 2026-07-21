<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\ImpersonationToken;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('filament')]
class UserImpersonateActionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email' => 'admin@example.com',
        ]);
    }

    public function test_impersonate_action_issues_a_grant_and_opens_frontend_in_new_tab(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->admin);

        $component = Livewire::test(ListUsers::class)
            ->callTableAction('impersonate', $target);

        $grant = ImpersonationToken::where('user_id', $target->id)->first();

        $this->assertNotNull($grant);
        $this->assertSame($this->admin->id, $grant->created_by);
        $this->assertNull($grant->project_slug);
        $this->assertTrue($grant->isValid());

        $jsCalls = $component->effects['xjs'] ?? [];
        $this->assertNotEmpty($jsCalls);
        $this->assertStringContainsString('window.open', $jsCalls[0]['expression']);
        $this->assertStringContainsString($grant->token, $jsCalls[0]['expression']);
    }
}
