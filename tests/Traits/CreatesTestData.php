<?php

namespace Tests\Traits;

use App\Models\ApprovalRequest;
use App\Models\ApprovalStep;
use App\Models\Department;
use App\Models\RequestTemplate;
use App\Models\User;

trait CreatesTestData
{
    /**
     * Create a user and generate an API token.
     */
    protected function createUserWithToken(string $role = 'user'): array
    {
        $user = User::factory()->create(['role' => $role]);
        $token = $user->createToken('test-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Create an admin user with token.
     */
    protected function createAdminWithToken(): array
    {
        return $this->createUserWithToken('admin');
    }

    /**
     * Create an approver user with token.
     */
    protected function createApproverWithToken(): array
    {
        return $this->createUserWithToken('approver');
    }

    /**
     * Create a department.
     */
    protected function createDepartment(?User $manager = null): Department
    {
        return Department::factory()
            ->when($manager, fn ($factory) => $factory->withManager($manager))
            ->create();
    }

    /**
     * Create a request template.
     */
    protected function createTemplate(?User $creator = null): RequestTemplate
    {
        return RequestTemplate::factory()
            ->when($creator, fn ($factory) => $factory->createdBy($creator))
            ->create();
    }

    /**
     * Create an approval request.
     */
    protected function createRequest(
        User $requester,
        ?RequestTemplate $template = null,
        string $status = 'draft'
    ): ApprovalRequest {
        $template = $template ?? $this->createTemplate();

        return ApprovalRequest::factory()
            ->forTemplate($template)
            ->byRequester($requester)
            ->state(['status' => $status])
            ->create();
    }

    /**
     * Create a pending request with approval steps.
     */
    protected function createPendingRequestWithSteps(
        User $requester,
        array $approvers,
        ?RequestTemplate $template = null
    ): ApprovalRequest {
        $template = $template ?? $this->createTemplate();

        $request = ApprovalRequest::factory()
            ->forTemplate($template)
            ->byRequester($requester)
            ->pending()
            ->create([
                'total_steps' => count($approvers),
                'current_step' => 1,
            ]);

        foreach ($approvers as $index => $approver) {
            ApprovalStep::factory()
                ->forRequest($request)
                ->byApprover($approver)
                ->stepOrder($index + 1)
                ->state([
                    'status' => $index === 0 ? 'pending' : 'waiting',
                ])
                ->create();
        }

        return $request->fresh(['steps', 'template', 'requester']);
    }

    /**
     * Create a draft request with approval steps.
     */
    protected function createDraftRequestWithSteps(
        User $requester,
        array $approvers,
        ?RequestTemplate $template = null
    ): ApprovalRequest {
        $template = $template ?? $this->createTemplate();

        $request = ApprovalRequest::factory()
            ->forTemplate($template)
            ->byRequester($requester)
            ->draft()
            ->create([
                'total_steps' => count($approvers),
            ]);

        foreach ($approvers as $index => $approver) {
            ApprovalStep::factory()
                ->forRequest($request)
                ->byApprover($approver)
                ->stepOrder($index + 1)
                ->waiting()
                ->create();
        }

        return $request->fresh(['steps', 'template', 'requester']);
    }

    /**
     * Create an approval step for a request.
     */
    protected function createApprovalStep(
        ApprovalRequest $request,
        User $approver,
        int $stepOrder = 1,
        string $status = 'waiting'
    ): ApprovalStep {
        return ApprovalStep::factory()
            ->forRequest($request)
            ->byApprover($approver)
            ->stepOrder($stepOrder)
            ->state(['status' => $status])
            ->create();
    }

    /**
     * Get auth headers for a user.
     */
    protected function authHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];
    }
}
