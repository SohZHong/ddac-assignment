<template>
    <div class="video-call space-y-4">
        <!-- Local Video -->
        <video ref="localVideoRef" class="w-1/2 rounded border" autoplay muted playsinline></video>

        <!-- Remote Video -->
        <video ref="remoteVideoRef" class="w-1/2 rounded border" autoplay playsinline></video>

        <div class="space-x-2">
            <button @click="startCall" class="rounded bg-green-600 px-4 py-2 text-white">Start Call</button>
            <button @click="hangUp" class="rounded bg-red-600 px-4 py-2 text-white">Hang Up</button>
        </div>
    </div>
</template>

<script setup lang="ts">
import axios from 'axios';
import Echo from 'laravel-echo';
import { onBeforeUnmount, ref } from 'vue';

// Props
interface Props {
    roomId: string;
    currentUser: {
        id: number;
        name: string;
        role: string;
    };
}

const props = defineProps<Props>();

const localVideoRef = ref<HTMLVideoElement | null>(null);
const remoteVideoRef = ref<HTMLVideoElement | null>(null);

let peerConnection: RTCPeerConnection | null = null;
let localStream: MediaStream | null = null;

// Use props for user and room data
const userId = props.currentUser.id;
const roomId = props.roomId;

// Setup Echo with Reverb
const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',
    enabledTransports: ['ws', 'wss'],
});

async function initMedia() {
    localStream = await navigator.mediaDevices.getUserMedia({
        video: true,
        audio: true,
    });

    if (localVideoRef.value) {
        localVideoRef.value.srcObject = localStream;
    }
}

async function createPeerConnection() {
    peerConnection = new RTCPeerConnection({
        iceServers: [
            { urls: 'stun:stun.l.google.com:19302' },
            {
                urls: 'turn:openrelay.metered.ca:80',
                username: 'openrelayproject',
                credential: 'openrelayproject',
            },
        ],
    });

    // Send ICE candidates to backend
    peerConnection.onicecandidate = ({ candidate }) => {
        if (candidate) {
            axios.post('/api/signal', {
                roomId,
                userId,
                type: 'ice',
                candidate,
            });
        }
    };

    // When remote track arrives, set it on remote video
    peerConnection.ontrack = (event) => {
        if (remoteVideoRef.value) {
            remoteVideoRef.value.srcObject = event.streams[0];
        }
    };

    // Add local tracks
    if (localStream) {
        localStream.getTracks().forEach((track) => {
            peerConnection?.addTrack(track, localStream!);
        });
    }
}

async function startCall() {
    await initMedia();
    await createPeerConnection();

    const offer = await peerConnection!.createOffer();
    await peerConnection!.setLocalDescription(offer);

    // Send offer
    await axios.post('/api/signal', {
        roomId,
        userId,
        type: 'offer',
        sdp: offer.sdp,
    });
}

function hangUp() {
    peerConnection?.close();
    peerConnection = null;
    localStream?.getTracks().forEach((t) => t.stop());
}

//
// 📡 Handle incoming signals
//
echo.private(`webrtc.${roomId}`).listen('.signal', async (e: any) => {
    const { type, sdp, candidate, from } = e.signal;

    if (from === userId) return; // ignore own

    if (!peerConnection) {
        await initMedia();
        await createPeerConnection();
    }

    if (type === 'offer') {
        await peerConnection!.setRemoteDescription({ type: 'offer', sdp });
        const answer = await peerConnection!.createAnswer();
        await peerConnection!.setLocalDescription(answer);

        await axios.post('/api/signal', {
            roomId,
            userId,
            type: 'answer',
            sdp: answer.sdp,
        });
    } else if (type === 'answer') {
        await peerConnection!.setRemoteDescription({ type: 'answer', sdp });
    } else if (type === 'ice' && candidate) {
        try {
            await peerConnection!.addIceCandidate(candidate);
        } catch (err) {
            console.error('Error adding ICE candidate', err);
        }
    }
});

onBeforeUnmount(() => {
    hangUp();
});
</script>
