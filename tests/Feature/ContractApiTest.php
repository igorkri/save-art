<?php

namespace Tests\Feature;

use App\Enums\ContractStatus;
use App\Enums\SignService;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_get_contract_template(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/contracts/template');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['version', 'file_url', 'expires_days'],
                'sign_services',
            ]);
    }

    public function test_user_can_list_contracts(): void
    {
        Contract::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/contracts');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'template_version',
                        'status',
                        'status_label',
                        'is_pending',
                        'is_signed',
                    ],
                ],
                'has_signed_contract',
            ]);
    }

    public function test_user_can_create_contract(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/contracts');

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'template_version',
                    'status',
                ],
                'message',
            ]);

        $this->assertDatabaseHas('contracts', [
            'user_id' => $this->user->id,
            'status' => ContractStatus::Pending->value,
        ]);
    }

    public function test_creating_contract_when_pending_exists_returns_existing(): void
    {
        $existingContract = Contract::factory()->create([
            'user_id' => $this->user->id,
            'status' => ContractStatus::Pending,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/contracts');

        $response->assertOk()
            ->assertJsonPath('data.id', $existingContract->id);
    }

    public function test_user_can_view_own_contract(): void
    {
        $contract = Contract::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/contracts/{$contract->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $contract->id);
    }

    public function test_user_cannot_view_others_contract(): void
    {
        $otherUser = User::factory()->create();
        $contract = Contract::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/contracts/{$contract->id}");

        $response->assertForbidden();
    }

    public function test_user_can_sign_pending_contract(): void
    {
        $contract = Contract::factory()->create([
            'user_id' => $this->user->id,
            'status' => ContractStatus::Pending,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/contracts/{$contract->id}/sign", [
                'sign_service' => SignService::Diia->value,
                'signature_base64' => base64_encode('test_signature_data'),
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', ContractStatus::Signed->value);

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'status' => ContractStatus::Signed->value,
            'sign_service' => SignService::Diia->value,
        ]);
    }

    public function test_user_cannot_sign_already_signed_contract(): void
    {
        $contract = Contract::factory()->signed()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/contracts/{$contract->id}/sign", [
                'sign_service' => SignService::Diia->value,
                'signature_base64' => base64_encode('test_signature_data'),
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', __('contracts.errors.not_pending'));
    }

    public function test_user_cannot_sign_expired_contract(): void
    {
        $contract = Contract::factory()->create([
            'user_id' => $this->user->id,
            'status' => ContractStatus::Pending,
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/contracts/{$contract->id}/sign", [
                'sign_service' => SignService::Diia->value,
                'signature_base64' => base64_encode('test_signature_data'),
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', __('contracts.errors.expired'));
    }

    public function test_user_can_get_active_contract(): void
    {
        Contract::factory()->signed()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/contracts/active');

        $response->assertOk()
            ->assertJsonPath('data.status', ContractStatus::Signed->value);
    }

    public function test_user_gets_404_when_no_active_contract(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/contracts/active');

        $response->assertNotFound();
    }

    public function test_sign_contract_validation(): void
    {
        $contract = Contract::factory()->create([
            'user_id' => $this->user->id,
            'status' => ContractStatus::Pending,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/contracts/{$contract->id}/sign", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['sign_service', 'signature_base64']);
    }

    public function test_unauthenticated_user_cannot_access_contracts(): void
    {
        $response = $this->getJson('/api/v1/contracts');

        $response->assertUnauthorized();
    }
}
