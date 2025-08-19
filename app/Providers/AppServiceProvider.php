<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{

    protected $policies = [
        \App\Models\Blog::class => \App\Policies\BlogPolicy::class,
        \App\Models\Schedule::class => \App\Policies\SchedulePolicy::class,
        \App\Models\Booking::class => \App\Policies\BookingPolicy::class,
        \App\Models\Quiz::class => \App\Policies\QuizPolicy::class,
    ];

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
        //
    }
}
