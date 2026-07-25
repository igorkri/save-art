<?php

namespace Tests\Feature\Api\V1;

use App\Enums\NotificationType;
use App\Models\ContactInfoRequest;
use App\Models\Project;
use App\Models\User;

class ContactInfoRequestsApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_request_contact_info(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/projects/{$project->slug}/contact-request");

        $response->assertCreated();

        $this->assertDatabaseHas('contact_info_requests', [
            'requester_id' => $this->user->id,
            'owner_id' => $owner->id,
            'project_id' => $project->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $owner->id,
            'type' => NotificationType::ContactRequest->value,
        ]);
    }

    public function test_cannot_request_contact_info_twice_while_pending(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/projects/{$project->slug}/contact-request")
            ->assertCreated();

        $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/projects/{$project->slug}/contact-request")
            ->assertUnprocessable();
    }

    public function test_owner_can_grant_contact_request(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $contactInfoRequest = ContactInfoRequest::factory()->create([
            'requester_id' => $this->user->id,
            'owner_id' => $owner->id,
            'project_id' => $project->id,
            'status' => 'pending',
        ]);

        $response = $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/v1/my/contact-requests/{$contactInfoRequest->id}/grant");

        $response->assertOk();

        $this->assertDatabaseHas('contact_info_requests', [
            'id' => $contactInfoRequest->id,
            'status' => 'granted',
        ]);

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $this->user->id,
            'type' => NotificationType::ContactProvided->value,
        ]);
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $owner->id,
            'type' => NotificationType::ContactProvided->value,
        ]);
    }

    public function test_owner_can_reject_contact_request(): void
    {
        $owner = User::factory()->create();

        $contactInfoRequest = ContactInfoRequest::factory()->create([
            'requester_id' => $this->user->id,
            'owner_id' => $owner->id,
            'status' => 'pending',
        ]);

        $response = $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/v1/my/contact-requests/{$contactInfoRequest->id}/reject");

        $response->assertOk();

        $this->assertDatabaseHas('contact_info_requests', [
            'id' => $contactInfoRequest->id,
            'status' => 'rejected',
        ]);

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $this->user->id,
            'type' => NotificationType::ContactRejected->value,
        ]);
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $owner->id,
            'type' => NotificationType::ContactRejected->value,
        ]);
    }

    public function test_non_owner_cannot_decide_contact_request(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $contactInfoRequest = ContactInfoRequest::factory()->create([
            'requester_id' => $this->user->id,
            'owner_id' => $owner->id,
            'status' => 'pending',
        ]);

        $this->withHeaders($this->authHeaders($stranger))
            ->postJson("/api/v1/my/contact-requests/{$contactInfoRequest->id}/grant")
            ->assertForbidden();
    }

    public function test_cannot_decide_already_resolved_request(): void
    {
        $owner = User::factory()->create();

        $contactInfoRequest = ContactInfoRequest::factory()->create([
            'requester_id' => $this->user->id,
            'owner_id' => $owner->id,
            'status' => 'granted',
            'decided_at' => now(),
        ]);

        $this->withHeaders($this->authHeaders($owner))
            ->postJson("/api/v1/my/contact-requests/{$contactInfoRequest->id}/reject")
            ->assertUnprocessable();
    }
}
