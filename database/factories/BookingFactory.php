<?php

namespace Database\Factories;

use App\Models\Schedule;
use App\Models\Booking;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Pick a schedule to book within
        $schedule = Schedule::inRandomOrder()->first() ?? Schedule::factory()->create();

        // Get all possible 30-min slots within the schedule
        $slots = [];
        $current = clone $schedule->start_time;
        while ($current < $schedule->end_time) {
            $end = (clone $current)->modify('+30 minutes');
            if ($end > $schedule->end_time) break;
            $slots[] = [$current, $end];
            $current = $end;
        }

        // Pick a slot not already booked
        $taken = Booking::where('schedule_id', $schedule->id)
            ->pluck('start_time')
            ->map(fn ($s) => (new \DateTime($s))->format('Y-m-d H:i:s'))
            ->toArray();

        $availableSlots = array_filter($slots, function ($slot) use ($taken) {
            return !in_array($slot[0]->format('Y-m-d H:i:s'), $taken);
        });

        if (empty($availableSlots)) {
            // no free slots left then just pick random
            [$start, $end] = $slots[array_rand($slots)];
        } else {
            [$start, $end] = $availableSlots[array_rand($availableSlots)];
        }

        $patientId = User::inRandomOrder()
            ->first()?->id 
            ?? User::factory()->randomRole();

        $status = $this->faker->randomElement([
            Booking::PENDING,
            Booking::CONFIRMED,
            Booking::CANCELLED,
        ]);

        return [
            'schedule_id' => $schedule->id,
            'patient_id'  => $patientId,
            'start_time'  => $start,
            'end_time'    => $end,
            'status'      => $status,
        ];
    }
}
