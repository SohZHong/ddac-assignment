<?php

namespace App\Http\Controllers\Healthcare;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PatientController extends Controller
{
    public function getLatestBooking(string $patientId)
    {
        $booking = Booking::with(['schedule.healthcare', 'patient'])
            ->where('patient_id', $patientId)
            ->where('status', Booking::CONFIRMED)
            ->whereHas('schedule', function ($query) {
                $query->where('healthcare_id', Auth::id());
            })
            ->orderBy('start_time', 'desc')
            ->first();

        if (!$booking) {
            return response()->json([
                'booking' => null,
                'message' => 'No confirmed booking found for this patient'
            ]);
        }

        return response()->json([
            'booking' => [
                'id' => $booking->id,
                'patient_id' => $booking->patient_id,
                'start_time' => $booking->start_time,
                'end_time' => $booking->end_time,
                'status' => $booking->status,
                'patient' => [
                    'id' => $booking->patient->id,
                    'name' => $booking->patient->name,
                    'email' => $booking->patient->email,
                ],
                'healthcare' => [
                    'id' => $booking->schedule->healthcare->id,
                    'name' => $booking->schedule->healthcare->name,
                ],
            ]
        ]);
    }
}
