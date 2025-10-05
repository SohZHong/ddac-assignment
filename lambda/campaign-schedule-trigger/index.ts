import { PublishCommand, SNSClient } from '@aws-sdk/client-sns';
import type { APIGatewayProxyEvent, ScheduledEvent } from 'aws-lambda';

const region = process.env.AWS_DEFAULT_REGION;
const topicArn = process.env.CAMPAIGN_SEND_TOPIC_ARN; // SNS topic subscribed by channel queues

const sns = new SNSClient({ region });

// Triggered by EventBridge schedule with input: { "campaignId": "...", "channels": ["email","sms"] }
export const handler = async (event: APIGatewayProxyEvent | ScheduledEvent) => {
    const input = parseInput(event);
    const campaignId = input.campaignId;
    const channels: string[] = input.channels ?? ['email'];

    if (!campaignId || !topicArn) {
        console.log('Missing campaignId or topicArn');
        return { ok: false };
    }

    await sns.send(
        new PublishCommand({
            TopicArn: topicArn,
            Message: JSON.stringify({ type: 'campaign_send_requested', campaignId, channels }),
            Subject: 'Campaign Send Requested',
        }),
    );

    return { ok: true };
};

function parseInput(event: APIGatewayProxyEvent | ScheduledEvent): any {
    // API Gateway proxy
    const eg = event as APIGatewayProxyEvent;
    if (typeof eg.httpMethod === 'string') {
        try {
            const body = eg.body && typeof eg.body === 'string' ? JSON.parse(eg.body) : eg.body || {};
            // Path param override if present
            if (eg.pathParameters?.campaignId && !body.campaignId) body.campaignId = eg.pathParameters.campaignId;
            return body || {};
        } catch (e) {
            return {};
        }
    }

    // EventBridge schedule or direct invoke
    const se = event as any;
    return se.detail ?? se;
}
