<?php

namespace Tests\Feature\Requests;

use App\Models\RequestTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestData;

class CreateRequestTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    public function test_authenticated_user_can_create_request(): void
    {
        ['user' => $user, 'token' => $token] = $this->createUserWithToken();
        $template = $this->createTemplate();
        $approver = User::factory()->approver()->create();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/requests', [
                'template_id' => $template->id,
                'title' => '테스트 신청',
                'content' => [
                    'reason' => '테스트 내용',
                    'start_date' => '2024-01-15',
                    'end_date' => '2024-01-17',
                ],
                'urgency' => 'normal',
                'steps' => [
                    ['approver_id' => $approver->id, 'type' => 'approve'],
                ],
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'status',
                    'requester',
                    'template',
                    'steps',
                ],
            ])
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.title', '테스트 신청');
    }

    public function test_unauthenticated_user_cannot_create_request(): void
    {
        $template = $this->createTemplate();

        $response = $this->postJson('/api/v1/requests', [
            'template_id' => $template->id,
            'title' => '테스트 신청',
        ]);

        $response->assertUnauthorized();
    }

    public function test_create_request_requires_template_id(): void
    {
        ['token' => $token] = $this->createUserWithToken();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/requests', [
                'title' => '테스트 신청',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['template_id']);
    }

    public function test_create_request_requires_title(): void
    {
        ['token' => $token] = $this->createUserWithToken();
        $template = $this->createTemplate();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/requests', [
                'template_id' => $template->id,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_create_request_validates_template_exists(): void
    {
        ['token' => $token] = $this->createUserWithToken();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/requests', [
                'template_id' => 999,
                'title' => '테스트 신청',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['template_id']);
    }

    public function test_create_request_with_multiple_approval_steps(): void
    {
        ['user' => $user, 'token' => $token] = $this->createUserWithToken();
        $template = $this->createTemplate();
        $approver1 = User::factory()->approver()->create();
        $approver2 = User::factory()->approver()->create();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/requests', [
                'template_id' => $template->id,
                'title' => '테스트 신청',
                'content' => ['reason' => '테스트 내용'],
                'steps' => [
                    ['approver_id' => $approver1->id, 'type' => 'approve'],
                    ['approver_id' => $approver2->id, 'type' => 'approve'],
                ],
            ]);

        $response->assertCreated()
            ->assertJsonCount(2, 'data.steps');
    }

    public function test_created_request_has_draft_status(): void
    {
        ['user' => $user, 'token' => $token] = $this->createUserWithToken();
        $template = $this->createTemplate();
        $approver = User::factory()->approver()->create();

        $response = $this->withHeaders($this->authHeaders($token))
            ->postJson('/api/v1/requests', [
                'template_id' => $template->id,
                'title' => '테스트 신청',
                'steps' => [
                    ['approver_id' => $approver->id, 'type' => 'approve'],
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('approval_requests', [
            'title' => '테스트 신청',
            'status' => 'draft',
            'requester_id' => $user->id,
        ]);
    }
}
