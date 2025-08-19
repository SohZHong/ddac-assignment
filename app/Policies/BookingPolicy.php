<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;
use Carbon\Carbon;

class BookingPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Booking $booking): bool
    {
        return false;
    }

    /**
     * Determine if the user can create a booking for a given schedule.
     */
    public function store(User $user, Schedule $schedule, string $startTime)
    {
        // Patient can create booking if not healthcare owner and hasn’t booked same schedule already
        if ($schedule->healthcare_id === $user->id) {
            return $this->deny('You cannot book your own schedule.');
        }

            $requestDate = Carbon::parse($startTime)->toDateString();

        // Prevent double booking for today
        $alreadyBooked = $schedule->bookings()
            ->where('patient_id', $user->id)
            ->whereDate('start_time', $requestDate)
            ->exists();

        if ($alreadyBooked) {
            return $this->deny('You already booked this schedule today.');
        }

        return $this->allow();    
    }

    /**
     * Determine if the user can update a booking.
     */
    public function update(User $user, Booking $booking): bool
    {
        // Only allow the owner of the booking to update/cancel it
        return $booking->patient_id === $user->id;
    }

    /**
     * Determine if the user can update the booking status.
     */
    public function review(User $user, Booking $booking): bool
    {
        // Only the healthcare professional who owns the schedule can manage the booking
        return $booking->healthcare->id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Booking $booking): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Booking $booking): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Booking $booking): bool
    {
        return false;
    }
}
