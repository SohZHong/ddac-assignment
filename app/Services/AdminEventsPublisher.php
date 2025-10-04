<?php

namespace App\Services;

use Aws\Sns\SnsClient;

class AdminEventsPublisher
{
    private SnsClient $client;
    private string $topicArn;

    public function __construct()
    {
        $this->client = new SnsClient([
            'version' => '2010-03-31',
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        ]);
        $this->topicArn = (string) env('SNS_ADMIN_EVENTS_ARN', '');
    }

    public function publish(string $type, array $data, ?string $emailSubject = null, ?string $emailBodyText = null): void
    {
        if ($this->topicArn === '') {
            return; // fail-soft if not configured
        }

        $defaultPayload = json_encode([
            'type' => $type,
            'data' => $data,
            'at' => now()->toISOString(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $params = [
            'TopicArn' => $this->topicArn,
            'MessageAttributes' => [
                'type' => [
                    'DataType' => 'String',
                    'StringValue' => $type,
                ],
            ],
        ];

        // If an email subject/body is provided, publish protocol-specific message
        if ($emailSubject !== null && $emailBodyText !== null) {
            $params['Subject'] = $emailSubject; // used by email protocol
            $params['MessageStructure'] = 'json';
            $params['Message'] = json_encode([
                'default' => $defaultPayload,
                'email' => $emailBodyText,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            // Fallback: one message for all protocols (JSON string)
            $params['Message'] = $defaultPayload;
        }

        $this->client->publish($params);
    }
}


