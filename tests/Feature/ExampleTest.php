<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Тест що API health endpoint працює
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // Перевіряємо що API працює замість головної сторінки
        // (головна сторінка потребує seed-даних HomePage)
        $response = $this->get('/api/v1/categories');

        $response->assertStatus(200);
    }
}
