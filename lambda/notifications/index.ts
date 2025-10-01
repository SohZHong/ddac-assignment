import { PublishCommand, SNSClient } from '@aws-sdk/client-sns';
import { SNSEvent } from 'aws-lambda';

const sns = new SNSClient({ region: process.env.AWS_DEFAULT_REGION });

// Universal Notification Handler
export const handler = async (event: SNSEvent) => {
    for (const record of event.Records) {
        const snsMessage = JSON.parse(record.Sns.Message);

        console.log('Received SNS message:', snsMessage);

        await publishToSns(process.env.AWS_SNS_TOPIC_ARN!, snsMessage);
    }
};

// Publishes messages to SNS
async function publishToSns(topicArn: string, message: any) {
    await sns.send(
        new PublishCommand({
            TopicArn: topicArn,
            Message: JSON.stringify(message),
            Subject: message.type ?? 'Notification',
        }),
    );

    console.log(`Message forwarded to SNS topic: ${topicArn}`);
}
