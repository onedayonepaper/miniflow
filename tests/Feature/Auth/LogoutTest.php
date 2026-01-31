<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestData;

class LogoutTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    public function test_authenticated_user_can_logout(): void
    {
        ['user' => $user, 'token' => $token] = $this->createUserWithToken();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/auth/logout');

        $response->assertOk()
            ->assertJson([
                'message' => '로그아웃 되었습니다.',
            ]);

        // Verify token is invalidated
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_logout(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertUnauthorized();
    }

    public function test_logout_with_invalid_token(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
            'Accept' => 'application/json',
        ])->postJson('/api/v1/auth/logout');

        $response->assertUnauthorized();
    }
}
