<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, onUnmounted, ref } from 'vue';

declare global {
    interface Window {
        Echo: any;
    }
}

// Props
interface Props {
    roomId: string;
    doctor: {
        id: number;
        name: string;
    };
    patient: {
        id: number;
        name: string;
    };
    currentUser: {
        id: number;
        name: string;
        role: string;
    };
}

const props = defineProps<Props>();

// Refs
const localVideo = ref<HTMLVideoElement>();
const remoteVideo = ref<HTMLVideoElement>();

// State
const callStatus = ref<'connecting' | 'connected' | 'ended'>('connecting');
const isVideoEnabled = ref(true);
const isAudioEnabled = ref(true);
const isDoctorConnected = ref(false);
const isPatientConnected = ref(false);
const callDuration = ref(0);
const callStartTime = ref<Date | null>(null);

// WebRTC
let localStream: MediaStream | null = null;
let peerConnection: RTCPeerConnection | null = null;
let websocket: any = null;
let iceCandidatesQueue: RTCIceCandidate[] = [];

// Computed
const isDoctor = computed(() => props.currentUser.role === 'doctor');

// Methods
const formatDuration = (seconds: number) => {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    if (hours > 0) {
        return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
    return `${minutes}:${secs.toString().padStart(2, '0')}`;
};

const initializeWebRTC = async () => {
    try {
        localStream = await navigator.mediaDevices.getUserMedia({
            video: true,
            audio: true,
        });

        if (localVideo.value) {
            localVideo.value.srcObject = localStream;
            localVideo.value.muted = true;
        }

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

        localStream.getTracks().forEach((track) => {
            if (peerConnection && localStream) {
                peerConnection.addTrack(track, localStream);
            }
        });

        peerConnection.ontrack = (event) => {
            const [remoteStream] = event.streams;
            if (remoteVideo.value) {
                remoteVideo.value.srcObject = remoteStream;
                remoteVideo.value.autoplay = true;
                remoteVideo.value.playsInline = true;
                callStatus.value = 'connected';
                startCallTimer();
            }
        };

        peerConnection.onicecandidate = (event) => {
            if (event.candidate) {
                sendSignal({
                    type: 'ice-candidate',
                    candidate: event.candidate,
                });
            }
        };

        peerConnection.onconnectionstatechange = () => {
            if (peerConnection) {
                if (peerConnection.connectionState === 'connected') {
                    callStatus.value = 'connected';
                    startCallTimer();
                }
            }
        };
    } catch {
        alert('Failed to access camera and microphone. Please check permissions and refresh the page.');
    }
};

const initializeWebSocket = () => {
    websocket = window.Echo.private(`video-call.${props.roomId}`)
        .listen('.participant.joined', (e: any) => {
            if (e.user_id === props.doctor.id) {
                isDoctorConnected.value = true;
            }
            if (e.user_id === props.patient.id) {
                isPatientConnected.value = true;
            }

            if (isDoctorConnected.value && isPatientConnected.value && isDoctor.value) {
                setTimeout(() => createOffer(), 1000);
            }
        })
        .listen('.webrtc.signal', (e: any) => {
            handleSignal(e.signal);
        })
        .listen('.call.ended', () => {
            endCall();
        });
};

const sendSignal = async (signal: any) => {
    try {
        const otherParticipantId = isDoctor.value ? props.patient.id : props.doctor.id;

        if (signal.sdp) {
            signal.sdp = cleanSDP(signal.sdp);
        }

        await axios.post(`/api/video-calls/${props.roomId}/signal`, {
            signal,
            to_user_id: otherParticipantId,
        });
    } catch (error) {
        console.error('Error sending signal:', error);
    }
};

const cleanSDP = (sdp: string): string => {
    const lines = sdp.split('\r\n');
    const cleanedLines: string[] = [];

    for (const line of lines) {
        if (line.startsWith('a=ssrc-group:')) {
            continue;
        }

        if (line.startsWith('a=ssrc:') && line.includes('msid:')) {
            continue;
        }

        cleanedLines.push(line);
    }

    return cleanedLines.join('\r\n');
};

const handleSignal = async (signal: any) => {
    if (!peerConnection || !signal) return;

    try {
        switch (signal.type) {
            case 'offer':
                const cleanedOffer = {
                    type: signal.type,
                    sdp: cleanSDP(signal.sdp),
                };

                await peerConnection.setRemoteDescription(new RTCSessionDescription(cleanedOffer));
                const answer = await peerConnection.createAnswer();
                await peerConnection.setLocalDescription(answer);

                await sendSignal({
                    type: 'answer',
                    sdp: answer.sdp,
                });

                await processQueuedIceCandidates();

                break;

            case 'answer':
                const cleanedAnswer = {
                    type: signal.type,
                    sdp: cleanSDP(signal.sdp),
                };

                await peerConnection.setRemoteDescription(new RTCSessionDescription(cleanedAnswer));

                await processQueuedIceCandidates();
                break;

            case 'ice-candidate':
                if (peerConnection.remoteDescription) {
                    await peerConnection.addIceCandidate(new RTCIceCandidate(signal.candidate));
                } else {
                    iceCandidatesQueue.push(new RTCIceCandidate(signal.candidate));
                }
                break;
        }
    } catch (error) {
        if (error instanceof Error && error.message.includes('Failed to parse SessionDescription')) {
        }
    }
};

const processQueuedIceCandidates = async () => {
    if (!peerConnection || iceCandidatesQueue.length === 0) return;

    for (const candidate of iceCandidatesQueue) {
        try {
            await peerConnection.addIceCandidate(candidate);
        } catch (error) {
            console.error('Error adding queued ICE candidate:', error);
        }
    }

    iceCandidatesQueue = [];
};

const createOffer = async () => {
    if (!peerConnection) return;

    try {
        const offer = await peerConnection.createOffer();
        await peerConnection.setLocalDescription(offer);
        await sendSignal({
            type: 'offer',
            sdp: offer.sdp,
        });
    } catch (error) {
        console.error('Error creating offer:', error);
    }
};

const toggleVideo = () => {
    if (localStream) {
        const videoTrack = localStream.getVideoTracks()[0];
        if (videoTrack) {
            videoTrack.enabled = !videoTrack.enabled;
            isVideoEnabled.value = videoTrack.enabled;
        }
    }
};

const toggleAudio = () => {
    if (localStream) {
        const audioTrack = localStream.getAudioTracks()[0];
        if (audioTrack) {
            audioTrack.enabled = !audioTrack.enabled;
            isAudioEnabled.value = audioTrack.enabled;
        }
    }
};

const startCallTimer = () => {
    if (callStartTime.value) return;

    callStartTime.value = new Date();
    setInterval(() => {
        if (callStartTime.value && callStatus.value === 'connected') {
            callDuration.value = Math.floor((Date.now() - callStartTime.value.getTime()) / 1000);
        }
    }, 1000);
};

const endCall = async () => {
    try {
        await axios.post(`/api/video-calls/${props.roomId}/end`);
        cleanup();
        router.visit('/dashboard');
    } catch (error) {
        console.error('Error ending call:', error);
        cleanup();
        router.visit('/dashboard');
    }
};

const cleanup = () => {
    if (localStream) {
        localStream.getTracks().forEach((track) => track.stop());
        localStream = null;
    }

    if (peerConnection) {
        peerConnection.close();
        peerConnection = null;
    }

    if (websocket) {
        websocket.stopListening('.participant.joined');
        websocket.stopListening('.webrtc.signal');
        websocket.stopListening('.call.ended');
        websocket = null;
    }

    iceCandidatesQueue = [];

    callStatus.value = 'ended';
};

const joinCall = async () => {
    try {
        await axios.post(`/api/video-calls/${props.roomId}/join`);

        if (isDoctor.value) {
            isDoctorConnected.value = true;
        } else {
            isPatientConnected.value = true;
        }
    } catch (error) {
        console.error('Error joining call:', error);
    }
};

onMounted(async () => {
    await initializeWebRTC();
    initializeWebSocket();
    await joinCall();
});

onUnmounted(() => {
    cleanup();
});
</script>

<template>
    <Head title="Video Consultation" />

    <div class="min-h-screen bg-black">
        <div class="border-b bg-black shadow-sm">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between py-4">
                    <div class="flex items-center space-x-4">
                        <h1 class="text-xl font-semibold text-white">Video Consultation</h1>
                        <div class="flex items-center space-x-2">
                            <div :class="['h-3 w-3 rounded-full', callStatus === 'connected' ? 'bg-green-500' : 'bg-yellow-500']"></div>
                            <span class="text-sm text-white">
                                {{ callStatus === 'connected' ? 'Connected' : 'Connecting...' }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center space-x-3">
                        <!-- Call Duration -->
                        <div v-if="callDuration" class="text-sm text-gray-600">
                            {{ formatDuration(callDuration) }}
                        </div>

                        <!-- End Call Button -->
                        <button
                            @click="endCall"
                            class="flex items-center space-x-2 rounded-lg bg-red-600 px-4 py-2 text-white transition-colors hover:bg-red-700"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            <span>End Call</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-1 p-4">
            <div class="mx-auto max-w-7xl">
                <div class="grid h-[calc(100vh-8rem)] grid-cols-1 gap-4 lg:grid-cols-4">
                    <div class="relative overflow-hidden rounded-lg bg-black lg:col-span-3">
                        <video ref="remoteVideo" autoplay playsinline class="h-full w-full object-cover"></video>

                        <div class="absolute top-4 right-4 h-36 w-48 overflow-hidden rounded-lg border-2 border-white bg-gray-800">
                            <video ref="localVideo" autoplay playsinline muted class="h-full w-full object-cover"></video>
                        </div>

                        <div v-if="callStatus !== 'connected'" class="bg-opacity-50 absolute inset-0 flex items-center justify-center bg-black">
                            <div class="text-center text-white">
                                <div class="mx-auto mb-4 h-12 w-12 animate-spin rounded-full border-b-2 border-white"></div>
                                <p class="text-lg">Connecting to {{ isDoctor ? 'patient' : 'doctor' }}...</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="rounded-lg bg-white p-4 shadow-sm">
                            <h3 class="mb-4 text-lg font-medium text-gray-900">Controls</h3>

                            <div class="space-y-3">
                                <button
                                    @click="toggleVideo"
                                    :class="[
                                        'flex w-full items-center justify-center space-x-2 rounded-lg px-4 py-2 transition-colors',
                                        isVideoEnabled ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-gray-600 text-white hover:bg-gray-700',
                                    ]"
                                >
                                    <svg v-if="isVideoEnabled" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
                                        ></path>
                                    </svg>
                                    <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"
                                        ></path>
                                    </svg>
                                    <span>{{ isVideoEnabled ? 'Turn Off Video' : 'Turn On Video' }}</span>
                                </button>

                                <button
                                    @click="toggleAudio"
                                    :class="[
                                        'flex w-full items-center justify-center space-x-2 rounded-lg px-4 py-2 transition-colors',
                                        isAudioEnabled ? 'bg-green-600 text-white hover:bg-green-700' : 'bg-gray-600 text-white hover:bg-gray-700',
                                    ]"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"
                                        ></path>
                                    </svg>
                                    <span>{{ isAudioEnabled ? 'Mute' : 'Unmute' }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Participant Info -->
                        <div class="rounded-lg bg-white p-4 shadow-sm">
                            <h3 class="mb-4 text-lg font-medium text-gray-900">Participants</h3>

                            <div class="space-y-3">
                                <!-- Doctor -->
                                <div class="flex items-center space-x-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100">
                                        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                            ></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">Dr. {{ doctor.name }}</p>
                                        <p class="text-sm text-gray-500">Doctor</p>
                                    </div>
                                    <div v-if="isDoctorConnected" class="ml-auto">
                                        <div class="h-3 w-3 rounded-full bg-green-500"></div>
                                    </div>
                                </div>

                                <!-- Patient -->
                                <div class="flex items-center space-x-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-100">
                                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                            ></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ patient.name }}</p>
                                        <p class="text-sm text-gray-500">Patient</p>
                                    </div>
                                    <div v-if="isPatientConnected" class="ml-auto">
                                        <div class="h-3 w-3 rounded-full bg-green-500"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
