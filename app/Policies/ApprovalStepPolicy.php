<?php

namespace App\Policies;

use App\Models\ApprovalStep;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApprovalStepPolicy
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
    public function view(User $user, ApprovalStep $step): bool
    {
        // 결재자
        if ($step->approver_id === $user->id) {
            return true;
        }

        // 요청서 작성자
        if ($step->request->requester_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * 결재 처리 (승인/반려)
     */
    public function process(User $user, ApprovalStep $step): bool
    {
        // 본인 결재 단계이고 처리 가능 상태
        return $step->approver_id === $user->id && $step->canProcess();
    }
}
