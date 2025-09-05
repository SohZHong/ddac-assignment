<script setup lang="ts">
import Toast from '@/components/Toast.vue';
import { huddleClient } from '@/utils/huddle01Client';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onUnmounted, ref } from 'vue';
interface Props {
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
        role: 'doctor' | 'patient';
    };
}

const props = defineProps<Props>();
const { props: pageProps } = usePage();
const user = computed(() => pageProps.auth?.user);
const isDoctor = computed(() => pageProps.auth?.user?.role === '2');

const isLoading = ref(false);
const isInRoom = ref(false);
const connectionStatus = ref('disconnected');
const errorMessage = ref('');

const isAudioEnabled = ref(false);
const isVideoEnabled = ref(false);
const isScreenSharing = ref(false);

const localVideoStream = ref<MediaStream | null>(null);
const localScreenStream = ref<MediaStream | null>(null);
const remotePeers = ref<
    Array<{
        peerId: string;
        videoStream: MediaStream | null;
        audioStream: MediaStream | null;
    }>
>([]);

const localVideoRef = ref(null);
const localScreenRef = ref(null);
const toastRef = ref(null);

const roomId = ref('');
const accessToken = ref('');
const roomIdInput = ref('');
const currentRole = ref<'host' | 'guest'>('guest');

const setRoomId = () => {
    if (roomIdInput.value.trim()) {
        roomId.value = roomIdInput.value.trim();
    }
};

const decodeToken = (token: string) => {
    try {
        const payload = token.split('.')[1];
        const decoded = JSON.parse(atob(payload));
        return decoded;
    } catch {
        return null;
    }
};

const createRoom = async () => {
    const res = await fetch('https://huddle-token-server.vercel.app/create-room', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
    });
    const data = await res.json();
    roomId.value = data.roomId;

    if (toastRef.value) {
        (toastRef.value as any).showToast();
    }

    const tokenRes = await fetch('https://huddle-token-server.vercel.app/get-token', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ roomId: roomId.value, role: 'host' }),
    });
    const tokenData = await tokenRes.json();
    accessToken.value = tokenData.accessToken;

    const decodedToken = decodeToken(accessToken.value);
    if (decodedToken) {
        currentRole.value = decodedToken.role || 'host';
    }

    try {
        await axios.post('/api/video-calls/notifications/meeting-link', {
            room_id: roomId.value,
            doctor_id: props.doctor.id,
            patient_id: props.patient.id,
            doctor_name: props.doctor.name,
            patient_name: props.patient.name,
        });

        if (toastRef.value) {
            (toastRef.value as any).showToast();
        }
    } catch (error) {
        console.error('Error creating room:', error);
        errorMessage.value = error?.message || 'Failed to create room';
    }

    await joinRoom();
};

const joinRoom = async () => {
    if (!roomId.value) return;

    if (!accessToken.value) {
        const tokenRes = await fetch('https://huddle-token-server.vercel.app/get-token', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ roomId: roomId.value, role: 'guest' }),
        });
        const tokenData = await tokenRes.json();
        accessToken.value = tokenData.accessToken;
        currentRole.value = 'guest';
    }

    const decodedToken = decodeToken(accessToken.value);
    if (decodedToken) {
        currentRole.value = decodedToken.role || 'guest';
    }

    await huddleClient.joinRoom({
        roomId: roomId.value,
        token: accessToken.value,
    });
    isInRoom.value = true;

    updateRemotePeersFromRoom();

    setupEventListeners();

    setTimeout(() => {
        updateRemotePeersFromRoom();
    }, 2000);

    const peerSyncInterval = setInterval(() => {
        if (isInRoom.value) {
            updateRemotePeersFromRoom();
        }
    }, 5000);

    (window as any).peerSyncInterval = peerSyncInterval;
};
const leaveRoom = async () => {
    if (!isInRoom.value) return;
    try {
        if ((window as any).peerSyncInterval) {
            clearInterval((window as any).peerSyncInterval);
            (window as any).peerSyncInterval = null;
        }

        await huddleClient.leaveRoom();
        isInRoom.value = false;
        connectionStatus.value = 'disconnected';
        localVideoStream.value = null;
        localScreenStream.value = null;
        remotePeers.value = [];
        isAudioEnabled.value = false;
        isVideoEnabled.value = false;
        isScreenSharing.value = false;
    } catch (err: any) {
        console.error('Leave room error:', err);
        errorMessage.value = err?.message || 'Failed to leave room';
    }
};

