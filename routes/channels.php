<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\VideoCall;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('video-call.{roomId}', function ($user, $roomId) {
    $videoCall = VideoCall::where('room_id', $roomId)->first();
    
    if (!$videoCall) {
        return false;
    }
    
    return (int) $user->id === (int) $videoCall->doctor_id || (int) $user->id === (int) $videoCall->patient_id;
});

Broadcast::channel('video-call-notifications.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
