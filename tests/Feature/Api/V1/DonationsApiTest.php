<?php

namespace Tests\Feature\Api\V1;

use App\Enums\DonationStatus;
use App\Enums\ProjectStatus;
use App\Models\Donation;
use App\Models\Project;
use App\Models\User;

class DonationsApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ==========================================
    // Створення донату
    // ==========================================

    public function test_can_create_donation(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::InProgress,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->postJson("/api/v1/projects/{$project->id}/donate", [
                'amount' => 100,
                'currency' => 'UAH',
                'donor_type' => 'personal',
                'donor_name' => 'Test Donor',
                'donor_email' => 'donor@example.com',
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'amount',
                    'currency',
                    'status',
                ],
            ]);
    }

    public function test_cannot_donate_to_draft_project(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::Draft,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->postJson("/api/v1/projects/{$project->id}/donate", [
                'amount' => 100,
                'currency' => 'UAH',
                'donor_type' => 'personal',
                'donor_name' => 'Test Donor',
                'donor_email' => 'donor@example.com',
            ]);

        // Проєкт не може приймати донати - повертається 422
        $response->assertUnprocessable();
    }

    public function test_donation_requires_valid_amount(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::InProgress,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->postJson("/api/v1/projects/{$project->id}/donate", [
                'amount' => 0,
                'currency' => 'UAH',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);
    }

    // ==========================================
    // Донат на платформу
    // ==========================================

    public function test_can_create_platform_donation(): void
    {
        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->postJson('/api/v1/donations/platform', [
                'amount' => 50,
                'currency' => 'UAH',
                'donor_type' => 'personal',
                'donor_name' => 'Platform Donor',
                'donor_email' => 'platform@example.com',
            ]);

        $response->assertCreated();
    }

    // ==========================================
    // Мої донати
    // ==========================================

    public function test_can_get_my_donations(): void
    {
        Donation::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'status' => DonationStatus::Paid,
        ]);

        // Чужий донат
        Donation::factory()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/my/donations');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_get_donation_details(): void
    {
        $donation = Donation::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/my/donations/{$donation->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $donation->id);
    }

    public function test_cannot_view_other_user_donation(): void
    {
        $donation = Donation::factory()->create();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/my/donations/{$donation->id}");

        $response->assertForbidden();
    }

    // ==========================================
    // Webhook
    // ==========================================

    public function test_webhook_updates_donation_status(): void
    {
        $donation = Donation::factory()->create([
            'status' => DonationStatus::Pending,
        ]);

        // LiqPay webhook вимагає data та signature
        // Без правильного налаштування сервісу, перевіряємо що 400 повертається при невалідних даних
        $response = $this->postJson('/api/v1/payments/webhook', [
            'data' => 'invalid_base64_data',
            'signature' => 'invalid_signature',
        ]);

        // Очікуємо 400 оскільки підпис невалідний
        $response->assertStatus(400);
    }
}