const toggleAudio = async () => {
    try {
        if (!isInRoom.value) return;
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            throw new Error('Microphone access is not available in this environment');
        }

        if (!isAudioEnabled.value) {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                stream.getTracks().forEach((track) => track.stop());
            } catch (err) {
                errorMessage.value = 'Microphone permission denied. Please allow microphone access and try again.';
                return;
            }
            await huddleClient.localPeer.enableAudio();
            isAudioEnabled.value = true;
        } else {
            await huddleClient.localPeer.disableAudio();
            isAudioEnabled.value = false;
        }
    } catch (err: any) {
        console.error('toggleAudio error:', err);
        errorMessage.value = err?.message || 'Failed to toggle audio';
    }
};

const toggleVideo = async () => {
    try {
        if (!isInRoom.value) return;
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            throw new Error('Camera access is not available in this environment');
        }

        if (!isVideoEnabled.value) {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                stream.getTracks().forEach((track) => track.stop());
            } catch (err) {
                errorMessage.value = 'Camera permission denied. Please allow camera access and try again.';
                return;
            }

            const stream = await huddleClient.localPeer.enableVideo();
            if (stream) {
                localVideoStream.value = stream;
                isVideoEnabled.value = true;
            }
            isVideoEnabled.value = true;
        } else {
            await huddleClient.localPeer.disableVideo();
            isVideoEnabled.value = false;
        }
    } catch (err: any) {
        console.error('toggleVideo error:', err);
        errorMessage.value = err?.message || 'Failed to toggle video';
    }
};

const toggleScreenShare = async () => {
    try {
        if (!isInRoom.value) return;

        if (isScreenSharing.value) {
            await huddleClient.localPeer.stopScreenShare();
            localScreenStream.value = null;
            isScreenSharing.value = false;
        } else {
            const stream = await huddleClient.localPeer.startScreenShare();
            if (stream) {
                localScreenStream.value = stream;
                isScreenSharing.value = true;
            }
        }
    } catch (err: any) {
        console.error('toggleScreenShare error:', err);
        errorMessage.value = err?.message || 'Failed to toggle screen share';
    }
};

