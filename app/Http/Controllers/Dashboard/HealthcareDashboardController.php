<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;

class HealthcareDashboardController extends Controller
{
    public function index(): \Inertia\Response
    {
        $schedules = Schedule::where('healthcare_id', auth()->id())
                ->where('start_time', '>=', now())
                ->orderBy('start_time', 'asc')
                ->get()
                ->map(fn ($schedule) => [
                'id'           => $schedule->id,
                'start'        => $schedule->start_time,
                'end'          => $schedule->end_time,
                'day_of_week'  => $schedule->day_of_week,
            ]);

        return Inertia::render('Healthcare/Dashboard', [
            'profile' => auth()->user()->only(['id','name','email']),
            'schedules' => $schedules,
            // 'bookings' => Booking::where('healthcare_id', auth()->id())
            //     ->with('patient:id,name')
            //     ->orderBy('start_time', 'asc')
            //     ->get(),
        ]);
    }
}
