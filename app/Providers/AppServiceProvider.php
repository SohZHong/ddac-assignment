<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use App\Services\AwsNotificationService;
use App\Notifications\Channels\SnsChannel;

class AppServiceProvider extends ServiceProvider
{

    protected $policies = [
        \App\Models\Blog::class => \App\Policies\BlogPolicy::class,
        \App\Models\Schedule::class => \App\Policies\SchedulePolicy::class,
        \App\Models\Booking::class => \App\Policies\BookingPolicy::class,
        \App\Models\Quiz::class => \App\Policies\QuizPolicy::class,
        \App\Models\ConsultationReport::class => \App\Policies\ConsultationReportPolicy::class,
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
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        $this->app->singleton(SnsChannel::class, function ($app) {
            return new SnsChannel($app->make(AwsNotificationService::class));
        });
    }
}
