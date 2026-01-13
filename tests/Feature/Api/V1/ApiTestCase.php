<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    /**
     * API Key for testing.
     */
    protected string $apiKey = '74j1aF+qYgihMEUlQqhBmbCCZIl8+G8AU8BrYp7+sIc=';

    /**
     * Default user for authenticated tests.
     */
    protected ?User $user = null;

    /**
     * Default project for tests.
     */
    protected mixed $project = null;

    /**
     * Get headers with API key.
     */
    protected function apiHeaders(): array
    {
        return [
            'X-Api-Key' => $this->apiKey,
            'Accept' => 'application/json',
        ];
    }

    /**
     * Get headers with API key and auth token.
     */
    protected function authHeaders(?User $user = null): array
    {
        $user = $user ?? $this->user;

        if (! $user) {
            throw new \RuntimeException('No user provided for authentication');
        }

        $token = $user->createToken('test')->plainTextToken;

        return [
            'Authorization' => "Bearer {$token}",
            'X-Api-Key' => $this->apiKey,
            'Accept' => 'application/json',
        ];
    }

    /**
     * Make GET request with API key.
     */
    protected function apiGet(string $uri): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders($this->apiHeaders())->getJson($uri);
    }

    /**
     * Make POST request with API key.
     */
    protected function apiPost(string $uri, array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders($this->apiHeaders())->postJson($uri, $data);
    }

    /**
     * Make PUT request with API key.
     */
    protected function apiPut(string $uri, array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders($this->apiHeaders())->putJson($uri, $data);
    }

    /**
     * Make PATCH request with API key.
     */
    protected function apiPatch(string $uri, array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders($this->apiHeaders())->patchJson($uri, $data);
    }

    /**
     * Make DELETE request with API key.
     */
    protected function apiDelete(string $uri): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders($this->apiHeaders())->deleteJson($uri);
    }

    /**
     * Make authenticated GET request.
     */
    protected function authGet(User $user, string $uri): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders($this->authHeaders($user))->getJson($uri);
    }

    /**
     * Make authenticated POST request.
     */
    protected function authPost(User $user, string $uri, array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders($this->authHeaders($user))->postJson($uri, $data);
    }

    /**
     * Make authenticated PUT request.
     */
    protected function authPut(User $user, string $uri, array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders($this->authHeaders($user))->putJson($uri, $data);
    }

    /**
     * Make authenticated PATCH request.
     */
    protected function authPatch(User $user, string $uri, array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders($this->authHeaders($user))->patchJson($uri, $data);
    }

    /**
     * Make authenticated DELETE request.
     */
    protected function authDelete(User $user, string $uri): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders($this->authHeaders($user))->deleteJson($uri);
    }
}
