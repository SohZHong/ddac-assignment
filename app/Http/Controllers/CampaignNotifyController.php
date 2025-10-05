<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CampaignNotifyController extends Controller
{
    public function notify(Request $request, string $campaignId)
    {
        $validated = $request->validate([
            'channels' => ['array'],
            'channels.*' => ['string'],
        ]);

        $channels = $validated['channels'] ?? ['in_app'];

        // Hardcoded per user's request (no env usage)
        $apiBase = 'https://16qqrjiewk.execute-api.us-east-1.amazonaws.com/prod';

        $url = $apiBase . "/campaigns/{$campaignId}/notify";

        try {
            $resp = Http::timeout(8)->asJson()->post($url, [
                'channels' => $channels,
            ]);

            return response()->json($resp->json() ?? ['ok' => $resp->successful()], $resp->status());
        } catch (\Throwable $e) {
            Log::error('Campaign notify proxy failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Upstream call failed',
            ], 502);
        }
    }
}


