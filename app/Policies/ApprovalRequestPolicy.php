<?php

namespace App\Policies;

use App\Models\ApprovalRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApprovalRequestPolicy
{
    use HandlesAuthorization;

    /**
     * 관리자는 모든 권한 부여
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * 목록 조회
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * 상세 조회
     */
    public function view(User $user, ApprovalRequest $request): bool
    {
        // 작성자
        if ($request->requester_id === $user->id) {
            return true;
        }

        // 승인자
        if ($request->steps()->where('approver_id', $user->id)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * 생성
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * 수정
     */
    public function update(User $user, ApprovalRequest $request): bool
    {
        // 작성자만 + draft 상태에서만
        return $request->requester_id === $user->id && $request->canEdit();
    }

    /**
     * 삭제
     */
    public function delete(User $user, ApprovalRequest $request): bool
    {
        // 작성자만 + draft 상태에서만
        return $request->requester_id === $user->id && $request->status === 'draft';
    }

    /**
     * 제출
     */
    public function submit(User $user, ApprovalRequest $request): bool
    {
        return $request->requester_id === $user->id && $request->canSubmit();
    }

    /**
     * 취소
     */
    public function cancel(User $user, ApprovalRequest $request): bool
    {
        return $request->requester_id === $user->id && $request->canCancel();
    }
}
