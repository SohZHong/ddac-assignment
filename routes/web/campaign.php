<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Campaign\CampaignController;

Route::middleware(['auth', 'role:health_campaign_manager,system_admin'])
    ->group(function () {
        Route::resource('campaigns', CampaignController::class);
    });