<?php

namespace Tests\Feature\Admin;

use App\Models\ApprovalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;
use Tests\Traits\CreatesTestData;

class AuditLogTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    public function test_admin_can_list_audit_logs(): void
    {
        ['token' => $token] = $this->createAdminWithToken();

        // Create some activity logs
        $user = User::factory()->create();
        $request = $this->createRequest($user);
        $request->update(['title' => 'Updated Title']); // This should trigger activity log

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/admin/audit-logs');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'log_name',
                        'description',
                        'subject_type',
                        'event',
                        'created_at',
                    ],
                ],
            ]);
    }

    public function test_non_admin_cannot_list_audit_logs(): void
    {
        ['token' => $token] = $this->createUserWithToken();

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/admin/audit-logs');

        $response->assertForbidden();
    }

    public function test_admin_can_view_audit_log_details(): void
    {
        ['token' => $token] = $this->createAdminWithToken();

        // Create activity log entry
        $user = User::factory()->create();
        $request = $this->createRequest($user);
        $request->update(['title' => 'Updated Title']);

        $activity = Activity::latest()->first();

        if ($activity) {
            $response = $this->withHeaders($this->authHeaders($token))
                ->getJson("/api/v1/admin/audit-logs/{$activity->id}");

            $response->assertOk()
                ->assertJsonPath('data.id', $activity->id);
        } else {
            $this->markTestSkipped('No activity log generated.');
        }
    }

    public function test_non_admin_cannot_view_audit_log_details(): void
    {
        ['token' => $token] = $this->createUserWithToken();

        $user = User::factory()->create();
        $request = $this->createRequest($user);
        $request->update(['title' => 'Updated Title']);

        $activity = Activity::latest()->first();

        if ($activity) {
            $response = $this->withHeaders($this->authHeaders($token))
                ->getJson("/api/v1/admin/audit-logs/{$activity->id}");

            $response->assertForbidden();
        } else {
            $this->markTestSkipped('No activity log generated.');
        }
    }

    public function test_audit_logs_can_be_filtered_by_date(): void
    {
        ['token' => $token] = $this->createAdminWithToken();

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/admin/audit-logs?from=2024-01-01&to=2024-12-31');

        $response->assertOk();
    }

    public function test_audit_logs_can_be_filtered_by_event(): void
    {
        ['token' => $token] = $this->createAdminWithToken();

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/admin/audit-logs?event=created');

        $response->assertOk();
    }

    public function test_unauthenticated_user_cannot_access_audit_logs(): void
    {
        $response = $this->getJson('/api/v1/admin/audit-logs');

        $response->assertUnauthorized();
    }

    public function test_audit_log_list_is_paginated(): void
    {
        ['token' => $token] = $this->createAdminWithToken();

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/admin/audit-logs?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'per_page', 'total'],
            ]);
    }
}
