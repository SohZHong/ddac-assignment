<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BookingPolicy
{
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
    public function store(User $user, Schedule $schedule): bool
    {
        // Patient can create booking if not healthcare owner and hasn’t booked same schedule already
        if ($schedule->healthcare_id === $user->id) return false;

        $alreadyBooked = $schedule->bookings()
            ->where('patient_id', $user->id)
            ->whereDate('start_time', now()->toDateString())
            ->exists();

        return !$alreadyBooked;
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
    public function reviewBooking(User $user, Booking $booking): bool
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
