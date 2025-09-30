<?php

return [
    'sns' => [
        'region'  => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'version' => '2010-03-31',
        'credentials' => [
            'key'    => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'token' => env('AWS_SESSION_TOKEN'),
        ],
        'topic_arn' => env('AWS_SNS_TOPIC_ARN'),
    ],
    'sqs' => [
        'region'  => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'version' => '2012-11-05',
        'credentials' => [
            'key'    => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'token' => env('AWS_SESSION_TOKEN'),
        ],
        'queue_url' => env('AWS_SQS_QUEUE_URL'),
    ],
    'api' => [
        'booking' => env('AWS_API_GATEWAY_BOOKING_URL')
    ]
];