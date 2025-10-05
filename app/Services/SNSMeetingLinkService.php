<?php

namespace App\Services;

use Aws\Sns\SnsClient;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;

class SNSMeetingLinkService
{
    private $snsClient;
    private $topicArn;

    public function __construct()
    {
        $this->snsClient = new SnsClient([
            'region' => config('lambda.sns.region'),
            'version' => config('lambda.sns.version'),
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
                'token' => env('AWS_SESSION_TOKEN'),
            ]
        ]);

        $this->topicArn = env('AWS_SNS_VIDEO_CALL_TOPIC_ARN', env('AWS_SNS_TOPIC_ARN'));
    }

    public function sendMeetingLinkNotification($data)
    {
        try {
            // Format the email content as HTML
            $emailSubject = "Video Consultation Ready - Dr. {$data['doctor_name']}";
            
            $emailMessage = $this->formatEmailMessage($data);
            $smsMessage = $this->formatSMSMessage($data);
            
            // For email subscriptions, we need a simple message structure
            $result = $this->snsClient->publish([
                'TopicArn' => $this->topicArn,
                'Message' => $emailMessage,
                'Subject' => $emailSubject,
                'MessageAttributes' => [
                    'notification_type' => [
                        'DataType' => 'String',
                        'StringValue' => 'meeting_link'
                    ],
                    'patient_id' => [
                        'DataType' => 'Number', 
                        'StringValue' => (string)$data['patient_id']
                    ],
                    'doctor_id' => [
                        'DataType' => 'Number',
                        'StringValue' => (string)$data['doctor_id']
                    ],
                    'priority' => [
                        'DataType' => 'String',
                        'StringValue' => 'high'
                    ]
                ]
            ]);

            Log::info('SNS meeting link notification sent successfully', [
                'message_id' => $result['MessageId'] ?? null,
                'patient_id' => $data['patient_id'],
                'doctor_id' => $data['doctor_id'],
                'room_id' => $data['room_id']
            ]);

            return [
                'success' => true,
                'message_id' => $result['MessageId'] ?? null,
                'channels' => ['email', 'sms', 'push_notification']
            ];

        } catch (AwsException $e) {
            Log::error('SNS meeting link notification failed', [
                'error' => $e->getMessage(),
                'patient_id' => $data['patient_id'] ?? null,
                'doctor_id' => $data['doctor_id'] ?? null
            ]);

            return false;
        }
    }

    private function formatEmailMessage($data)
    {
        $meetingUrl = $data['meeting_url'] ?? url("/video-call/{$data['room_id']}");
        
        return "Dear {$data['patient_name']},

Dr. {$data['doctor_name']} has started a video consultation session and is ready to see you.

Video Call Details:
• Doctor: Dr. {$data['doctor_name']}
• Room ID: {$data['room_id']}
• Time: " . now()->format('M d, Y at h:i A') . "

To join the video consultation:
1. Click this link: {$meetingUrl}
2. Or manually enter Room ID: {$data['room_id']}

Important Notes:
• Please join as soon as possible - Dr. {$data['doctor_name']} is waiting
• Make sure you have a stable internet connection
• Enable your camera and microphone when joining
• If you experience technical difficulties, please contact support

Thank you for choosing our healthcare platform.

Best regards,
Healthcare Platform Team";
    }

    private function formatSMSMessage($data)
    {
        $meetingUrl = $data['meeting_url'] ?? url("/video-call/{$data['room_id']}");
        
        return "HEALTHCARE ALERT: Dr. {$data['doctor_name']} is ready for your video consultation. Room ID: {$data['room_id']}. Join now: {$meetingUrl}";
    }

    public function sendMeetingReminder($data)
    {
        try {
            $emailSubject = "Upcoming Video Consultation - Dr. {$data['doctor_name']}";
            
            $emailMessage = $this->formatReminderEmailMessage($data);
            $smsMessage = $this->formatReminderSMSMessage($data);

            $message = [
                'default' => $emailMessage,
                'sms' => $smsMessage,
                'email' => $emailMessage,
                'http' => json_encode([
                    'notification_type' => 'meeting_reminder',
                    'patient_id' => $data['patient_id'],
                    'appointment_time' => $data['appointment_time'],
                    'doctor_name' => $data['doctor_name'],
                    'action' => 'prepare_for_call'
                ])
            ];

            $result = $this->snsClient->publish([
                'TopicArn' => $this->topicArn,
                'Message' => json_encode($message),
                'MessageStructure' => 'json',
                'Subject' => $emailSubject,
                'MessageAttributes' => [
                    'notification_type' => [
                        'DataType' => 'String',
                        'StringValue' => 'meeting_reminder'
                    ],
                    'patient_id' => [
                        'DataType' => 'Number',
                        'StringValue' => (string)$data['patient_id']
                    ]
                ]
            ]);

            Log::info('SNS meeting reminder sent successfully', [
                'message_id' => $result['MessageId'] ?? null,
                'patient_id' => $data['patient_id'],
                'appointment_time' => $data['appointment_time']
            ]);

            return [
                'success' => true,
                'message_id' => $result['MessageId'] ?? null
            ];

        } catch (AwsException $e) {
            Log::error('SNS meeting reminder failed', [
                'error' => $e->getMessage(),
                'patient_id' => $data['patient_id'] ?? null
            ]);

            return false;
        }
    }

    private function formatReminderEmailMessage($data)
    {
        $appointmentTime = \Carbon\Carbon::parse($data['appointment_time'])->format('M d, Y at h:i A');
        
        return "Dear {$data['patient_name']},

This is a friendly reminder about your upcoming video consultation with Dr. {$data['doctor_name']}.

Appointment Details:
• Doctor: Dr. {$data['doctor_name']}
• Scheduled Time: {$appointmentTime}
• Duration: Approximately 30 minutes

Preparation Checklist:
□ Test your camera and microphone
□ Ensure stable internet connection  
□ Find a quiet, private location
□ Have your medical records ready
□ Prepare any questions for the doctor

You will receive another notification when Dr. {$data['doctor_name']} starts the video call.

If you need to reschedule or cancel, please contact us as soon as possible.

Best regards,
Healthcare Platform Team";
    }

    private function formatReminderSMSMessage($data)
    {
        $appointmentTime = \Carbon\Carbon::parse($data['appointment_time'])->format('M d h:i A');
        
        return "REMINDER: Video consultation with Dr. {$data['doctor_name']} scheduled for {$appointmentTime}. Please be ready to join when notified.";
    }

    public function sendMeetingCancellation($data)
    {
        try {
            $emailSubject = "Video Consultation Cancelled - Dr. {$data['doctor_name']}";
            
            $emailMessage = $this->formatCancellationEmailMessage($data);
            $smsMessage = $this->formatCancellationSMSMessage($data);

            $message = [
                'default' => $emailMessage,
                'sms' => $smsMessage,
                'email' => $emailMessage,
                'http' => json_encode([
                    'notification_type' => 'meeting_cancellation',
                    'patient_id' => $data['patient_id'],
                    'doctor_name' => $data['doctor_name'],
                    'reason' => $data['reason'] ?? 'Not specified',
                    'action' => 'reschedule_appointment'
                ])
            ];

            $result = $this->snsClient->publish([
                'TopicArn' => $this->topicArn,
                'Message' => json_encode($message),
                'MessageStructure' => 'json',
                'Subject' => $emailSubject,
                'MessageAttributes' => [
                    'notification_type' => [
                        'DataType' => 'String',
                        'StringValue' => 'meeting_cancellation'
                    ],
                    'patient_id' => [
                        'DataType' => 'Number',
                        'StringValue' => (string)$data['patient_id']
                    ]
                ]
            ]);

            return [
                'success' => true,
                'message_id' => $result['MessageId'] ?? null
            ];

        } catch (AwsException $e) {
            Log::error('SNS meeting cancellation failed', [
                'error' => $e->getMessage(),
                'patient_id' => $data['patient_id'] ?? null
            ]);

            return false;
        }
    }

    private function formatCancellationEmailMessage($data)
    {
        $reason = $data['reason'] ?? 'Due to unforeseen circumstances';
        
        return "Dear {$data['patient_name']},

We regret to inform you that your video consultation with Dr. {$data['doctor_name']} has been cancelled.

Cancellation Details:
• Doctor: Dr. {$data['doctor_name']}
• Reason: {$reason}
• Cancelled at: " . now()->format('M d, Y at h:i A') . "

Next Steps:
• Our support team will contact you within 24 hours
• You can reschedule your appointment online
• If urgent, please contact our emergency hotline

We apologize for any inconvenience caused and appreciate your understanding.

To reschedule your appointment, please log into your account or contact our support team.

Best regards,
Healthcare Platform Team";
    }

    private function formatCancellationSMSMessage($data)
    {
        return "NOTICE: Your video consultation with Dr. {$data['doctor_name']} has been cancelled. Our team will contact you to reschedule. Apologies for the inconvenience.";
    }
}