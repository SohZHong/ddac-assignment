<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;

class HealthcareDashboardController extends Controller
{
    public function index(): Response
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

    public function appointment(): Response
    {
        $bookings = Booking::with('patient:id,name,email')
            ->orderBy('start_time', 'asc')
            ->get()
            ->map(fn ($booking) => [
                'id'          => $booking->id,
                'schedule_id' => $booking->schedule_id,
                'patient'     => [
                    'id'    => $booking->patient->id,
                    'name'  => $booking->patient->name,
                    'email' => $booking->patient->email,
                ],
                'start_time'  => $booking->start_time,
                'end_time'    => $booking->end_time,
                'status'      => $booking->status,
            ]);

        return Inertia::render('Healthcare/Booking', [
            'bookings' => $bookings
        ]);
    }
}
