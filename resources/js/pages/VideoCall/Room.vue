<template>
    <div class="min-h-screen bg-gray-100 py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-6 rounded-lg bg-white p-6 shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Video Call Room</h1>
                        <p class="text-sm text-gray-600">Room ID: {{ roomId }}</p>
                        <p v-if="user" class="text-sm text-gray-500">
                            Logged in as: {{ user.name }}
                            <span
                                :class="[
                                    'ml-2 inline-flex rounded-full px-2 py-1 text-xs font-medium',
                                    isDoctor ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800',
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
                                    ? 'bg-green-100 text-green-800'
                                    : connectionStatus === 'connecting'
                                      ? 'bg-yellow-100 text-yellow-800'
                                      : 'bg-red-100 text-red-800',
                            ]"
                        >
                            {{ connectionStatus }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Control Panel -->
            <div class="mb-6 rounded-lg bg-white p-6 shadow">
                <div class="flex flex-wrap justify-center gap-4">
                    <!-- Create Room - Only for Doctors -->
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
                    <div class="mb-4">
                        <input v-model="roomIdInput" placeholder="Enter Room ID" class="rounded border px-2 py-1 text-black" />
                        <button @click="setRoomId" class="ml-2 rounded bg-blue-500 px-3 py-1 text-black">Set Room</button>
                    </div>
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
                            isAudioEnabled ? 'bg-green-600 text-white hover:bg-green-700' : 'bg-gray-600 text-white hover:bg-gray-700',
                            !isInRoom ? 'cursor-not-allowed opacity-50' : '',
                        ]"
                    >
                        {{ isAudioEnabled ? 'Disable Audio' : 'Enable Audio' }}
                    </button>

                    <!-- Video Control -->
                    <button
                        @click="toggleVideo"
                        :disabled="!isInRoom"
                        :class="[
                            'rounded-lg px-6 py-3 font-medium transition-colors',
                            isVideoEnabled ? 'bg-green-600 text-white hover:bg-green-700' : 'bg-gray-600 text-white hover:bg-gray-700',
                            !isInRoom ? 'cursor-not-allowed opacity-50' : '',
                        ]"
                    >
                        {{ isVideoEnabled ? 'Disable Video' : 'Enable Video' }}
                    </button>

                    <!-- Screen Share Control - Only for Doctors -->
                    <button
                        v-if="isDoctor"
                        @click="toggleScreenShare"
                        :disabled="!isInRoom"
                        :class="[
                            'rounded-lg px-6 py-3 font-medium transition-colors',
                            isScreenSharing ? 'bg-purple-600 text-white hover:bg-purple-700' : 'bg-gray-600 text-white hover:bg-gray-700',
                            !isInRoom ? 'cursor-not-allowed opacity-50' : '',
                        ]"
                    >
                        {{ isScreenSharing ? 'Stop Sharing' : 'Share Screen' }}
                    </button>
                </div>
            </div>

            <!-- Video Grid -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Local Video -->
                <div class="rounded-lg bg-white p-4 shadow">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Your Video</h3>
                    <div class="relative aspect-video overflow-hidden rounded-lg bg-gray-900">
                        <video ref="localVideoRef" :srcObject="localVideoStream" autoplay muted playsinline class="h-full w-full object-cover" />
                        <div v-if="!localVideoStream" class="absolute inset-0 flex items-center justify-center">
                            <div class="text-center text-white">
                                <div class="mx-auto mb-2 flex h-16 w-16 items-center justify-center rounded-full bg-gray-600">
                                    <svg class="h-8 w-8" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <p class="text-sm">Video Disabled</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Screen Share -->
                <div v-if="localScreenStream" class="rounded-lg bg-white p-4 shadow">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Screen Share</h3>
                    <div class="relative aspect-video overflow-hidden rounded-lg bg-gray-900">
                        <video ref="localScreenRef" :srcObject="localScreenStream" autoplay muted playsinline class="h-full w-full object-cover" />
                    </div>
                </div>
            </div>

            <!-- Remote Participants -->
            <div v-if="remotePeers.length > 0" class="mt-6">
                <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Other Participants</h3>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div v-for="peer in remotePeers" :key="peer.peerId" class="relative aspect-video overflow-hidden rounded-lg bg-gray-900">
                            <!-- Video Stream -->
                            <video
                                v-if="peer.videoStream"
                                :ref="`remotePeer-${peer.peerId}-video`"
                                :srcObject="peer.videoStream"
                                autoplay
                                playsinline
                                class="h-full w-full object-cover"
                            />

                            <!-- Audio Stream -->
                            <audio v-if="peer.audioStream" :ref="`remotePeer-${peer.peerId}-audio`" :srcObject="peer.audioStream" autoplay />

                            <!-- Peer Info -->
                            <div class="bg-opacity-50 absolute bottom-2 left-2 rounded bg-black px-2 py-1 text-sm text-white">
                                Peer {{ peer.peerId.slice(-6) }}
                            </div>

                            <!-- No Video Placeholder -->
                            <div v-if="!peer.videoStream" class="absolute inset-0 flex items-center justify-center">
                                <div class="text-center text-white">
                                    <div class="mx-auto mb-2 flex h-16 w-16 items-center justify-center rounded-full bg-gray-600">
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

            <!-- Error Display -->
            <div v-if="errorMessage" class="mt-6">
                <div class="rounded-lg border border-red-200 bg-red-50 p-4">
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
                            <h3 class="text-sm font-medium text-red-800">Error</h3>
                            <p class="mt-1 text-sm text-red-700">{{ errorMessage }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Toast ref="toastRef" title="Room Created Successfully!" :description="`Room ID: ${roomId}`" variant="success" />
    </div>
</template>

<script setup lang="ts">
import Toast from '@/components/Toast.vue';
import { huddleClient } from '@/utils/huddle01Client';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onUnmounted, ref } from 'vue';
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

    // Show toast notification with room ID
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

