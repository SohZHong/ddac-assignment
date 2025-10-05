<?php

use App\Models\AdminLog;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

it('confirms subscription and logs metadata without target_id', function () {
    // Ensure database is migrated for test env
    Artisan::call('migrate');

    $payload = [
        'Type' => 'SubscriptionConfirmation',
        'MessageId' => (string) Str::uuid(),
        'Token' => 'fake-token',
        'TopicArn' => 'arn:aws:sns:us-east-1:123456789012:hc_campaign_events',
        'SubscribeURL' => 'https://example.com/confirm',
        'Timestamp' => now()->toIso8601String(),
        'SignatureVersion' => '1',
        'Signature' => 'fake',
        'SigningCertURL' => 'https://sns.us-east-1.amazonaws.com/cert.pem',
    ];

    // Do not actually perform HTTP GET to SubscribeURL in tests
    Http::fake();

    $res = $this->postJson('/api/sns/campaign', $payload, [
        'x-amz-sns-message-type' => 'SubscriptionConfirmation',
    ]);

    $res->assertOk();

    $log = AdminLog::latest('id')->first();
    expect($log)->not->toBeNull();
    expect($log->action)->toBe('sns_subscription_confirmation');
    expect($log->target_type)->toBe('sns');
    expect($log->target_id)->toBeNull();
    expect($log->metadata)->toBeArray();
});

it('stores notification with parsed message and numeric target_id', function () {
    Artisan::call('migrate');

    $message = [
        'type' => 'campaign_send_requested',
        'campaignId' => '123',
        'channels' => ['in_app'],
    ];

    $payload = [
        'Type' => 'Notification',
        'MessageId' => (string) Str::uuid(),
        'TopicArn' => 'arn:aws:sns:us-east-1:123456789012:hc_campaign_events',
        'Subject' => 'Campaign Send Requested',
        'Message' => json_encode($message),
        'Timestamp' => now()->toIso8601String(),
        'SignatureVersion' => '1',
        'Signature' => 'fake',
        'SigningCertURL' => 'https://sns.us-east-1.amazonaws.com/cert.pem',
    ];

    $res = $this->postJson('/api/sns/campaign', $payload, [
        'x-amz-sns-message-type' => 'Notification',
    ]);

    $res->assertOk();

    $log = AdminLog::latest('id')->first();
    expect($log)->not->toBeNull();
    expect($log->action)->toBe('sns_campaign_event');
    expect($log->target_type)->toBe('campaign');
    expect($log->target_id)->toBe(123);
    expect($log->metadata['parsed']['campaignId'])->toBe('123');
});


