<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class ScheduleController extends Controller
{

    /**
     * Public page for viewing schedules
     */
    public function index(): Response
    {
        $schedules = Schedule::with(['healthcare:id,name', 'bookings'])
            ->where('start_time', '>=', now())
            ->orderBy('start_time', 'asc')
            ->get()
            ->flatMap(function ($schedule) {
                // keep them as Carbon
                $bookedTimes = $schedule->bookings
                    ->pluck('start_time')
                    ->map(fn (Carbon $time) => $time->copy()->utc()->seconds(0)->micro(0));

                $slots = [];
                $current = $schedule->start_time->copy()->utc()->seconds(0)->micro(0);
                $endTime = $schedule->end_time->copy()->utc()->seconds(0)->micro(0);

                while ($current->lt($endTime)) {
                    $end = $current->copy()->addHour();

                    // compare as Carbon
                    if ($bookedTimes->contains(fn (Carbon $bt) => $bt->equalTo($current))) {
                        $current->addHour();
                        continue;
                    }

                    $slots[] = [
                        'id'          => $schedule->id . '-' . $current->format('H'),
                        'schedule_id' => $schedule->id,
                        'title'       => $schedule->healthcare->name,
                        'start'       => $current->toIso8601String(), // important for frontend
                        'end'         => $end->toIso8601String(),
                    ];

                    $current->addHour();
                }
                return $slots;
            });

        return Inertia::render('Schedule/Index', [
            'schedules' => $schedules
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'start'        => 'required|date',
            'end'          => 'required|date|after:start',
            'day_of_week'  => 'required|integer|min:0|max:6',
        ]);
        $validated['healthcare_id'] = auth()->id();

        $schedule = Schedule::create([
            'start_time'    => $validated['start'],
            'end_time'      => $validated['end'],
            'day_of_week'   => $validated['day_of_week'],
            'healthcare_id' => auth()->id(),
        ]);
        return response()->json([
            'message' => 'Schedule created successfully!',
            'schedule' => $schedule,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Schedule $schedule)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Schedule $schedule)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Schedule $schedule)
    {
        $this->authorize('update', $schedule);

        $validated = $request->validate([
            'start_time'        => 'required|date',
            'end_time'          => 'required|date|after:start',
            'day_of_week'       => 'required|integer|min:0|max:6',
        ]);

        $schedule->update($validated);
        return response()->json([
            'message' => 'Schedule updated successfully!',
            'schedule' => $schedule,
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $schedule = Schedule::findOrFail($id);

        $this->authorize('delete', $schedule);

        $schedule->delete();

        return response()->json([
            'message' => 'Schedule deleted successfully!',
        ], 201);   
    }
}
