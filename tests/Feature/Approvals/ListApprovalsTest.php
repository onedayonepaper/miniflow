<?php

namespace Tests\Feature\Approvals;

use App\Models\ApprovalStep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestData;

class ListApprovalsTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    public function test_user_can_list_their_pending_approvals(): void
    {
        ['user' => $approver, 'token' => $token] = $this->createApproverWithToken();
        $requester = User::factory()->create();
        $request = $this->createPendingRequestWithSteps($requester, [$approver]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/approvals');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'step_order',
                        'type',
                        'status',
                        'request',
                    ],
                ],
            ]);
    }

    public function test_user_only_sees_their_own_approvals(): void
    {
        ['user' => $approver1, 'token' => $token1] = $this->createApproverWithToken();
        ['user' => $approver2] = $this->createApproverWithToken();
        $requester = User::factory()->create();

        // Create request with approver1 as the approver
        $this->createPendingRequestWithSteps($requester, [$approver1]);
        // Create request with approver2 as the approver
        $this->createPendingRequestWithSteps($requester, [$approver2]);

        $response = $this->withHeaders($this->authHeaders($token1))
            ->getJson('/api/v1/approvals');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_approval_list_includes_request_details(): void
    {
        ['user' => $approver, 'token' => $token] = $this->createApproverWithToken();
        $requester = User::factory()->create();
        $request = $this->createPendingRequestWithSteps($requester, [$approver]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/approvals');

        $response->assertOk()
            ->assertJsonPath('data.0.request.id', $request->id)
            ->assertJsonPath('data.0.request.title', $request->title);
    }

    public function test_unauthenticated_user_cannot_list_approvals(): void
    {
        $response = $this->getJson('/api/v1/approvals');

        $response->assertUnauthorized();
    }

    public function test_can_filter_approvals_by_status(): void
    {
        ['user' => $approver, 'token' => $token] = $this->createApproverWithToken();
        $requester = User::factory()->create();

        // Create pending approval
        $this->createPendingRequestWithSteps($requester, [$approver]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/approvals?status=pending');

        $response->assertOk();
    }

    public function test_empty_list_when_no_pending_approvals(): void
    {
        ['token' => $token] = $this->createApproverWithToken();

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/approvals');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
