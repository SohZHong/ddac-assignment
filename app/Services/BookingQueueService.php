<?php

namespace App\Services;

use Aws\Sqs\SqsClient;

class BookingQueueService
{
    protected SqsClient $sqs;
    protected string $queueUrl;

    public function __construct()
    {
        $this->sqs = new SqsClient([
            'region'      => config('lambda.sqs.region'),
            'version'     => config('lambda.sqs.version'),
            'credentials' => config('lambda.sqs.credentials'),
        ]);

        $this->queueUrl = config('lambda.sqs.queue_url');
    }

    public function push(array $message)
    {
        $this->sqs->sendMessage([
            'QueueUrl'    => $this->queueUrl,
            'MessageBody' => json_encode($message),
            'MessageGroupId' => 'booking', // FIFO grouping
        ]);
    }
}