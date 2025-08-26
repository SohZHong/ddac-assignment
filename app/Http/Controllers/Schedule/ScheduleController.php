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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'start'        => 'required|date|after:now',
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

    public function update(Request $request, Schedule $schedule)
    {
        $this->authorize('update', $schedule);

        $validated = $request->validate([
            'start_time'        => 'required|date|after:now',
            'end_time'          => 'required|date|after:start',
            'day_of_week'       => 'required|integer|min:0|max:6',
        ]);

        $schedule->update($validated);
        return response()->json([
            'message' => 'Schedule updated successfully!',
            'schedule' => $schedule,
        ], 201);
    }

    public function destroy(string $id)
    {
        $schedule = Schedule::findOrFail($id);

        $this->authorize('delete', $schedule);

        $schedule->delete();

        return response()->json([
            'message' => 'Schedule deleted successfully!',
        ], 201);   
    }

    public function getByHealthcare($healthcareId)
    {
        $schedules = Schedule::with(['healthcare:id,name'])
            ->where('healthcare_id', $healthcareId)
            ->get()
            ->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'healthcare_id' => $schedule->healthcare_id,
                    'day_of_week' => $schedule->day_of_week,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'healthcare' => [
                        'id' => $schedule->healthcare->id,
                        'name' => $schedule->healthcare->name,
                    ],
                ];
            });

        return response()->json($schedules);
    }
}
