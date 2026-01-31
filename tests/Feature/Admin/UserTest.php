<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestData;

class UserTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    public function test_admin_can_list_users(): void
    {
        ['token' => $token] = $this->createAdminWithToken();
        User::factory()->count(5)->create();

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/admin/users');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'role',
                    ],
                ],
            ]);
    }

    public function test_non_admin_cannot_list_users(): void
    {
        ['token' => $token] = $this->createUserWithToken();

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/admin/users');

        $response->assertForbidden();
    }

    public function test_approver_cannot_list_users(): void
    {
        ['token' => $token] = $this->createApproverWithToken();

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/admin/users');

        $response->assertForbidden();
    }

    public function test_admin_can_view_user_details(): void
    {
        ['token' => $token] = $this->createAdminWithToken();
        $user = User::factory()->create();

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson("/api/v1/admin/users/{$user->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_non_admin_cannot_view_user_details(): void
    {
        ['token' => $token] = $this->createUserWithToken();
        $user = User::factory()->create();

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson("/api/v1/admin/users/{$user->id}");

        $response->assertForbidden();
    }

    public function test_admin_viewing_nonexistent_user_returns_404(): void
    {
        ['token' => $token] = $this->createAdminWithToken();

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/admin/users/999');

        $response->assertNotFound();
    }

    public function test_unauthenticated_user_cannot_access_admin_routes(): void
    {
        $response = $this->getJson('/api/v1/admin/users');

        $response->assertUnauthorized();
    }

    public function test_user_list_is_paginated(): void
    {
        ['token' => $token] = $this->createAdminWithToken();
        User::factory()->count(25)->create();

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/admin/users?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'per_page', 'total'],
            ]);
    }
}
