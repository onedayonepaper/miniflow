<?php

namespace App\Actions\Auth;

use App\Exceptions\ApiException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginAction
{
    public function execute(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ApiException::unauthorized('이메일 또는 비밀번호가 올바르지 않습니다.');
        }

        if ($user->trashed()) {
            throw ApiException::forbidden('비활성화된 계정입니다.');
        }

        $deviceName = $credentials['device_name'] ?? 'api-token';
        $token = $user->createToken($deviceName)->plainTextToken;

        return [
            'user' => $user->load('department'),
            'token' => $token,
        ];
    }
}
