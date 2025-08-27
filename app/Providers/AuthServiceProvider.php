<?php

namespace App\Providers;

use App\Models\User;
use App\Models\EventRegistration;
use App\Models\EventAttendance;
use App\Policies\EventRegistrationPolicy;
use App\Policies\EventAttendancePolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        EventRegistration::class => EventRegistrationPolicy::class,
        EventAttendance::class => EventAttendancePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
