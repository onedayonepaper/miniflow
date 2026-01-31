<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Request\CancelRequestAction;
use App\Actions\Request\CreateRequestAction;
use App\Actions\Request\SubmitRequestAction;
use App\Actions\Request\UpdateRequestAction;
use App\Http\Requests\Request\StoreRequestRequest;
use App\Http\Requests\Request\UpdateRequestRequest;
use App\Http\Resources\ApprovalRequestResource;
use App\Models\ApprovalRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = ApprovalRequest::query()
            ->with(['template', 'requester', 'currentStep.approver'])
            ->when(
                !$user->isAdmin(),
                fn ($q) => $q->where(function ($q) use ($user) {
                    $q->where('requester_id', $user->id)
                      ->orWhereHas('steps', fn ($q) => $q->where('approver_id', $user->id));
                })
            )
            ->when($request->status, fn ($q, $status) => $q->ofStatus($status))
            ->when($request->template_id, fn ($q, $id) => $q->where('template_id', $id))
            ->latest();

        $requests = $request->per_page
            ? $query->paginate($request->per_page)
            : $query->get();

        if ($request->per_page) {
            return $this->paginated(ApprovalRequestResource::collection($requests));
        }

        return $this->success(ApprovalRequestResource::collection($requests));
    }

    public function store(StoreRequestRequest $request, CreateRequestAction $action): JsonResponse
    {
        $approvalRequest = $action->execute(
            $request->validated(),
            $request->user()
        );

        return $this->created(new ApprovalRequestResource($approvalRequest), '요청서가 생성되었습니다.');
    }

    public function show(ApprovalRequest $request): JsonResponse
    {
        $this->authorize('view', $request);

        $request->load(['template', 'requester.department', 'steps.approver', 'attachments.uploader']);

        return $this->success(new ApprovalRequestResource($request));
    }

    public function update(
        UpdateRequestRequest $request,
        ApprovalRequest $approvalRequest,
        UpdateRequestAction $action
    ): JsonResponse {
        $this->authorize('update', $approvalRequest);

        $approvalRequest = $action->execute($approvalRequest, $request->validated());

        return $this->success(new ApprovalRequestResource($approvalRequest), '요청서가 수정되었습니다.');
    }

    public function destroy(ApprovalRequest $request): JsonResponse
    {
        $this->authorize('delete', $request);

        $request->steps()->delete();
        $request->delete();

        return $this->success(message: '요청서가 삭제되었습니다.');
    }

    public function submit(ApprovalRequest $request, SubmitRequestAction $action): JsonResponse
    {
        $this->authorize('submit', $request);

        $request = $action->execute($request);

        return $this->success(new ApprovalRequestResource($request), '요청서가 제출되었습니다.');
    }

    public function cancel(ApprovalRequest $request, CancelRequestAction $action): JsonResponse
    {
        $this->authorize('cancel', $request);

        $request = $action->execute($request);

        return $this->success(new ApprovalRequestResource($request), '요청서가 취소되었습니다.');
    }
}
