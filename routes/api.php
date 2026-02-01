<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TemplateController;
use App\Http\Controllers\Api\V1\RequestController;
use App\Http\Controllers\Api\V1\ApprovalController;
use App\Http\Controllers\Api\V1\AttachmentController;
use App\Http\Controllers\Api\V1\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\V1\Admin\AuditLogController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| MiniFlow 신청/승인 시스템 API v1
|
*/

// Health Check Endpoint (public)
Route::get('health', function () {
    $services = [];

    // Database Check
    try {
        DB::connection()->getPdo();
        $services['database'] = 'ok';
    } catch (\Exception $e) {
        $services['database'] = 'error';
    }

    // Cache Check
    try {
        Cache::store()->put('health_check', true, 10);
        $services['cache'] = Cache::store()->get('health_check') ? 'ok' : 'error';
    } catch (\Exception $e) {
        $services['cache'] = 'error';
    }

    // Queue Check
    try {
        $services['queue'] = Queue::size('default') >= 0 ? 'ok' : 'error';
    } catch (\Exception $e) {
        $services['queue'] = 'error';
    }

    $allHealthy = !in_array('error', array_values($services));

    return response()->json([
        'status' => $allHealthy ? 'healthy' : 'degraded',
        'timestamp' => now()->toIso8601String(),
        'version' => config('app.version', '1.0.0'),
        'services' => $services,
    ], $allHealthy ? 200 : 503);
});

Route::prefix('v1')->group(function () {
    // ===== Auth =====
    Route::prefix('auth')->group(function () {
        // 로그인에는 auth rate limiter 적용 (브루트포스 방지)
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:auth');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    // ===== Protected Routes =====
    Route::middleware('auth:sanctum')->group(function () {
        // Templates (양식)
        Route::get('templates', [TemplateController::class, 'index']);
        Route::get('templates/{template}', [TemplateController::class, 'show']);

        // Requests (요청서)
        Route::apiResource('requests', RequestController::class);
        Route::post('requests/{request}/submit', [RequestController::class, 'submit']);
        Route::post('requests/{request}/cancel', [RequestController::class, 'cancel']);

        // Attachments (첨부파일)
        Route::post('requests/{request}/attachments', [AttachmentController::class, 'store']);
        Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download'])
            ->name('api.v1.attachments.download');
        Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy']);

        // Approvals (승인)
        Route::get('approvals', [ApprovalController::class, 'index']);
        Route::get('approvals/{step}', [ApprovalController::class, 'show']);
        Route::post('approvals/{step}/approve', [ApprovalController::class, 'approve']);
        Route::post('approvals/{step}/reject', [ApprovalController::class, 'reject']);

        // ===== Admin Routes =====
        Route::prefix('admin')->middleware('can:admin')->group(function () {
            Route::get('users', [AdminUserController::class, 'index']);
            Route::get('users/{user}', [AdminUserController::class, 'show']);

            Route::get('audit-logs', [AuditLogController::class, 'index']);
            Route::get('audit-logs/{activity}', [AuditLogController::class, 'show']);
        });
    });
});