const setupEventListeners = () => {
    try {
        huddleClient.room.on('room-joined' as any, () => {
            connectionStatus.value = 'connected';
        });

        huddleClient.room.on('room-left' as any, () => {
            connectionStatus.value = 'disconnected';
        });

        huddleClient.room.on('room-connecting' as any, () => {
            connectionStatus.value = 'connecting';
        });

        huddleClient.room.on('room-failed' as any, () => {
            connectionStatus.value = 'failed';
            errorMessage.value = 'Failed to connect to room';
        });
    } catch {
        console.error('Event listener error');
    }

    const peerJoinedEvents = ['new-peer-joined', 'peer-joined', 'peerJoined'];
    peerJoinedEvents.forEach((eventName) => {
        try {
            huddleClient.room.on(eventName as any, (peer: any) => {
                if (peer?.peerId) {
                    const existingPeer = remotePeers.value.find((p) => p.peerId === peer.peerId);
                    if (!existingPeer) {
                        remotePeers.value.push({
                            peerId: peer.peerId,
                            videoStream: null,
                            audioStream: null,
                        });
                    }
                } else {
                    updateRemotePeersFromRoom();
                }
            });
        } catch {
            console.error(`Peer joined event ${eventName} not available`);
        }
    });

    const lobbyEvents = ['lobby-peers-updated', 'lobby-peer-joined', 'lobby-peer-left'];
    lobbyEvents.forEach((eventName) => {
        try {
            huddleClient.room.on(eventName as any, () => {
                if (eventName === 'lobby-peers-updated') {
                    const lobbyPeerIds = (huddleClient.room as any).lobbyPeerIds || [];

                    if (currentRole.value === 'host' && lobbyPeerIds.length > 0) {
                        lobbyPeerIds.forEach((peerId: string) => {
                            admitLobbyPeer(peerId);
                        });
                    }
                }
            });
        } catch {
            console.error(`Lobby event ${eventName} not available`);
        }
    });

    const checkLobbyPeers = () => {
        try {
            const lobbyPeerIds = (huddleClient.room as any).lobbyPeerIds || [];

            if (lobbyPeerIds.length > 0 && currentRole.value === 'host') {
                lobbyPeerIds.forEach((peerId: string) => {
                    admitLobbyPeer(peerId);
                });
            }
        } catch {
            console.error('Lobby check failed');
        }
    };

    setInterval(checkLobbyPeers, 2000);

    const peerLeftEvents = ['peer-left', 'peerLeft', 'user-left', 'userLeft', 'participant-left'];
    peerLeftEvents.forEach((eventName) => {
        try {
            huddleClient.room.on(eventName as any, (peer: any) => {
                if (peer?.peerId) {
                    remotePeers.value = remotePeers.value.filter((p) => p.peerId !== peer.peerId);
                }
            });
        } catch {
            console.error('Event unavailable');
        }
    });

    const streamAddedEvents = ['stream-added', 'streamAdded', 'track-added', 'trackAdded'];
    streamAddedEvents.forEach((eventName) => {
        try {
            huddleClient.room.on(eventName as any, (data: any) => {
                const { peerId, label } = data;
                if (peerId && label) {
                    const remotePeer = huddleClient.room.getRemotePeerById(peerId);

                    if (remotePeer) {
                        const consumer = remotePeer.getConsumer(label);

                        if (consumer?.track) {
                            const track = consumer.track;
                            const stream = new MediaStream([track]);
                            let peerObj = remotePeers.value.find((p) => p.peerId === peerId);

                            if (!peerObj) {
                                peerObj = { peerId, videoStream: null, audioStream: null };
                                remotePeers.value.push(peerObj);
                            }

                            if (label === 'video') {
                                peerObj.videoStream = stream;
                            }
                            if (label === 'audio') {
                                peerObj.audioStream = stream;
                            }
                        }
                    }
                }
            });
        } catch {
            console.error('Event unavailable');
        }
    });

    // Stream closed events
    const streamClosedEvents = ['stream-closed', 'streamClosed', 'track-closed', 'trackClosed'];
    streamClosedEvents.forEach((eventName) => {
        try {
            huddleClient.room.on(eventName as any, (data: any) => {
                const { peerId, label } = data;
                const peerObj = remotePeers.value.find((p) => p.peerId === peerId);
                if (peerObj) {
                    if (label === 'video') {
                        if (peerObj.videoStream) {
                            peerObj.videoStream.getTracks().forEach((track: MediaStreamTrack) => track.stop());
                        }
                        peerObj.videoStream = null;
                    }
                    if (label === 'audio') {
                        if (peerObj.audioStream) {
                            peerObj.audioStream.getTracks().forEach((track: MediaStreamTrack) => track.stop());
                        }
                        peerObj.audioStream = null;
                    }
                }
            });
        } catch {
            console.error('Stream event closed not available');
        }
    });
};

const updateRemotePeersFromRoom = () => {
    try {
        if (huddleClient.room.remotePeers) {
            if (huddleClient.room.remotePeers instanceof Map) {
                huddleClient.room.remotePeers.forEach((remotePeer, peerId) => {
                    const existingPeer = remotePeers.value.find((p) => p.peerId === peerId);
                    if (!existingPeer) {
                        remotePeers.value.push({
                            peerId,
                            videoStream: null,
                            audioStream: null,
                        });
                    }
                });
            } else {
                Object.entries(huddleClient.room.remotePeers as any).forEach(([peerId]) => {
                    const existingPeer = remotePeers.value.find((p) => p.peerId === peerId);
                    if (!existingPeer) {
                        remotePeers.value.push({
                            peerId,
                            videoStream: null,
                            audioStream: null,
                        });
                    }
                });
            }
        }
    } catch {
        console.error('Failed to update remote peers');
    }
};

