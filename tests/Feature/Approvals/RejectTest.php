<?php

namespace Tests\Feature\Approvals;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\CreatesTestData;

class RejectTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    public function test_approver_can_reject_pending_step(): void
    {
        Event::fake();

        ['user' => $approver, 'token' => $token] = $this->createApproverWithToken();
        $requester = User::factory()->create();
        $request = $this->createPendingRequestWithSteps($requester, [$approver]);
        $step = $request->steps->first();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/approvals/{$step->id}/reject", [
                'comment' => '반려 사유입니다.',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        $this->assertDatabaseHas('approval_steps', [
            'id' => $step->id,
            'status' => 'rejected',
            'comment' => '반려 사유입니다.',
        ]);
    }

    public function test_reject_requires_comment(): void
    {
        ['user' => $approver, 'token' => $token] = $this->createApproverWithToken();
        $requester = User::factory()->create();
        $request = $this->createPendingRequestWithSteps($requester, [$approver]);
        $step = $request->steps->first();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/approvals/{$step->id}/reject");

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['comment']);
    }

    public function test_rejection_rejects_entire_request(): void
    {
        Event::fake();

        ['user' => $approver, 'token' => $token] = $this->createApproverWithToken();
        $requester = User::factory()->create();
        $request = $this->createPendingRequestWithSteps($requester, [$approver]);
        $step = $request->steps->first();

        $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/approvals/{$step->id}/reject", [
                'comment' => '반려합니다.',
            ]);

        $this->assertDatabaseHas('approval_requests', [
            'id' => $request->id,
            'status' => 'rejected',
        ]);
    }

    public function test_rejection_skips_remaining_steps(): void
    {
        Event::fake();

        ['user' => $approver1, 'token' => $token1] = $this->createApproverWithToken();
        $approver2 = User::factory()->approver()->create();
        $requester = User::factory()->create();
        $request = $this->createPendingRequestWithSteps($requester, [$approver1, $approver2]);
        $step1 = $request->steps->where('step_order', 1)->first();

        $this->withHeaders($this->authHeaders($token1))
            ->postJson("/api/v1/approvals/{$step1->id}/reject", [
                'comment' => '반려합니다.',
            ]);

        // Check second step is skipped
        $this->assertDatabaseHas('approval_steps', [
            'request_id' => $request->id,
            'step_order' => 2,
            'status' => 'skipped',
        ]);
    }

    public function test_non_approver_cannot_reject(): void
    {
        ['user' => $approver] = $this->createApproverWithToken();
        ['token' => $otherToken] = $this->createUserWithToken();
        $requester = User::factory()->create();
        $request = $this->createPendingRequestWithSteps($requester, [$approver]);
        $step = $request->steps->first();

        $response = $this->withHeaders($this->authHeaders($otherToken))
            ->postJson("/api/v1/approvals/{$step->id}/reject", [
                'comment' => '반려합니다.',
            ]);

        $response->assertForbidden();
    }

    public function test_cannot_reject_already_processed_step(): void
    {
        Event::fake();

        ['user' => $approver, 'token' => $token] = $this->createApproverWithToken();
        $requester = User::factory()->create();
        $request = $this->createPendingRequestWithSteps($requester, [$approver]);
        $step = $request->steps->first();

        // First rejection
        $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/approvals/{$step->id}/reject", [
                'comment' => '반려합니다.',
            ]);

        // Try to reject again
        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/approvals/{$step->id}/reject", [
                'comment' => '다시 반려합니다.',
            ]);

        $response->assertStatus(409);
    }

    public function test_rejection_sets_completed_at_on_request(): void
    {
        Event::fake();

        ['user' => $approver, 'token' => $token] = $this->createApproverWithToken();
        $requester = User::factory()->create();
        $request = $this->createPendingRequestWithSteps($requester, [$approver]);
        $step = $request->steps->first();

        $this->withHeaders($this->authHeaders($token))
            ->postJson("/api/v1/approvals/{$step->id}/reject", [
                'comment' => '반려합니다.',
            ]);

        $request->refresh();
        $this->assertNotNull($request->completed_at);
    }
}
