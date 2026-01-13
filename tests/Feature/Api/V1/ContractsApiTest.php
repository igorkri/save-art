<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Models\User;

class ContractsApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ==========================================
    // Шаблон контракту
    // ==========================================

    public function test_can_get_contract_template(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/contracts/template');

        $response->assertOk();
    }

    public function test_can_download_contract_template(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/contracts/template/download');

        $response->assertOk();
    }

    // ==========================================
    // Активний контракт
    // ==========================================

    public function test_can_get_active_contract(): void
    {
        Contract::factory()->create([
            'user_id' => $this->user->id,
            'status' => ContractStatus::Signed,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/contracts/active');

        $response->assertOk()
            ->assertJsonPath('data.status', 'signed');
    }

    public function test_returns_null_when_no_active_contract(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/contracts/active');

        $response->assertOk()
            ->assertJsonPath('data', null);
    }

    // ==========================================
    // Список контрактів
    // ==========================================

    public function test_can_get_contracts_list(): void
    {
        Contract::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/contracts');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_cannot_see_other_user_contracts(): void
    {
        Contract::factory()->count(2)->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/contracts');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    // ==========================================
    // Створення контракту
    // ==========================================

    public function test_can_create_contract(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/contracts', [
                'type' => 'standard',
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('contracts', [
            'user_id' => $this->user->id,
        ]);
    }

    // ==========================================
    // Перегляд контракту
    // ==========================================

    public function test_can_view_contract(): void
    {
        $contract = Contract::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/contracts/{$contract->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $contract->id);
    }

    public function test_cannot_view_other_user_contract(): void
    {
        $contract = Contract::factory()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/contracts/{$contract->id}");

        $response->assertForbidden();
    }

    // ==========================================
    // Підписання контракту
    // ==========================================

    public function test_can_sign_contract(): void
    {
        $contract = Contract::factory()->create([
            'user_id' => $this->user->id,
            'status' => ContractStatus::Pending,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/contracts/{$contract->id}/sign", [
                'signature' => 'digital-signature-data',
            ]);

        $response->assertOk();
    }

    public function test_cannot_sign_already_signed_contract(): void
    {
        $contract = Contract::factory()->create([
            'user_id' => $this->user->id,
            'status' => ContractStatus::Signed,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/contracts/{$contract->id}/sign", [
                'signature' => 'signature',
            ]);

        $response->assertUnprocessable();
    }

    // ==========================================
    // Завантаження контракту
    // ==========================================

    public function test_can_download_contract(): void
    {
        $contract = Contract::factory()->create([
            'user_id' => $this->user->id,
            'status' => ContractStatus::Signed,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/contracts/{$contract->id}/download");

        $response->assertOk();
    }
}
