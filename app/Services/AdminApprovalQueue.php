<?php

namespace App\Services;

use Aws\Sqs\SqsClient;

class AdminApprovalQueue
{
    private SqsClient $client;
    private string $queueUrl;

    public function __construct()
    {
        $this->client = new SqsClient([
            'version' => '2012-11-05',
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        ]);
        $this->queueUrl = (string) env('SQS_ADMIN_APPROVALS_URL', '');
    }

    public function publish(array $payload): void
    {
        if ($this->queueUrl === '') {
            // Fail soft if not configured to avoid blocking approval path in dev
            return;
        }

        $this->client->sendMessage([
            'QueueUrl' => $this->queueUrl,
            'MessageBody' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
    }
}


