<?php

namespace App\Providers;

use App\Events\ApprovalRequestSubmitted;
use App\Events\ApprovalStepProcessed;
use App\Listeners\SendApprovalNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        ApprovalRequestSubmitted::class => [
            [SendApprovalNotification::class, 'handleSubmitted'],
        ],
        ApprovalStepProcessed::class => [
            [SendApprovalNotification::class, 'handleProcessed'],
        ],
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array<int, class-string>
     */
    protected $subscribe = [
        SendApprovalNotification::class,
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
