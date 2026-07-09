<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('camera-status-{token}', function ($user, $token) {
    return \App\Models\Camera::where('websocket_channel_id', 'camera-status-' . $token)
        ->where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->orWhereHas('group', function ($q) use ($user) {
                      $q->where('user_id', $user->id);
                  });
        })
        ->exists();
});

Broadcast::channel('user.{userId}.detections', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('user.{userId}.device-configs', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('user.{userId}.ota-updates', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

