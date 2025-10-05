<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class MeetingNotificationApiService
{
    private string $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.meeting_notification_api.url');
    }

    /**
     * Send meeting link notification via Lambda API
     *
     * @param array $meetingData
     * @return bool
     */
    public function sendMeetingLinkNotification(array $meetingData): bool
    {
        try {
            // Prepare the payload for the Lambda API
            $payload = [
                'patient_id' => $meetingData['patient_id'],
                'patient_name' => $meetingData['patient_name'],
                'doctor_id' => $meetingData['doctor_id'],
                'doctor_name' => $meetingData['doctor_name'],
                'room_id' => $meetingData['room_id'],
            ];

            // Make HTTP request to Lambda API
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($this->apiUrl, $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info('Meeting notification sent via Lambda API successfully', [
                    'patient_id' => $meetingData['patient_id'],
                    'doctor_id' => $meetingData['doctor_id'],
                    'room_id' => $meetingData['room_id'],
                    'message_id' => $responseData['messageId'] ?? null,
                ]);

                return true;
            } else {
                Log::error('Lambda API request failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'payload' => $payload,
                ]);

                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception occurred while calling Lambda API', [
                'error' => $e->getMessage(),
                'payload' => $meetingData,
            ]);

            return false;
        }
    }

    /**
     * Check if the API service is available
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiUrl);
    }
}