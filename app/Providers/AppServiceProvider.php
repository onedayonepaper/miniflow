<?php

namespace App\Providers;

use App\Models\ApprovalRequest;
use App\Models\ApprovalStep;
use App\Policies\ApprovalRequestPolicy;
use App\Policies\ApprovalStepPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Policy 등록
        Gate::policy(ApprovalRequest::class, ApprovalRequestPolicy::class);
        Gate::policy(ApprovalStep::class, ApprovalStepPolicy::class);

        // Admin Gate 정의
        Gate::define('admin', function ($user) {
            return $user->isAdmin();
        });

        // Rate Limiter 정의
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // 기본 API Rate Limiter (인증 사용자: 60/분, 비인증: 10/분)
        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(60)->by($request->user()->id)
                : Limit::perMinute(10)->by($request->ip());
        });

        // Auth 전용 Rate Limiter (5회/분 - 브루트포스 방지)
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Admin 전용 Rate Limiter (더 높은 제한)
        RateLimiter::for('admin', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(120)->by($request->user()->id)
                : Limit::perMinute(10)->by($request->ip());
        });
    }
}
