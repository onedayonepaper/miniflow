<?php

namespace App\Actions\Auth;

use App\Models\User;

class LogoutAction
{
    public function execute(User $user): void
    {
        // 현재 토큰만 삭제
        $user->currentAccessToken()->delete();
    }

    public function executeAll(User $user): void
    {
        // 모든 토큰 삭제
        $user->tokens()->delete();
    }
}
