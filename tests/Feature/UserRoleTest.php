<?php

namespace Tests\Feature;

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_default_user_role(): void
    {
        $user = User::factory()->create();

        $this->assertEquals(UserRole::User, $user->role);
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->canModerate());
    }

    public function test_user_can_have_specific_roles(): void
    {
        $developer = User::factory()->developer()->create();
        $admin = User::factory()->admin()->create();
        $moderator = User::factory()->moderator()->create();
        $owner = User::factory()->owner()->create();
        $mecenat = User::factory()->mecenat()->create();

        $this->assertEquals(UserRole::Developer, $developer->role);
        $this->assertEquals(UserRole::Admin, $admin->role);
        $this->assertEquals(UserRole::Moderator, $moderator->role);
        $this->assertEquals(UserRole::Owner, $owner->role);
        $this->assertEquals(UserRole::Mecenat, $mecenat->role);
    }

    public function test_admin_roles_are_identified_correctly(): void
    {
        $developer = User::factory()->developer()->create();
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->owner()->create();
        $moderator = User::factory()->moderator()->create();
        $user = User::factory()->create();

        $this->assertTrue($developer->isAdmin());
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($owner->isAdmin());
        $this->assertFalse($moderator->isAdmin());
        $this->assertFalse($user->isAdmin());
    }

    public function test_moderation_permissions_work_correctly(): void
    {
        $developer = User::factory()->developer()->create();
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->owner()->create();
        $moderator = User::factory()->moderator()->create();
        $mecenat = User::factory()->mecenat()->create();
        $user = User::factory()->create();

        $this->assertTrue($developer->canModerate());
        $this->assertTrue($admin->canModerate());
        $this->assertTrue($owner->canModerate());
        $this->assertTrue($moderator->canModerate());
        $this->assertFalse($mecenat->canModerate());
        $this->assertFalse($user->canModerate());
    }

    public function test_has_role_method_works(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($admin->hasRole(UserRole::Admin));
        $this->assertFalse($admin->hasRole(UserRole::User));
    }

    public function test_has_any_role_method_works(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($admin->hasAnyRole([UserRole::Admin, UserRole::Developer]));
        $this->assertFalse($admin->hasAnyRole([UserRole::User, UserRole::Moderator]));
    }

    public function test_role_labels_are_in_russian(): void
    {
        $developer = User::factory()->developer()->create();
        $admin = User::factory()->admin()->create();
        $moderator = User::factory()->moderator()->create();
        $owner = User::factory()->owner()->create();
        $mecenat = User::factory()->mecenat()->create();
        $user = User::factory()->create();

        $this->assertEquals('Разработчик', $developer->role_label);
        $this->assertEquals('Администратор', $admin->role_label);
        $this->assertEquals('Модератор', $moderator->role_label);
        $this->assertEquals('Владелец', $owner->role_label);
        $this->assertEquals('Меценат', $mecenat->role_label);
        $this->assertEquals('Пользователь', $user->role_label);
    }

    public function test_user_role_enum_methods(): void
    {
        $this->assertTrue(UserRole::Developer->isAdmin());
        $this->assertTrue(UserRole::Admin->isAdmin());
        $this->assertTrue(UserRole::Owner->isAdmin());
        $this->assertFalse(UserRole::Moderator->isAdmin());
        $this->assertFalse(UserRole::User->isAdmin());
        $this->assertFalse(UserRole::Mecenat->isAdmin());

        $this->assertTrue(UserRole::Developer->canModerate());
        $this->assertTrue(UserRole::Admin->canModerate());
        $this->assertTrue(UserRole::Owner->canModerate());
        $this->assertTrue(UserRole::Moderator->canModerate());
        $this->assertFalse(UserRole::User->canModerate());
        $this->assertFalse(UserRole::Mecenat->canModerate());
    }

    public function test_user_role_options_method(): void
    {
        $options = UserRole::getOptions();

        $this->assertArrayHasKey('developer', $options);
        $this->assertArrayHasKey('admin', $options);
        $this->assertArrayHasKey('moderator', $options);
        $this->assertArrayHasKey('owner', $options);
        $this->assertArrayHasKey('mecenat', $options);
        $this->assertArrayHasKey('user', $options);

        $this->assertEquals('Разработчик', $options['developer']);
        $this->assertEquals('Администратор', $options['admin']);
        $this->assertEquals('Модератор', $options['moderator']);
        $this->assertEquals('Владелец', $options['owner']);
        $this->assertEquals('Меценат', $options['mecenat']);
        $this->assertEquals('Пользователь', $options['user']);
    }
}
