<?php

namespace App\Services;

use Aws\Sns\SnsClient;

class AwsNotificationService
{
    private SnsClient $sns;
    private string $topicArn;

    public function __construct()
    {
        $this->sns = new SnsClient([
            'region'      => config('lambda.sns.region'),
            'version'     => config('lambda.sns.version'),
            'credentials' => config('lambda.sns.credentials'),
        ]);

        $this->topicArn = config('lambda.sns.topic_arn');
    }

    public function publish($message)
    {
        $this->sns->publish([
            'TopicArn' => $this->topicArn,
            'Message'  => json_encode($message),
        ]);
    }
}