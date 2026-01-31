<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Approval\ApproveAction;
use App\Actions\Approval\RejectAction;
use App\Http\Requests\Approval\ApproveRequest;
use App\Http\Requests\Approval\RejectRequest;
use App\Http\Resources\ApprovalStepResource;
use App\Models\ApprovalStep;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = ApprovalStep::query()
            ->with(['request.template', 'request.requester', 'approver'])
            ->when(
                !$user->isAdmin(),
                fn ($q) => $q->where('approver_id', $user->id)
            )
            ->when(
                $request->status,
                fn ($q, $status) => $q->where('status', $status),
                fn ($q) => $q->pending() // 기본: pending만
            )
            ->latest();

        $steps = $request->per_page
            ? $query->paginate($request->per_page)
            : $query->get();

        if ($request->per_page) {
            return $this->paginated(ApprovalStepResource::collection($steps));
        }

        return $this->success(ApprovalStepResource::collection($steps));
    }

    public function show(ApprovalStep $step): JsonResponse
    {
        $this->authorize('view', $step);

        $step->load([
            'request.template',
            'request.requester.department',
            'request.steps.approver',
            'request.attachments',
            'approver',
        ]);

        return $this->success(new ApprovalStepResource($step));
    }

    public function approve(
        ApproveRequest $request,
        ApprovalStep $step,
        ApproveAction $action
    ): JsonResponse {
        $this->authorize('process', $step);

        $step = $action->execute($step, $request->validated('comment'));

        return $this->success(new ApprovalStepResource($step), '승인되었습니다.');
    }

    public function reject(
        RejectRequest $request,
        ApprovalStep $step,
        RejectAction $action
    ): JsonResponse {
        $this->authorize('process', $step);

        $step = $action->execute($step, $request->validated('comment'));

        return $this->success(new ApprovalStepResource($step), '반려되었습니다.');
    }
}
