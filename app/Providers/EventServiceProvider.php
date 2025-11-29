<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        \App\Events\OrderCreated::class => [
            \App\Listeners\SendOrderNotification::class,
            \App\Listeners\UpdateStock::class,
        ],
        \App\Events\OrderStatusUpdated::class => [
            \App\Listeners\SendOrderNotification::class,
        ],
        \App\Events\OrderCancelled::class => [
            \App\Listeners\UpdateStock::class,
            \App\Listeners\SendOrderNotification::class,
        ],
    ];

    public function boot()
    {
        //
    }
}