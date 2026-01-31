<?php

namespace Tests\Feature\Approvals;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\CreatesTestData;

class ApproveTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    public function test_approver_can_approve_pending_step(): void
    {
        Event::fake();

        ['user' => $approver, 'token' => $token] = $this->createApproverWithToken();
        $requester = User::factory()->create();
        $request = $this->createPendingRequestWithSteps($requester, [$approver]);
        $step = $request->steps->first();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/approvals/{$step->id}/approve", [
                'comment' => '승인합니다.',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('approval_steps', [
            'id' => $step->id,
            'status' => 'approved',
            'comment' => '승인합니다.',
        ]);
    }

    public function test_single_step_approval_completes_request(): void
    {
        Event::fake();

        ['user' => $approver, 'token' => $token] = $this->createApproverWithToken();
        $requester = User::factory()->create();
        $request = $this->createPendingRequestWithSteps($requester, [$approver]);
        $step = $request->steps->first();

        $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/approvals/{$step->id}/approve");

        $this->assertDatabaseHas('approval_requests', [
            'id' => $request->id,
            'status' => 'approved',
        ]);
    }

    public function test_multi_step_approval_activates_next_step(): void
    {
        Event::fake();

        ['user' => $approver1, 'token' => $token1] = $this->createApproverWithToken();
        $approver2 = User::factory()->approver()->create();
        $requester = User::factory()->create();
        $request = $this->createPendingRequestWithSteps($requester, [$approver1, $approver2]);
        $step1 = $request->steps->where('step_order', 1)->first();

        $this->withHeaders($this->authHeaders($token1))
            ->postJson("/api/v1/approvals/{$step1->id}/approve");

        // Check first step is approved
        $this->assertDatabaseHas('approval_steps', [
            'id' => $step1->id,
            'status' => 'approved',
        ]);

        // Check second step is now pending
        $this->assertDatabaseHas('approval_steps', [
            'request_id' => $request->id,
            'step_order' => 2,
            'status' => 'pending',
        ]);

        // Request should still be pending
        $this->assertDatabaseHas('approval_requests', [
            'id' => $request->id,
            'status' => 'pending',
            'current_step' => 2,
        ]);
    }

    public function test_non_approver_cannot_approve(): void
    {
        ['user' => $approver] = $this->createApproverWithToken();
        ['token' => $otherToken] = $this->createUserWithToken(); // Regular user
        $requester = User::factory()->create();
        $request = $this->createPendingRequestWithSteps($requester, [$approver]);
        $step = $request->steps->first();

        $response = $this->withHeaders($this->authHeaders($otherToken))
            ->postJson("/api/v1/approvals/{$step->id}/approve");

        $response->assertForbidden();
    }

    public function test_cannot_approve_waiting_step(): void
    {
        ['user' => $approver1, 'token' => $token1] = $this->createApproverWithToken();
        ['user' => $approver2, 'token' => $token2] = $this->createApproverWithToken();
        $requester = User::factory()->create();
        $request = $this->createPendingRequestWithSteps($requester, [$approver1, $approver2]);
        $step2 = $request->steps->where('step_order', 2)->first();

        // Try to approve step 2 before step 1 is approved
        $response = $this->withHeaders($this->authHeaders($token2))
            ->postJson("/api/v1/approvals/{$step2->id}/approve");

        $response->assertStatus(409);
    }

    public function test_approve_without_comment_is_allowed(): void
    {
        Event::fake();

        ['user' => $approver, 'token' => $token] = $this->createApproverWithToken();
        $requester = User::factory()->create();
        $request = $this->createPendingRequestWithSteps($requester, [$approver]);
        $step = $request->steps->first();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/approvals/{$step->id}/approve");

        $response->assertOk();
    }
}
