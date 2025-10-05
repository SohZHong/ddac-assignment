"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.handler = void 0;
const client_sns_1 = require("@aws-sdk/client-sns");
const sns = new client_sns_1.SNSClient({ region: process.env.AWS_DEFAULT_REGION });
// Main Lambda handler for API Gateway - sends meeting link notifications via SNS
const handler = async (event) => {
    console.log('Meeting Link Notification Request:', JSON.stringify(event));
    try {
        // Parse request body
        const body = typeof event.body === 'string' ? JSON.parse(event.body) : event.body;
        // Validate required fields
        const validation = validateRequest(body);
        if (!validation.valid) {
            return createResponse(400, {
                success: false,
                error: validation.error,
            });
        }
        const request = body;
        // Send notification via SNS
        const result = await sendMeetingLinkNotification(request);
        return createResponse(200, result);
    }
    catch (error) {
        console.error('Error sending meeting link notification:', error);
        return createResponse(500, {
            success: false,
            error: 'Internal server error',
            channels: [],
        });
    }
};
exports.handler = handler;
function validateRequest(body) {
    if (!body) {
        return { valid: false, error: 'Request body is required' };
    }
    const requiredFields = ['patient_id', 'patient_name', 'doctor_id', 'doctor_name', 'room_id'];
    for (const field of requiredFields) {
        if (!body[field]) {
            return { valid: false, error: `${field} is required` };
        }
    }
    // Validate data types
    if (typeof body.patient_id !== 'number' || typeof body.doctor_id !== 'number') {
        return { valid: false, error: 'patient_id and doctor_id must be numbers' };
    }
    if (typeof body.patient_name !== 'string' || typeof body.doctor_name !== 'string' || typeof body.room_id !== 'string') {
        return { valid: false, error: 'patient_name, doctor_name, and room_id must be strings' };
    }
    return { valid: true };
}
async function sendMeetingLinkNotification(data) {
    try {
        const notificationType = data.notification_type || 'meeting_link';
        const emailSubject = `Video Consultation Ready - Dr. ${data.doctor_name}`;
        // Format messages
        const emailMessage = formatEmailMessage(data);
        const smsMessage = formatSMSMessage(data);
        // Construct SNS message with multiple delivery protocols
        const message = {
            default: emailMessage, // Default message for unknown protocols
            sms: smsMessage,
            email: emailMessage,
            // For HTTP/HTTPS endpoints (push notifications, webhooks)
            http: JSON.stringify({
                notification_type: notificationType,
                patient_id: data.patient_id,
                doctor_id: data.doctor_id,
                room_id: data.room_id,
                doctor_name: data.doctor_name,
                patient_name: data.patient_name,
                meeting_url: data.meeting_url || `${process.env.APP_URL}/video-call/${data.room_id}`,
                timestamp: new Date().toISOString(),
                action: 'join_video_call',
            }),
        };
        // Publish to SNS topic
        const result = await sns.send(new client_sns_1.PublishCommand({
            TopicArn: process.env.AWS_SNS_VIDEO_CALL_TOPIC_ARN,
            Message: JSON.stringify(message),
            MessageStructure: 'json', // Important: enables protocol-specific messages
            Subject: emailSubject,
            MessageAttributes: {
                notification_type: {
                    DataType: 'String',
                    StringValue: notificationType,
                },
                patient_id: {
                    DataType: 'Number',
                    StringValue: data.patient_id.toString(),
                },
                doctor_id: {
                    DataType: 'Number',
                    StringValue: data.doctor_id.toString(),
                },
                priority: {
                    DataType: 'String',
                    StringValue: 'high',
                },
                room_id: {
                    DataType: 'String',
                    StringValue: data.room_id,
                },
            },
        }));
        console.log('SNS meeting link notification sent successfully', {
            message_id: result.MessageId,
            patient_id: data.patient_id,
            doctor_id: data.doctor_id,
            room_id: data.room_id,
        });
        return {
            success: true,
            message_id: result.MessageId,
            channels: ['email', 'sms', 'push_notification'],
        };
    }
    catch (error) {
        console.error('SNS meeting link notification failed', {
            error: error.message,
            patient_id: data.patient_id,
            doctor_id: data.doctor_id,
            room_id: data.room_id,
        });
        return {
            success: false,
            error: `SNS notification failed: ${error.message}`,
            channels: [],
        };
    }
}
function formatEmailMessage(data) {
    const meetingUrl = data.meeting_url || `${process.env.APP_URL}/video-call/${data.room_id}`;
    const currentTime = new Date().toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    });
    return `Dear ${data.patient_name},

Dr. ${data.doctor_name} has started a video consultation session and is ready to see you.

Video Call Details:
• Doctor: Dr. ${data.doctor_name}
• Room ID: ${data.room_id}
• Time: ${currentTime}

To join the video consultation:
1. Click this link: ${meetingUrl}
2. Or manually enter Room ID: ${data.room_id}

Important Notes:
• Please join as soon as possible - Dr. ${data.doctor_name} is waiting
• Make sure you have a stable internet connection
• Enable your camera and microphone when joining
• If you experience technical difficulties, please contact support

Thank you for choosing our healthcare platform.

Best regards,
Healthcare Platform Team`;
}
function formatSMSMessage(data) {
    const meetingUrl = data.meeting_url || `${process.env.APP_URL}/video-call/${data.room_id}`;
    return `HEALTHCARE ALERT: Dr. ${data.doctor_name} is ready for your video consultation. Room ID: ${data.room_id}. Join now: ${meetingUrl}`;
}
function createResponse(statusCode, body) {
    return {
        statusCode,
        headers: {
            'Content-Type': 'application/json',
            'Access-Control-Allow-Origin': '*',
            'Access-Control-Allow-Headers': 'Content-Type,Authorization',
            'Access-Control-Allow-Methods': 'POST,OPTIONS',
        },
        body: JSON.stringify(body),
    };
}
