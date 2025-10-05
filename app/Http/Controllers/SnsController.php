<?php

namespace App\Http\Controllers;

use App\Models\AdminLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SnsController extends Controller
{
    public function handle(Request $request)
    {
        $type = $request->header('x-amz-sns-message-type');
        $payload = $request->json()->all();

        // Basic validation of SNS signature is omitted for brevity; can be added later
        if ($type === 'SubscriptionConfirmation') {
            // Auto-confirm the HTTPS subscription so SNS can start delivering notifications
            $confirmed = false;
            if (!empty($payload['SubscribeURL'])) {
                try {
                    $resp = Http::timeout(5)->get($payload['SubscribeURL']);
                    $confirmed = $resp->successful();
                } catch (\Throwable $e) {
                    Log::warning('SNS auto-confirm failed', ['error' => $e->getMessage()]);
                }
            }

            // Store a log entry for audit (best-effort; do not fail request)
            try {
                AdminLog::create([
                    'user_id' => null,
                    'action' => 'sns_subscription_confirmation',
                    'target_type' => 'sns',
                    // target_id is bigint; avoid writing ARN string. Leave null.
                    'target_id' => null,
                    'metadata' => array_merge($payload, ['auto_confirmed' => $confirmed]),
                    'ip_address' => $request->ip(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('SNS subscription confirmed but log not persisted', [
                    'error' => $e->getMessage(),
                ]);
            }
            // Let SNS know we received it; actual confirm is done by hitting SubscribeURL (optional server-side fetch)
            return response()->json(['status' => 'ok']);
        }

        if ($type === 'Notification') {
            $rawMessage = $payload['Message'] ?? null;
            $parsed = $this->tryDecode($rawMessage);

            // Attempt to derive a numeric target id from parsed message (e.g., campaignId)
            $targetId = null;
            if (is_array($parsed)) {
                // Accept either numeric or numeric-string campaignId
                if (isset($parsed['campaignId']) && is_numeric($parsed['campaignId'])) {
                    $targetId = (int) $parsed['campaignId'];
                }
            }

            try {
                AdminLog::create([
                    'user_id' => null,
                    'action' => 'sns_campaign_event',
                    'target_type' => 'campaign',
                    'target_id' => $targetId,
                    'metadata' => [
                        'sns' => $payload,
                        'parsed' => $parsed,
                    ],
                    'ip_address' => $request->ip(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('SNS notification received but log not persisted', [
                    'error' => $e->getMessage(),
                ]);
            }

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


