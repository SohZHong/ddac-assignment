import { Context, SNSEvent } from 'aws-lambda';

// Universal Notification Handler
export const handler = async (event: SNSEvent, context: Context) => {
    for (const record of event.Records) {
        const snsMessage = JSON.parse(record.Sns.Message);

        console.log('Received SNS message:', snsMessage);

        switch (snsMessage.type) {
            case 'booking_confirmation':
                await sendEmail(snsMessage.recipient, 'Booking Confirmation', snsMessage.message);
                break;

            case 'schedule_reminder':
                await sendEmail(snsMessage.recipient, 'Schedule Reminder', snsMessage.message);
                break;

            case 'cancellation':
                await sendEmail(snsMessage.recipient, 'Booking Cancelled', snsMessage.message);
                break;

            default:
                console.warn('Unknown notification type:', snsMessage.type);
        }
    }
};

// Example: Replace this with SES, Pinpoint, or 3rd-party service later
async function sendEmail(to: string, subject: string, body: string) {
    console.log(`📧 Sending email to ${to} | Subject: ${subject} | Body: ${body}`);
    // TODO: integrate AWS SES or another email/SMS provider here
}
