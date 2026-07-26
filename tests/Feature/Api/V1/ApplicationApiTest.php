<?php

namespace Tests\Feature\Api\V1;

use App\Jobs\SendApplicationEmail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

class ApplicationApiTest extends ApiTestCase
{
    public function test_can_submit_application(): void
    {
        Mail::fake();

        $response = $this->withHeaders($this->apiHeaders())
            ->postJson('/api/v1/applications', [
                'name' => 'Іван Іваненко',
                'email' => 'ivan@example.com',
                'phone' => '+380501234567',
                'about' => 'Хочу долучитись як волонтер.',
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Заявку надіслано.');

        Mail::assertSent(\App\Mail\ApplicationSubmitted::class, function ($mail) {
            return $mail->applicant['name'] === 'Іван Іваненко'
                && $mail->applicant['email'] === 'ivan@example.com'
                && $mail->hasTo(config('services.application_email'));
        });
    }

    public function test_submitting_application_dispatches_queued_job(): void
    {
        Queue::fake();

        $response = $this->withHeaders($this->apiHeaders())
            ->postJson('/api/v1/applications', [
                'name' => 'Іван Іваненко',
                'email' => 'ivan@example.com',
            ]);

        $response->assertOk();

        Queue::assertPushed(SendApplicationEmail::class, function ($job) {
            return $job->applicant['name'] === 'Іван Іваненко';
        });
    }

    public function test_can_submit_application_with_resume(): void
    {
        Mail::fake();

        $file = UploadedFile::fake()->create('resume.pdf', 500, 'application/pdf');

        $response = $this->withHeaders($this->apiHeaders())
            ->postJson('/api/v1/applications', [
                'name' => 'Марія Коваль',
                'email' => 'maria@example.com',
                'resume' => $file,
            ]);

        $response->assertOk();

        Mail::assertSent(\App\Mail\ApplicationSubmitted::class, function ($mail) {
            return $mail->resume !== null
                && $mail->resume['original_name'] === 'resume.pdf';
        });
    }

    public function test_name_and_email_are_required(): void
    {
        $response = $this->withHeaders($this->apiHeaders())
            ->postJson('/api/v1/applications', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_email_must_be_valid(): void
    {
        $response = $this->withHeaders($this->apiHeaders())
            ->postJson('/api/v1/applications', [
                'name' => 'Test',
                'email' => 'not-an-email',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_resume_must_be_pdf_or_doc(): void
    {
        $file = UploadedFile::fake()->create('resume.exe', 100, 'application/x-msdownload');

        $response = $this->withHeaders($this->apiHeaders())
            ->postJson('/api/v1/applications', [
                'name' => 'Test',
                'email' => 'test@example.com',
                'resume' => $file,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['resume']);
    }
}
