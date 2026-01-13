<?php

namespace Tests\Feature\Api\V1;

use App\Models\Faq;
use App\Models\FaqCategory;

class FaqApiTest extends ApiTestCase
{
    // ==========================================
    // Список FAQ
    // ==========================================

    public function test_can_get_faq_list(): void
    {
        $category = FaqCategory::factory()->create();
        Faq::factory()->count(5)->create([
            'faq_category_id' => $category->id,
            'is_active' => true,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/faq');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'question',
                        'answer',
                    ],
                ],
            ]);
    }

    public function test_inactive_faq_not_shown(): void
    {
        Faq::factory()->create(['is_active' => false]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/faq');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    // ==========================================
    // FAQ по мові
    // ==========================================

    public function test_can_get_faq_by_language(): void
    {
        Faq::factory()->create([
            'is_active' => true,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/faq/language/uk');

        $response->assertOk();
    }

    public function test_returns_empty_for_unsupported_language(): void
    {
        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/faq/language/fr');

        $response->assertOk();
    }

    // ==========================================
    // FAQ по категорії
    // ==========================================

    public function test_can_get_faq_by_category(): void
    {
        $category = FaqCategory::factory()->create([
            'slug' => 'donations',
        ]);
        Faq::factory()->count(3)->create([
            'faq_category_id' => $category->id,
            'is_active' => true,
        ]);

        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/faq/category/donations');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_returns_404_for_nonexistent_category(): void
    {
        $response = $this->withHeaders(['X-Api-Key' => $this->apiKey])
            ->getJson('/api/v1/faq/category/nonexistent');

        $response->assertNotFound();
    }
}
