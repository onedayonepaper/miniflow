<?php

namespace Tests\Feature\Requests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\CreatesTestData;

class SubmitRequestTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    public function test_user_can_submit_their_own_draft_request(): void
    {
        Event::fake();

        ['user' => $user, 'token' => $token] = $this->createUserWithToken();
        $approver = User::factory()->approver()->create();
        $request = $this->createDraftRequestWithSteps($user, [$approver]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/requests/{$request->id}/submit");

        $response->assertOk()
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('approval_requests', [
            'id' => $request->id,
            'status' => 'pending',
            'current_step' => 1,
        ]);
    }

    public function test_submit_request_activates_first_approval_step(): void
    {
        Event::fake();

        ['user' => $user, 'token' => $token] = $this->createUserWithToken();
        $approver = User::factory()->approver()->create();
        $request = $this->createDraftRequestWithSteps($user, [$approver]);

        $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/requests/{$request->id}/submit");

        $this->assertDatabaseHas('approval_steps', [
            'request_id' => $request->id,
            'step_order' => 1,
            'status' => 'pending',
        ]);
    }

    public function test_user_cannot_submit_another_users_request(): void
    {
        ['token' => $token] = $this->createUserWithToken();
        $otherUser = User::factory()->create();
        $approver = User::factory()->approver()->create();
        $request = $this->createDraftRequestWithSteps($otherUser, [$approver]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/requests/{$request->id}/submit");

        $response->assertForbidden();
    }

    public function test_cannot_submit_already_pending_request(): void
    {
        ['user' => $user, 'token' => $token] = $this->createUserWithToken();
        $approver = User::factory()->approver()->create();
        $request = $this->createPendingRequestWithSteps($user, [$approver]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/requests/{$request->id}/submit");

        $response->assertStatus(409);
    }

    public function test_cannot_submit_request_without_approval_steps(): void
    {
        ['user' => $user, 'token' => $token] = $this->createUserWithToken();
        $request = $this->createRequest($user, null, 'draft');
        // No steps added

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/requests/{$request->id}/submit");

        $response->assertStatus(400); // Business error - no steps
    }

    public function test_submit_sets_submitted_at_timestamp(): void
    {
        Event::fake();

        ['user' => $user, 'token' => $token] = $this->createUserWithToken();
        $approver = User::factory()->approver()->create();
        $request = $this->createDraftRequestWithSteps($user, [$approver]);

        $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/requests/{$request->id}/submit");

        $request->refresh();
        $this->assertNotNull($request->submitted_at);
    }
}
