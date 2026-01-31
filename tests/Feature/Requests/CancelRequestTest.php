<?php

namespace Tests\Feature\Requests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestData;

class CancelRequestTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    public function test_user_can_cancel_their_own_draft_request(): void
    {
        ['user' => $user, 'token' => $token] = $this->createUserWithToken();
        $request = $this->createRequest($user, null, 'draft');

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/requests/{$request->id}/cancel");

        $response->assertOk()
            ->assertJsonPath('data.status', 'canceled');

        $this->assertDatabaseHas('approval_requests', [
            'id' => $request->id,
            'status' => 'canceled',
        ]);
    }

    public function test_user_can_cancel_their_own_pending_request(): void
    {
        ['user' => $user, 'token' => $token] = $this->createUserWithToken();
        $approver = User::factory()->approver()->create();
        $request = $this->createPendingRequestWithSteps($user, [$approver]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/requests/{$request->id}/cancel");

        $response->assertOk()
            ->assertJsonPath('data.status', 'canceled');
    }

    public function test_user_cannot_cancel_another_users_request(): void
    {
        ['token' => $token] = $this->createUserWithToken();
        $otherUser = User::factory()->create();
        $request = $this->createRequest($otherUser, null, 'draft');

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/requests/{$request->id}/cancel");

        $response->assertForbidden();
    }

    public function test_cannot_cancel_already_approved_request(): void
    {
        ['user' => $user, 'token' => $token] = $this->createUserWithToken();
        $request = $this->createRequest($user, null, 'approved');

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/requests/{$request->id}/cancel");

        $response->assertStatus(409);
    }

    public function test_cannot_cancel_already_rejected_request(): void
    {
        ['user' => $user, 'token' => $token] = $this->createUserWithToken();
        $request = $this->createRequest($user, null, 'rejected');

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/requests/{$request->id}/cancel");

        $response->assertStatus(409);
    }

    public function test_cannot_cancel_already_canceled_request(): void
    {
        ['user' => $user, 'token' => $token] = $this->createUserWithToken();
        $request = $this->createRequest($user, null, 'canceled');

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/requests/{$request->id}/cancel");

        $response->assertStatus(409);
    }

    public function test_cancel_sets_completed_at_timestamp(): void
    {
        ['user' => $user, 'token' => $token] = $this->createUserWithToken();
        $request = $this->createRequest($user, null, 'draft');

        $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/requests/{$request->id}/cancel");

        $request->refresh();
        $this->assertNotNull($request->completed_at);
    }
}
