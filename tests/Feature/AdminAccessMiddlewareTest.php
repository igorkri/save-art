<?php

namespace Tests\Feature;

use App\Http\Middleware\AdminAccess;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AdminAccessMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected AdminAccess $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new AdminAccess;
    }

    public function test_allows_access_for_admin_users(): void
    {
        // Arrange
        $user = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($user);

        $request = Request::create('/admin');
        $next = function () {
            return response('success');
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals('success', $response->getContent());
    }

    public function test_allows_access_for_developer_users(): void
    {
        // Arrange
        $user = User::factory()->create(['role' => UserRole::Developer]);
        $this->actingAs($user);

        $request = Request::create('/admin');
        $next = function () {
            return response('success');
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals('success', $response->getContent());
    }

    public function test_denies_access_for_regular_users(): void
    {
        // Arrange
        $user = User::factory()->create(['role' => UserRole::User]);
        $this->actingAs($user);

        $request = Request::create('/admin');
        $next = function () {
            return response('success');
        };

        // Act & Assert
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Access denied. Administrator role required.');

        $this->middleware->handle($request, $next);
    }

    public function test_denies_access_for_mecenat_users(): void
    {
        // Arrange
        $user = User::factory()->create(['role' => UserRole::Mecenat]);
        $this->actingAs($user);

        $request = Request::create('/admin');
        $next = function () {
            return response('success');
        };

        // Act & Assert
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Access denied. Administrator role required.');

        $this->middleware->handle($request, $next);
    }

    public function test_denies_access_for_unauthenticated_users(): void
    {
        // Arrange - не авторизуемся
        $request = Request::create('/admin');
        $next = function () {
            return response('success');
        };

        // Act & Assert
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->middleware->handle($request, $next);
    }

    public function test_allows_access_for_owner_users(): void
    {
        // Owner тоже должен иметь доступ к админке
        // Arrange
        $user = User::factory()->create(['role' => UserRole::Owner]);
        $this->actingAs($user);

        $request = Request::create('/admin');
        $next = function () {
            return response('success');
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals('success', $response->getContent());
    }
}
