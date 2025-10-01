// producer.ts
import { SQSClient, SendMessageCommand } from '@aws-sdk/client-sqs';
import type { APIGatewayProxyHandler } from 'aws-lambda';

// Reuse client across Lambda invocations
const sqs = new SQSClient({ region: process.env.AWS_DEFAULT_REGION });

export const handler: APIGatewayProxyHandler = async (event) => {
    console.log('Incoming event:', JSON.stringify(event));

    try {
        const body = typeof event.body === 'string' ? JSON.parse(event.body) : event.body;

        if (!body?.schedule_id || !body?.patient_id || !body?.start_time || !body?.end_time) {
            return response(400, { error: 'Missing required fields' });
        }

        const params = {
            QueueUrl: process.env.SQS_URL,
            MessageBody: JSON.stringify({
                schedule_id: body.schedule_id,
                patient_id: body.patient_id,
                start_time: body.start_time,
                end_time: body.end_time,
                status: body.status ?? '0',
                patient_email: body.patient_email ?? null,
            }),
            MessageGroupId: `schedule-${body.schedule_id}`,
            MessageDeduplicationId: `${body.schedule_id}-${body.patient_id}-${body.start_time}`,
        };

        const result = await sqs.send(new SendMessageCommand(params));
        return response(200, { messageId: result.MessageId });
    } catch (err: any) {
        console.error('Error:', err);
        return response(500, { error: 'Internal Server Error' });
    }
};

function response(statusCode: number, body: any) {
    return {
        statusCode,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
    };
}