const admitLobbyPeer = async (peerId: string) => {
    try {
        if (typeof (huddleClient.room as any).admitPeer === 'function') {
            await (huddleClient.room as any).admitPeer(peerId);
        }
    } catch {
        console.error('Failed to admit peer');
    }
};

onUnmounted(async () => {
    if (isInRoom.value) {
        await leaveRoom();
    }
});
</script>

<template>
    <div class="min-h-screen bg-gray-900 py-6">
        <div class="mx-auto flex max-w-7xl flex-col px-4 sm:px-6 lg:px-8">
            <div class="mb-6 rounded-lg border border-gray-700 bg-gray-800 p-6 shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-white">Video Call Room</h1>
                        <p class="text-sm text-gray-300">Room ID: {{ roomId }}</p>
                        <p v-if="user" class="text-sm text-gray-400">
                            Logged in as: {{ user.name }}
                            <span
                                :class="[
                                    'ml-2 inline-flex rounded-full px-2 py-1 text-xs font-medium',
                                    isDoctor ? 'bg-blue-600 text-blue-100' : 'bg-green-600 text-green-100',
                                ]"
                            >
                                {{ isDoctor ? 'Doctor' : 'Patient' }}
                            </span>
                        </p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span
                            :class="[
                                'rounded-full px-3 py-1 text-sm font-medium',
                                connectionStatus === 'connected'
                                    ? 'bg-green-600 text-green-100'
                                    : connectionStatus === 'connecting'
                                      ? 'bg-yellow-600 text-yellow-100'
                                      : 'bg-red-600 text-red-100',
                            ]"
                        >
                            {{ connectionStatus }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="mb-4 self-end">
                <input
                    v-model="roomIdInput"
                    placeholder="Enter Room ID"
                    class="rounded border border-gray-600 bg-gray-700 px-2 py-1 text-white placeholder-gray-400"
                />
                <button @click="setRoomId" class="ml-2 max-h-full rounded bg-blue-600 px-3 py-1 text-white hover:bg-blue-700">Set Room</button>
            </div>
            <div class="mb-6 rounded-lg border border-gray-700 bg-gray-800 p-6 shadow-lg">
                <div class="flex flex-wrap justify-center gap-4">
                    <button
                        v-if="isDoctor"
                        @click="createRoom"
                        :disabled="isLoading"
                        :class="[
                            'rounded-lg px-6 py-3 font-medium transition-colors',
                            'bg-blue-600 text-white hover:bg-blue-700',
                            isLoading ? 'cursor-not-allowed opacity-50' : '',
                        ]"
                    >
                        Create Room
                    </button>

                    <button
                        @click="isInRoom ? leaveRoom() : joinRoom()"
                        :disabled="isLoading"
                        :class="[
                            'rounded-lg px-6 py-3 font-medium transition-colors',
                            isInRoom ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-blue-600 text-white hover:bg-blue-700',
                            isLoading ? 'cursor-not-allowed opacity-50' : '',
                        ]"
                    >
                        {{ isInRoom ? 'Leave Room' : 'Join Room' }}
                    </button>

                    <button
                        @click="toggleAudio"
                        :disabled="!isInRoom"
                        :class="[
                            'rounded-lg px-6 py-3 font-medium transition-colors',
                            isAudioEnabled ? 'bg-green-600 text-white hover:bg-green-700' : 'bg-gray-600 text-white hover:bg-gray-500',
                            !isInRoom ? 'cursor-not-allowed opacity-50' : '',
                        ]"
                    >
                        {{ isAudioEnabled ? 'Disable Audio' : 'Enable Audio' }}
                    </button>

                    <button
                        @click="toggleVideo"
                        :disabled="!isInRoom"
                        :class="[
                            'rounded-lg px-6 py-3 font-medium transition-colors',
                            isVideoEnabled ? 'bg-green-600 text-white hover:bg-green-700' : 'bg-gray-600 text-white hover:bg-gray-500',
                            !isInRoom ? 'cursor-not-allowed opacity-50' : '',
                        ]"
                    >
                        {{ isVideoEnabled ? 'Disable Video' : 'Enable Video' }}
                    </button>

                    <button
                        v-if="isDoctor"
                        @click="toggleScreenShare"
                        :disabled="!isInRoom"
                        :class="[
                            'rounded-lg px-6 py-3 font-medium transition-colors',
                            isScreenSharing ? 'bg-purple-600 text-white hover:bg-purple-700' : 'bg-gray-600 text-white hover:bg-gray-500',
                            !isInRoom ? 'cursor-not-allowed opacity-50' : '',
                        ]"
                    >
                        {{ isScreenSharing ? 'Stop Sharing' : 'Share Screen' }}
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="rounded-lg border border-gray-700 bg-gray-800 p-4 shadow-lg">
                    <h3 class="mb-4 text-lg font-medium text-white">Your Video</h3>
                    <div class="relative aspect-video overflow-hidden rounded-lg border border-gray-700 bg-gray-900">
                        <video ref="localVideoRef" :srcObject="localVideoStream" autoplay muted playsinline class="h-full w-full object-cover" />
                        <div v-if="!localVideoStream" class="absolute inset-0 flex items-center justify-center">
                            <div class="text-center text-gray-300">
                                <div class="mx-auto mb-2 flex h-16 w-16 items-center justify-center rounded-full bg-gray-700">
                                    <svg class="h-8 w-8" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <p class="text-sm">Video Disabled</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="localScreenStream" class="rounded-lg border border-gray-700 bg-gray-800 p-4 shadow-lg">
                    <h3 class="mb-4 text-lg font-medium text-white">Screen Share</h3>
                    <div class="relative aspect-video overflow-hidden rounded-lg border border-gray-700 bg-gray-900">
                        <video ref="localScreenRef" :srcObject="localScreenStream" autoplay muted playsinline class="h-full w-full object-cover" />
                    </div>
                </div>
            </div>

            <div v-if="remotePeers.length > 0" class="mt-6">
                <div class="rounded-lg border border-gray-700 bg-gray-800 p-6 shadow-lg">
                    <h3 class="mb-4 text-lg font-medium text-white">Other Participants</h3>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="peer in remotePeers"
                            :key="peer.peerId"
                            class="relative aspect-video overflow-hidden rounded-lg border border-gray-700 bg-gray-900"
                        >
                            <video
                                v-if="peer.videoStream"
                                :ref="`remotePeer-${peer.peerId}-video`"
                                :srcObject="peer.videoStream"
                                autoplay
                                playsinline
                                class="h-full w-full object-cover"
                            />

                            <audio v-if="peer.audioStream" :ref="`remotePeer-${peer.peerId}-audio`" :srcObject="peer.audioStream" autoplay />

                            <div
                                class="bg-opacity-75 absolute bottom-2 left-2 rounded border border-gray-600 bg-gray-800 px-2 py-1 text-sm text-white"
                            >
                                Peer {{ peer.peerId.slice(-6) }}
                            </div>

                            <div v-if="!peer.videoStream" class="absolute inset-0 flex items-center justify-center">
                                <div class="text-center text-gray-300">
                                    <div class="mx-auto mb-2 flex h-16 w-16 items-center justify-center rounded-full bg-gray-700">
                                        <svg class="h-8 w-8" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <p class="text-sm">No Video</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="errorMessage" class="mt-6">
                <div class="rounded-lg border border-red-600 bg-red-900 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-300">Error</h3>
                            <p class="mt-1 text-sm text-red-200">{{ errorMessage }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Toast ref="toastRef" title="Room Created Successfully!" :description="`Room ID: ${roomId}`" variant="success" />
    </div>
</template>
