<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'role:health_campaign_manager,system_admin'])
    ->group(function () {
        Route::get('/campaigns', function () {
            return Inertia::render('Campaigns/Dashboard');
        })->name('campaigns.index');
    });