// Join as guest
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

    // Decode token to check role and permissions
    const decodedToken = decodeToken(accessToken.value);
    if (decodedToken) {
        currentRole.value = decodedToken.role || 'guest';
    }

    await huddleClient.joinRoom({
        roomId: roomId.value,
        token: accessToken.value,
    });
    isInRoom.value = true;

    // Immediate peer discovery
    updateRemotePeersFromRoom();

    setupEventListeners();

    // Start peer discovery after a short delay
    setTimeout(() => {
        updateRemotePeersFromRoom();
    }, 2000);

    // Periodically sync peers from room.remotePeers
    const peerSyncInterval = setInterval(() => {
        if (isInRoom.value) {
            updateRemotePeersFromRoom();
        }
    }, 5000); // Check every 5 seconds

    // Store interval ID for cleanup
    (window as any).peerSyncInterval = peerSyncInterval;
};
const leaveRoom = async () => {
    if (!isInRoom.value) return;
    try {
        // Clean up peer sync interval
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

        if (isAudioEnabled.value) {
            await huddleClient.localPeer.disableAudio();
            isAudioEnabled.value = false;
        } else {
            await huddleClient.localPeer.enableAudio();
            isAudioEnabled.value = true;
        }
    } catch (err: any) {
        console.error('toggleAudio error:', err);
        errorMessage.value = err?.message || 'Failed to toggle audio';
    }
};

const toggleVideo = async () => {
    try {
        if (!isInRoom.value) return;

        if (isVideoEnabled.value) {
            await huddleClient.localPeer.disableVideo();
            localVideoStream.value = null;
            isVideoEnabled.value = false;
        } else {
            const stream = await huddleClient.localPeer.enableVideo();
            if (stream) {
                localVideoStream.value = stream;
                isVideoEnabled.value = true;
            }
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

    // Peer joined events
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
                    // Update remote peers from room
                    updateRemotePeersFromRoom();
                }
            });
        } catch {
            console.error(`Peer joined event ${eventName} not available`);
        }
    });

    // Lobby events for auto-admission
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

    // Check lobby peers periodically
    setInterval(checkLobbyPeers, 2000);

    // Peer left events
    const peerLeftEvents = ['peer-left', 'peerLeft', 'user-left', 'userLeft', 'participant-left'];
    peerLeftEvents.forEach((eventName) => {
        try {
            huddleClient.room.on(eventName as any, (peer: any) => {
                if (peer?.peerId) {
                    remotePeers.value = remotePeers.value.filter((p) => p.peerId !== peer.peerId);
                }
            });
        } catch {
            // Event not available
        }
    });

    // Stream events
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
            // Event not available
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
            // Event not available
        }
    });
};

// Update remote peers from room.remotePeers (v2 API)
const updateRemotePeersFromRoom = () => {
    try {
        if (huddleClient.room.remotePeers) {
            // In v2, remotePeers is a Map<string, RemotePeer>
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
                // Try to iterate as object
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
        // Failed to update remote peers
    }
};

// Function to admit lobby peers (for hosts)
const admitLobbyPeer = async (peerId: string) => {
    try {
        // Use the admitPeer method to move peer from lobby to main room
        if (typeof (huddleClient.room as any).admitPeer === 'function') {
            await (huddleClient.room as any).admitPeer(peerId);
        }
    } catch {
        // Failed to admit peer
    }
};

onUnmounted(async () => {
    if (isInRoom.value) {
        await leaveRoom();
    }
});
</script>
