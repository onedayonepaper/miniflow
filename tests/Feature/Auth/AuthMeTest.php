<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestData;

class AuthMeTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    public function test_authenticated_user_can_get_their_info(): void
    {
        ['user' => $user, 'token' => $token] = $this->createUserWithToken();

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'role',
                ],
            ])
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_unauthenticated_user_cannot_get_info(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertUnauthorized();
    }

    public function test_admin_user_info_shows_admin_role(): void
    {
        ['user' => $user, 'token' => $token] = $this->createAdminWithToken();

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('data.role', 'admin');
    }

    public function test_user_info_includes_department_when_assigned(): void
    {
        ['user' => $user, 'token' => $token] = $this->createUserWithToken();
        $department = $this->createDepartment();
        $user->update(['department_id' => $department->id]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('data.department.id', $department->id);
    }
}
