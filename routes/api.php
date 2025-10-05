<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\SnsController;
use App\Http\Controllers\CampaignNotifyController;

require __DIR__.'/api/blog.php';
require __DIR__.'/api/schedule.php';
require __DIR__.'/api/booking.php';
require __DIR__.'/api/notification.php';
require __DIR__.'/api/quiz.php';
require __DIR__.'/api/report.php';
require __DIR__.'/api/video-call.php';
require __DIR__.'/api/healthcare.php';
require __DIR__.'/api/health.php';
require __DIR__.'/api/events.php';
require __DIR__.'/api/livekit.php';

// SNS subscription + notification endpoint (no auth). SNS will POST JSON here.
Route::post('/sns/campaign', [SnsController::class, 'handle']);

// Proxy route: frontend calls EC2 domain, Laravel forwards to API Gateway → Lambda
Route::post('/campaigns/{campaignId}/notify', [CampaignNotifyController::class, 'notify']);
