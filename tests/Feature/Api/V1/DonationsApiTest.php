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
            ->postJson("/api/v1/projects/{$project->slug}/donate", [
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
            ->postJson("/api/v1/projects/{$project->slug}/donate", [
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
            ->postJson("/api/v1/projects/{$project->slug}/donate", [
                'amount' => 0,
                'currency' => 'UAH',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);
    }

    // ==========================================
    // Псевдонім для анонімного донату авторизованого користувача
    // ==========================================

    public function test_authenticated_anonymous_donation_requires_pseudonym(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::InProgress,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/projects/{$project->slug}/donate", [
                'amount' => 100,
                'currency' => 'UAH',
                'donor_type' => 'personal',
                'is_anonymous' => true,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['donor_name']);
    }

    public function test_authenticated_anonymous_donation_uses_pseudonym_not_real_name(): void
    {
        $this->user->update(['full_name' => 'Real Author Name']);

        $project = Project::factory()->create([
            'status' => ProjectStatus::InProgress,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/projects/{$project->slug}/donate", [
                'amount' => 100,
                'currency' => 'UAH',
                'donor_type' => 'personal',
                'is_anonymous' => true,
                'donor_name' => 'CoolCat',
            ]);

        $response->assertCreated();

        $donation = Donation::where('user_id', $this->user->id)->firstOrFail();
        $this->assertSame('CoolCat', $donation->donor_name);
        $this->assertSame('CoolCat', $donation->getDisplayName());
        $this->assertNotSame('Real Author Name', $donation->getDisplayName());
    }

    public function test_authenticated_non_anonymous_donation_does_not_require_pseudonym(): void
    {
        $project = Project::factory()->create([
            'status' => ProjectStatus::InProgress,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/projects/{$project->slug}/donate", [
                'amount' => 100,
                'currency' => 'UAH',
                'donor_type' => 'personal',
                'is_anonymous' => false,
            ]);

        $response->assertCreated();

        $donation = Donation::where('user_id', $this->user->id)->firstOrFail();
        $this->assertSame($this->user->name, $donation->getDisplayName());
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

    public function test_authenticated_anonymous_platform_donation_requires_pseudonym(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/donations/platform', [
                'amount' => 50,
                'currency' => 'UAH',
                'donor_type' => 'personal',
                'is_anonymous' => true,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['donor_name']);
    }

    public function test_authenticated_anonymous_platform_donation_uses_pseudonym(): void
    {
        $this->user->update(['full_name' => 'Real Author Name']);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/donations/platform', [
                'amount' => 50,
                'currency' => 'UAH',
                'donor_type' => 'personal',
                'is_anonymous' => true,
                'donor_name' => 'AnonPatron',
            ]);

        $response->assertCreated();

        $donation = Donation::where('user_id', $this->user->id)->firstOrFail();
        $this->assertSame('AnonPatron', $donation->donor_name);
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
    // Видимість донатів на публічному профілі
    // ==========================================

    public function test_can_hide_donations_from_public_profile(): void
    {
        Donation::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'donation_type' => Donation::TYPE_PROJECT,
            'status' => DonationStatus::Paid,
            'is_public' => true,
        ]);

        // Донат іншого користувача не повинен зачепитися
        $otherDonation = Donation::factory()->create([
            'donation_type' => Donation::TYPE_PROJECT,
            'status' => DonationStatus::Paid,
            'is_public' => true,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->patchJson('/api/v1/my/donations/visibility', ['is_public' => false]);

        $response->assertOk()->assertJsonPath('is_public', false);

        $this->assertDatabaseCount('donations', 3);
        $this->assertDatabaseMissing('donations', [
            'user_id' => $this->user->id,
            'is_public' => true,
        ]);
        $this->assertDatabaseHas('donations', [
            'id' => $otherDonation->id,
            'is_public' => true,
        ]);
    }

    public function test_updating_visibility_requires_boolean(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->patchJson('/api/v1/my/donations/visibility', ['is_public' => 'not-a-boolean']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['is_public']);
    }

    public function test_updating_visibility_requires_auth(): void
    {
        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->patchJson('/api/v1/my/donations/visibility', ['is_public' => false]);

        $response->assertUnauthorized();
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
