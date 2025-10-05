<?php

namespace App\Http\Controllers;

use App\Models\AdminLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SnsController extends Controller
{
    public function handle(Request $request)
    {
        $type = $request->header('x-amz-sns-message-type');
        $payload = $request->json()->all();

        // Basic validation of SNS signature is omitted for brevity; can be added later
        if ($type === 'SubscriptionConfirmation') {
            // Store a log entry for audit
            AdminLog::create([
                'user_id' => null,
                'action' => 'sns_subscription_confirmation',
                'target_type' => 'sns',
                'target_id' => $payload['TopicArn'] ?? null,
                'metadata' => $payload,
                'ip_address' => $request->ip(),
            ]);
            // Let SNS know we received it; actual confirm is done by hitting SubscribeURL (optional server-side fetch)
            return response()->json(['status' => 'ok']);
        }

        if ($type === 'Notification') {
            $message = $payload['Message'] ?? null;

            AdminLog::create([
                'user_id' => null,
                'action' => 'sns_campaign_event',
                'target_type' => 'campaign',
                'target_id' => $payload['Subject'] ?? null,
                'metadata' => [
                    'sns' => $payload,
                    'parsed' => $this->tryDecode($message),
                ],
                'ip_address' => $request->ip(),
            ]);

            return response()->json(['status' => 'ok']);
        }

        // For UnsubscribeConfirmation or others
        return response()->json(['status' => 'ignored'], 200);
    }

    private function tryDecode($message)
    {
        if (! is_string($message)) return $message;
        try {
            return json_decode($message, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            return $message;
        }
    }
}


