<?php

namespace Tests\Feature\Requests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestData;

class UpdateRequestTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    public function test_user_can_update_their_own_draft_request(): void
    {
        ['user' => $user, 'token' => $token] = $this->createUserWithToken();
        $request = $this->createRequest($user, null, 'draft');

        $response = $this->withHeaders($this->authHeaders($token))
            ->putJson("/api/v1/requests/{$request->id}", [
                'title' => '수정된 제목',
                'content' => ['reason' => '수정된 사유'],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.title', '수정된 제목');

        $this->assertDatabaseHas('approval_requests', [
            'id' => $request->id,
            'title' => '수정된 제목',
        ]);
    }

    public function test_user_cannot_update_another_users_request(): void
    {
        ['token' => $token] = $this->createUserWithToken();
        $otherUser = User::factory()->create();
        $request = $this->createRequest($otherUser, null, 'draft');

        $response = $this->withHeaders($this->authHeaders($token))
            ->putJson("/api/v1/requests/{$request->id}", [
                'title' => '수정된 제목',
            ]);

        $response->assertForbidden();
    }

    public function test_user_cannot_update_pending_request(): void
    {
        ['user' => $user, 'token' => $token] = $this->createUserWithToken();
        $request = $this->createRequest($user, null, 'pending');

        $response = $this->withHeaders($this->authHeaders($token))
            ->putJson("/api/v1/requests/{$request->id}", [
                'title' => '수정된 제목',
            ]);

        $response->assertStatus(409); // Conflict - cannot edit non-draft request
    }

    public function test_user_cannot_update_approved_request(): void
    {
        ['user' => $user, 'token' => $token] = $this->createUserWithToken();
        $request = $this->createRequest($user, null, 'approved');

        $response = $this->withHeaders($this->authHeaders($token))
            ->putJson("/api/v1/requests/{$request->id}", [
                'title' => '수정된 제목',
            ]);

        $response->assertStatus(409);
    }

    public function test_update_request_validates_title(): void
    {
        ['user' => $user, 'token' => $token] = $this->createUserWithToken();
        $request = $this->createRequest($user, null, 'draft');

        $response = $this->withHeaders($this->authHeaders($token))
            ->putJson("/api/v1/requests/{$request->id}", [
                'title' => '', // Empty title
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_unauthenticated_user_cannot_update_request(): void
    {
        $user = User::factory()->create();
        $request = $this->createRequest($user, null, 'draft');

        $response = $this->putJson("/api/v1/requests/{$request->id}", [
            'title' => '수정된 제목',
        ]);

        $response->assertUnauthorized();
    }
}
