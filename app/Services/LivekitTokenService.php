<?php

namespace App\Services;

use Agence104\LiveKit\AccessToken;
use Agence104\LiveKit\AccessTokenOptions;
use Agence104\LiveKit\VideoGrant;
use App\Models\User;
use App\Models\LivekitRoom;

class LivekitTokenService
{
    public function generateToken(User $user, LivekitRoom $room, string $participantIdentity): string
    {
        $tokenOptions = (new AccessTokenOptions())
            ->setIdentity($participantIdentity);

        $videoGrant = (new VideoGrant())
            ->setRoomJoin()
            ->setRoomName($room->room_name);

        // Set permissions based on user role and room ownership
        if ($user->id === $room->created_by) {
            // Room creator has full permissions
            $videoGrant->setCanPublish(true)
                ->setCanSubscribe(true)
                ->setCanPublishData(true);
        } else {
            // Regular participants can publish and subscribe
            $videoGrant->setCanPublish(true)
                ->setCanSubscribe(true)
                ->setCanPublishData(true);
        }

        return (new AccessToken(
            config('services.livekit.api_key'),
            config('services.livekit.api_secret')
        ))
            ->init($tokenOptions)
            ->setGrant($videoGrant)
            ->toJwt();
    }

    public function generateViewerToken(User $user, LivekitRoom $room, string $participantIdentity): string
    {
        $tokenOptions = (new AccessTokenOptions())
            ->setIdentity($participantIdentity);

        $videoGrant = (new VideoGrant())
            ->setRoomJoin()
            ->setRoomName($room->room_name)
            ->setCanPublish(false) // Viewers cannot publish
            ->setCanSubscribe(true)
            ->setCanPublishData(true); // But they can chat

        return (new AccessToken(
            config('services.livekit.api_key'),
            config('services.livekit.api_secret')
        ))
            ->init($tokenOptions)
            ->setGrant($videoGrant)
            ->toJwt();
    }
}
