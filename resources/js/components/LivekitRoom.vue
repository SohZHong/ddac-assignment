<template>
    <div class="livekit-room">
        <div class="room-header">
            <h2 class="text-xl font-semibold">{{ eventTitle }}</h2>
            <div class="room-status">
                <span :class="statusClass">{{ roomStatus }}</span>
            </div>
        </div>

        <!-- Error Message -->
        <div v-if="errorMessage" class="error-message">
            {{ errorMessage }}
        </div>

        <div class="room-content">
            <!-- Video Grid -->
            <div class="video-container">
                <div class="video-grid">
                    <!-- Local Participant (only show for publishers) -->
                    <div v-if="canPublish && localParticipant" :key="localParticipant.identity" class="video-item">
                        <video
                            :ref="`video-${localParticipant.identity}`"
                            :id="`video-${localParticipant.identity}`"
                            autoplay
                            muted
                            playsinline
                            style="
                                width: 100%;
                                height: 100%;
                                object-fit: cover;
                                background: #000;
                                border: 2px solid red;
                                min-height: 200px;
                                display: block;
                                visibility: visible;
                                opacity: 1;
                                z-index: 9999;
                                position: relative;
                            "
                        ></video>
                        <div v-if="!localParticipant.isCameraEnabled && !localParticipant.isScreenSharing" class="video-placeholder">
                            <div class="placeholder-avatar">
                                {{ localParticipant.name?.charAt(0) || localParticipant.identity?.charAt(0) || '?' }}
                            </div>
                        </div>
                        <div class="participant-info">
                            <span class="participant-name">{{ localParticipant.name || localParticipant.identity }} (You)</span>
                            <div class="participant-status">
                                <span v-if="localParticipant.isSpeaking" class="speaking-indicator">🔊</span>
                                <span v-if="localParticipant.isScreenSharing" class="screen-share">🖥️</span>
                                <span v-if="!localParticipant.isCameraEnabled && !localParticipant.isScreenSharing" class="camera-off">📷</span>
                                <span v-if="!localParticipant.isMicrophoneEnabled" class="mic-off">🎤</span>
                            </div>
                        </div>
                    </div>

                    <!-- Remote Participants -->
                    <div v-for="participant in remoteParticipants" :key="participant.identity" class="video-item">
                        <video :ref="`video-${participant.identity}`" :id="`video-${participant.identity}`" autoplay playsinline></video>
                        <div v-if="!participant.isCameraEnabled && !participant.isScreenSharing" class="video-placeholder">
                            <div class="placeholder-avatar">{{ participant.name?.charAt(0) || participant.identity?.charAt(0) || '?' }}</div>
                        </div>
                        <div class="participant-info">
                            <span class="participant-name">{{ participant.name || participant.identity }}</span>
                            <div class="participant-status">
                                <span v-if="participant.isSpeaking" class="speaking-indicator">🔊</span>
                                <span v-if="participant.isScreenSharing" class="screen-share">🖥️</span>
                                <span v-if="!participant.isCameraEnabled && !participant.isScreenSharing" class="camera-off">📷</span>
                                <span v-if="!participant.isMicrophoneEnabled" class="mic-off">🎤</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Controls -->
                <div class="controls">
                    <!-- Streaming controls - only show for users who can publish (event creators) -->
                    <button v-if="canPublish" @click="toggleMicrophone" :class="['control-btn', { active: isMicrophoneEnabled }]">
                        {{ isMicrophoneEnabled ? '🔇' : '🎤' }}
                    </button>
                    <button v-if="canPublish" @click="toggleCamera" :class="['control-btn', { active: isCameraEnabled }]">
                        {{ isCameraEnabled ? '🚫' : '📷' }}
                    </button>
                    <button v-if="canPublish" @click="toggleScreenShare" :class="['control-btn', { active: isScreenSharing }]">
                        {{ isScreenSharing ? '📺' : '🖥️' }}
                    </button>

                    <!-- Viewer indicator for users who can't publish -->
                    <span v-if="!canPublish" class="viewer-indicator">👁️ Viewer Mode</span>

                    <!-- Join audio button to satisfy autoplay policies -->
                    <button v-if="!hasStartedAudio" @click="joinAudio" class="control-btn">🔊 Join Audio</button>

                    <button @click="leaveRoom" class="control-btn leave-btn">❌ Leave</button>
                    <div class="connection-status">
                        <span :class="['status-indicator', statusClass]">{{ roomStatus }}</span>
                    </div>
                </div>
            </div>

            <!-- Chat -->
            <div class="chat-container">
                <!-- Debug info -->
                <div class="debug-info" style="padding: 0.5rem; background: #333; font-size: 0.75rem; color: #ccc">
                    Messages: {{ chatMessages.length }} | Array: {{ Array.isArray(chatMessages) ? 'Yes' : 'No' }}
                    <button
                        @click="addTestMessage"
                        style="
                            margin-left: 0.5rem;
                            padding: 0.25rem 0.5rem;
                            background: #666;
                            color: white;
                            border: none;
                            border-radius: 0.25rem;
                            cursor: pointer;
                        "
                    >
                        Add Test
                    </button>
                </div>

                <div class="chat-messages" ref="chatMessagesRef">
                    <div v-for="message in chatMessages" :key="message.id" class="chat-message">
                        <span class="message-sender">{{ message.sender }}:</span>
                        <span class="message-text">{{ message.text }}</span>
                    </div>
                </div>
                <div class="chat-input">
                    <input v-model="newMessage" @keyup.enter="sendMessage" placeholder="Type a message..." class="message-input" />
                    <button @click="sendMessage" class="send-btn">Send</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Room, RoomEvent, Track } from 'livekit-client';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    roomId: {
        type: Number,
        required: true,
    },
    eventTitle: {
        type: String,
        default: 'Livestream',
    },
});

const localParticipant = ref(null);
const remoteParticipants = ref([]);
const newMessage = ref('');
const isMicrophoneEnabled = ref(false);
const isCameraEnabled = ref(false);
const isScreenSharing = ref(false);
const roomStatus = ref('Connecting...');
const chatMessages = ref([]);

// Ensure chatMessages is always an array
const ensureChatMessagesArray = () => {
    if (!Array.isArray(chatMessages.value)) {
        console.log('chatMessages is not an array, reinitializing...');
        chatMessages.value = [];
    }
};

const chatMessagesRef = ref(null);
const errorMessage = ref('');

let livekitRoom = null;
const serverCanPublish = ref(false);
const participantIdentity = ref('');
const hasStartedAudio = ref(false);

const statusClass = computed(() => {
    switch (roomStatus.value) {
        case 'Connected':
            return 'status-connected';
        case 'Connecting...':
            return 'status-connecting';
        case 'Disconnected':
            return 'status-disconnected';
        default:
            return 'status-unknown';
    }
});

// Save chat messages to localStorage
const saveChatMessages = () => {
    try {
        localStorage.setItem(`chat-${props.roomId}`, JSON.stringify(chatMessages.value));
    } catch (error) {
        console.error('Failed to save chat messages:', error);
    }
};

// Load chat messages from localStorage
const loadChatMessages = () => {
    try {
        const saved = localStorage.getItem(`chat-${props.roomId}`);
        if (saved) {
            const parsed = JSON.parse(saved);
            if (Array.isArray(parsed)) {
                chatMessages.value = parsed;
                console.log('Loaded chat messages from localStorage:', parsed.length);
            }
        }
    } catch (error) {
        console.error('Failed to load chat messages:', error);
    }
};

// Watch for changes in chat messages and save to localStorage
watch(
    chatMessages,
    () => {
        saveChatMessages();
    },
    { deep: true },
);

// Check if user can publish (event creators can stream)
const canPublish = computed(() => {
    const sdkFlag = livekitRoom?.localParticipant?.canPublish || false;
    const resolved = serverCanPublish.value || sdkFlag;

    if (livekitRoom?.localParticipant) {
        console.log('Local participant:', livekitRoom.localParticipant);
        console.log('Can publish (SDK):', sdkFlag);
        console.log('Can publish (server):', serverCanPublish.value);
        console.log('Can publish (resolved):', resolved);
    }

    return resolved;
});

// Add a watcher to update permissions when room state changes
watch(
    () => livekitRoom?.localParticipant,
    (participant) => {
        if (participant) {
            console.log('Participant updated:', participant);
            console.log('Can publish:', participant.canPublish);

            // Update UI based on permissions
            if (!participant.canPublish) {
                isCameraEnabled.value = false;
                isMicrophoneEnabled.value = false;
                isScreenSharing.value = false;
            }
        }
    },
    { immediate: true },
);

onMounted(async () => {
    // Ensure chatMessages is properly initialized
    ensureChatMessagesArray();
    loadChatMessages(); // Load messages on mount

    await connectToRoom();
});

onUnmounted(() => {
    if (livekitRoom) {
        livekitRoom.disconnect();
    }
    saveChatMessages(); // Save messages on unmount
});

const connectToRoom = async () => {
    try {
        // Get token from backend
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const response = await fetch(`/api/livekit/rooms/${props.roomId}/join`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                ...(csrfToken && { 'X-CSRF-TOKEN': csrfToken }),
            },
        });

        if (!response.ok) {
            throw new Error('Failed to get room access');
        }

        const { token, room_name, participant_identity, ws_url, can_publish } = await response.json();

        // Debug: Log token details
        console.log('Token received:', token);
        console.log('Room name:', room_name);
        console.log('Participant identity:', participant_identity);
        console.log('WS URL:', ws_url);
        console.log('can_publish (server):', can_publish);

        serverCanPublish.value = !!can_publish;
        participantIdentity.value = participant_identity || '';

        // Create LiveKit room
        livekitRoom = new Room({
            adaptiveStream: true,
            dynacast: true,
        });

        // Set up event listeners BEFORE connecting
        setupEventListeners();

        // Pre-warm connection (this speeds up the actual connection)
        console.log('Preparing connection...');
        const connectUrl = ws_url || import.meta.env.VITE_LIVEKIT_URL || '';
        if (!connectUrl) {
            throw new Error('Missing LiveKit URL');
        }
        await livekitRoom.prepareConnection(connectUrl, token);

        // Connect to room
        console.log('Connecting to room...');
        await livekitRoom.connect(connectUrl, token, {
            timeout: 15000, // 15 second timeout
        });

        roomStatus.value = 'Connected';
        errorMessage.value = '';

        // Start audio (required for user interaction)
        try {
            await livekitRoom.startAudio();
            console.log('Audio started successfully');
        } catch (audioError) {
            console.warn('Failed to start audio:', audioError);
            // This is not critical, continue
        }

        // Update participants list
        updateParticipants();

        // Debug: Check if video element exists
        setTimeout(() => {
            if (livekitRoom.localParticipant) {
                const videoElement = document.getElementById(`video-${livekitRoom.localParticipant.identity}`);
                console.log('Local video element exists:', !!videoElement);
                console.log('Local participant identity:', livekitRoom.localParticipant.identity);

                // Check for existing local tracks and attach them
                livekitRoom.localParticipant.getTrackPublications().forEach((publication) => {
                    if (publication.track?.kind === Track.Kind.Video) {
                        handleLocalTrackPublished(publication);
                    }
                });
            }
        }, 1000);
    } catch (error) {
        console.error('Failed to connect to room:', error);
        roomStatus.value = 'Connection failed';
        errorMessage.value = `Failed to connect: ${error.message}`;
    }
};

const setupEventListeners = () => {
    if (!livekitRoom) return;

    // Connection events
    livekitRoom.on(RoomEvent.Connected, handleConnected);
    livekitRoom.on(RoomEvent.Connecting, handleConnecting);
    livekitRoom.on(RoomEvent.ConnectionStateChanged, handleConnectionStateChanged);
    livekitRoom.on(RoomEvent.Disconnected, handleDisconnected);

    // Track events
    livekitRoom.on(RoomEvent.TrackSubscribed, handleTrackSubscribed);
    livekitRoom.on(RoomEvent.TrackUnsubscribed, handleTrackUnsubscribed);
    livekitRoom.on(RoomEvent.LocalTrackPublished, handleLocalTrackPublished);
    livekitRoom.on(RoomEvent.LocalTrackUnpublished, handleLocalTrackUnpublished);

    // Participant events
    livekitRoom.on(RoomEvent.ParticipantConnected, updateParticipants);
    livekitRoom.on(RoomEvent.ParticipantDisconnected, updateParticipants);

    // Data events
    livekitRoom.on(RoomEvent.DataReceived, handleDataReceived);
};

const enableCameraAndMicrophone = async () => {
    if (!livekitRoom?.localParticipant) {
        console.log('No local participant available');
        return;
    }

    try {
        // Enable camera
        await livekitRoom.localParticipant.setCameraEnabled(true);
        isCameraEnabled.value = true;
        console.log('Camera enabled successfully');

        // Enable microphone
        await livekitRoom.localParticipant.setMicrophoneEnabled(true);
        isMicrophoneEnabled.value = true;
        console.log('Microphone enabled successfully');

        // Attach video track after enabling
        setTimeout(() => {
            attachLocalVideoTrack();
        }, 500);
    } catch (error) {
        console.error('Failed to enable camera and microphone:', error);
    }
};

const handleConnected = async () => {
    console.log('Room connected successfully');
    roomStatus.value = 'Connected';
    errorMessage.value = '';

    // Load chat history once connected
    try {
        await loadChatHistory();
    } catch (err) {
        console.error('Failed to load chat history:', err);
    }

    // Ensure chatMessages is an array before adding welcome message
    ensureChatMessagesArray();

    // Add a welcome message to test chat functionality
    chatMessages.value.push({
        id: Date.now(),
        sender: 'System',
        text: 'Welcome to the livestream! Chat is now active.',
        timestamp: new Date(),
    });
    console.log('Welcome message added, chat messages count:', chatMessages.value.length);

    // Do not call startAudio() here; we already attempted after connect.

    // Debug: Log participant permissions
    console.log('=== PARTICIPANT PERMISSIONS DEBUG ===');
    console.log('Local participant:', livekitRoom.localParticipant);
    console.log('Can publish (direct):', livekitRoom.localParticipant.canPublish);
    console.log('Can subscribe (direct):', livekitRoom.localParticipant.canSubscribe);
    console.log('Can publish data (direct):', livekitRoom.localParticipant.canPublishData);
    console.log('Participant identity:', livekitRoom.localParticipant.identity);
    console.log('Permissions object:', livekitRoom.localParticipant.permissions);
    console.log('=====================================');

    // Check if user has permission to publish (only event creators can stream)
    const userCanPublish = livekitRoom.localParticipant.canPublish;
    console.log('User can publish:', userCanPublish);

    if (userCanPublish) {
        // Only enable camera and microphone for users who can publish (event creators)
        setTimeout(async () => {
            try {
                if (!isCameraEnabled.value) {
                    await enableCameraAndMicrophone();
                }
            } catch (error) {
                console.error('Failed to enable camera after connection:', error);
            }
        }, 1000);
    } else {
        // For regular users (viewers), disable controls and show appropriate UI
        console.log('User is a viewer - camera and microphone controls disabled');
        isCameraEnabled.value = false;
        isMicrophoneEnabled.value = false;
        isScreenSharing.value = false;
    }
};

const handleConnecting = () => {
    console.log('Connecting to room...');
    roomStatus.value = 'Connecting...';
};

const handleConnectionStateChanged = (state) => {
    console.log('Connection state changed:', state);
    console.log('Room connection state:', livekitRoom?.connectionState);
    console.log('Room state:', livekitRoom?.state);

    // Use room.state as the primary connection indicator
    const roomState = livekitRoom?.state;
    console.log('Using room state:', roomState);

    switch (roomState) {
        case 'connected':
            roomStatus.value = 'Connected';
            errorMessage.value = '';
            break;
        case 'connecting':
            roomStatus.value = 'Connecting...';
            break;
        case 'disconnected':
            roomStatus.value = 'Disconnected';
            break;
        case 'reconnecting':
            roomStatus.value = 'Reconnecting...';
            break;
        default:
            roomStatus.value = roomState || state;
    }
};

const handleDisconnected = () => {
    console.log('Room disconnected');
    roomStatus.value = 'Disconnected';
};

const handleLocalTrackPublished = (publication) => {
    console.log('Local track published:', publication.track?.kind, publication.track?.source);

    if (publication.track?.kind === Track.Kind.Video) {
        nextTick(() => {
            const videoElement = document.getElementById(`video-${livekitRoom.localParticipant.identity}`);
            if (videoElement) {
                publication.track.attach(videoElement);
                console.log('Local video element attached');
                const v = /** @type {HTMLVideoElement} */ videoElement;
                if (typeof v.play === 'function') {
                    v.play().catch(() => {});
                }
            } else {
                console.warn('Local video element not found');
            }
        });
    }
};

const attachLocalVideoTrack = () => {
    if (!livekitRoom?.localParticipant) return;

    console.log('Attempting to attach local video track...');

    // Get the video track publication
    const videoPublication = livekitRoom.localParticipant.getTrackPublication(Track.Source.Camera);

    if (videoPublication?.track) {
        console.log('Found local video track, attaching...');
        const videoElement = document.getElementById(`video-${livekitRoom.localParticipant.identity}`);
        if (videoElement) {
            videoPublication.track.attach(videoElement);
            console.log('Local video track attached successfully');
        } else {
            console.error('Video element not found for local participant');
        }
    } else {
        console.log('No local video track found yet, waiting...');
        // Wait a bit and try again
        setTimeout(attachLocalVideoTrack, 500);
    }
};

const attachLocalScreenShareTrack = () => {
    if (!livekitRoom?.localParticipant) return;

    console.log('Attempting to attach local screen share track...');

    // Get the screen share track publication
    const screenSharePublication = livekitRoom.localParticipant.getTrackPublication(Track.Source.ScreenShare);

    if (screenSharePublication?.track) {
        console.log('Found local screen share track, attaching...');
        const videoElement = document.getElementById(`video-${livekitRoom.localParticipant.identity}`);
        if (videoElement) {
            screenSharePublication.track.attach(videoElement);
            console.log('Local screen share track attached successfully');
        } else {
            console.error('Video element not found for local participant');
        }
    } else {
        console.log('No local screen share track found yet, waiting...');
        // Wait a bit and try again
        setTimeout(attachLocalScreenShareTrack, 500);
    }
};

const handleLocalTrackUnpublished = (publication) => {
    console.log('Local track unpublished:', publication.track?.kind, publication.track?.source);
};

const handleTrackSubscribed = (track, publication, participant) => {
    console.log('Track subscribed:', track.kind, track.source, 'from', participant.identity);

    if (track.kind === Track.Kind.Video) {
        // Ensure a tile exists for this participant; if not, add one locally
        const exists = remoteParticipants.value.some((p) => p.identity === participant.identity);
        if (!exists) {
            remoteParticipants.value.push({
                identity: participant.identity,
                name: participant.name,
                isCameraEnabled: true,
                isMicrophoneEnabled: false,
                isScreenSharing: false,
                isSpeaking: false,
            });
        }

        // Retry attach if element isn't ready yet
        const tryAttach = (attempt = 0) => {
            nextTick(() => {
                const videoElement = document.getElementById(`video-${participant.identity}`);
                if (videoElement) {
                    track.attach(videoElement);
                    console.log('Remote video track attached');
                    const v = /** @type {HTMLVideoElement} */ videoElement;
                    if (typeof v.play === 'function') {
                        v.play().catch((err) => {
                            console.warn('Video play() blocked; will rely on Join Audio or user gesture.', err);
                        });
                    }
                } else if (attempt < 20) {
                    console.warn('Video element not found, retrying attach...', participant.identity, 'attempt', attempt + 1);
                    setTimeout(() => tryAttach(attempt + 1), 200);
                } else {
                    console.warn('Video element not found for participant after retries:', participant.identity);
                }
            });
        };
        tryAttach(0);
    }
    if (track.kind === Track.Kind.Audio) {
        nextTick(() => {
            let audioElement = /** @type {HTMLAudioElement | null} */ document.getElementById(`audio-${participant.identity}`);
            if (!audioElement) {
                audioElement = document.createElement('audio');
                audioElement.id = `audio-${participant.identity}`;
                audioElement.autoplay = true;
                audioElement.setAttribute('playsinline', 'true');
                audioElement.style.display = 'none';
                document.body.appendChild(audioElement);
            }
            track.attach(audioElement);
            audioElement.play().catch((err) => {
                console.warn('Audio play() blocked; needs user gesture. Use Join Audio.', err);
            });
        });
    }
};

const handleTrackUnsubscribed = (track, publication, participant) => {
    console.log('Track unsubscribed:', track.kind, track.source, 'from', participant.identity);
};

const handleDataReceived = (payload, participant, _kind, topic) => {
    try {
        if (topic !== 'chat') return;

        // Avoid duplicating local messages if SDK echoes to sender
        if (participant?.identity && livekitRoom?.localParticipant?.identity && participant.identity === livekitRoom.localParticipant.identity) {
            return;
        }

        const decoded = new TextDecoder().decode(payload);
        const message = JSON.parse(decoded);

        // Ensure chatMessages is an array before adding message
        ensureChatMessagesArray();

        chatMessages.value.push({
            id: Date.now(),
            sender: participant?.identity || 'Unknown',
            text: message.text,
            timestamp: new Date(),
        });

        console.log('Received message from:', participant?.identity, 'Total messages:', chatMessages.value.length);
        scrollToBottom();
    } catch (error) {
        console.error('Failed to handle received message:', error);
    }
};

const loadChatHistory = async () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const resp = await fetch(`/api/livekit/rooms/${props.roomId}/chat`, {
        method: 'GET',
        headers: {
            Accept: 'application/json',
            ...(csrfToken && { 'X-CSRF-TOKEN': csrfToken }),
        },
    });
    if (!resp.ok) throw new Error('Failed to load chat');
    const data = await resp.json();
    const mapped = Array.isArray(data.messages)
        ? data.messages.map((m) => ({
              id: m.id,
              sender: m.sender_identity || (m.sender_id ? `User ${m.sender_id}` : 'Unknown'),
              text: m.text,
              timestamp: m.sent_at ? new Date(m.sent_at) : new Date(),
          }))
        : [];
    chatMessages.value = mapped;
    scrollToBottom();
};

const updateParticipants = () => {
    if (!livekitRoom) return;

    // Update local participant
    if (livekitRoom.localParticipant) {
        const localTracks = livekitRoom.localParticipant.getTrackPublications();
        localParticipant.value = {
            identity: livekitRoom.localParticipant.identity,
            name: livekitRoom.localParticipant.name,
            isCameraEnabled: localTracks.find((pub) => pub.source === Track.Source.Camera)?.track?.isEnabled || false,
            isMicrophoneEnabled: localTracks.find((pub) => pub.source === Track.Source.Microphone)?.track?.isEnabled || false,
            isScreenSharing: localTracks.find((pub) => pub.source === Track.Source.ScreenShare)?.track?.isEnabled || false,
            isSpeaking: livekitRoom.localParticipant.isSpeaking,
        };
    }

    // Update remote participants
    if (livekitRoom.participants && livekitRoom.participants.size > 0) {
        remoteParticipants.value = Array.from(livekitRoom.participants.values()).map((participant) => {
            const participantTracks = participant.getTrackPublications();
            return {
                identity: participant.identity,
                name: participant.name,
                isCameraEnabled: participantTracks.find((pub) => pub.source === Track.Source.Camera)?.track?.isEnabled || false,
                isMicrophoneEnabled: participantTracks.find((pub) => pub.source === Track.Source.Microphone)?.track?.isEnabled || false,
                isScreenSharing: participantTracks.find((pub) => pub.source === Track.Source.ScreenShare)?.track?.isEnabled || false,
                isSpeaking: participant.isSpeaking,
            };
        });
    } else {
        remoteParticipants.value = [];
    }

    console.log('Participants updated:', remoteParticipants.value.length + 1);
};

const toggleMicrophone = async () => {
    if (!livekitRoom?.localParticipant) {
        console.log('Not connected to room. Please wait...');
        return;
    }

    try {
        if (isMicrophoneEnabled.value) {
            await livekitRoom.localParticipant.setMicrophoneEnabled(false);
            isMicrophoneEnabled.value = false;
        } else {
            await livekitRoom.localParticipant.setMicrophoneEnabled(true);
            isMicrophoneEnabled.value = true;
        }
        updateParticipants();
    } catch (error) {
        console.error('Failed to toggle microphone:', error);
    }
};

const toggleCamera = async () => {
    if (!livekitRoom?.localParticipant) {
        console.log('Not connected to room. Please wait...');
        return;
    }

    try {
        if (isCameraEnabled.value) {
            await livekitRoom.localParticipant.setCameraEnabled(false);
            isCameraEnabled.value = false;
        } else {
            await livekitRoom.localParticipant.setCameraEnabled(true);
            isCameraEnabled.value = true;
            // Attach the video track immediately after enabling
            attachLocalVideoTrack();
        }
        updateParticipants();
    } catch (error) {
        console.error('Failed to toggle camera:', error);
    }
};

const toggleScreenShare = async () => {
    if (!livekitRoom?.localParticipant) {
        console.log('Not connected to room. Please wait...');
        return;
    }

    try {
        if (isScreenSharing.value) {
            await livekitRoom.localParticipant.setScreenShareEnabled(false);
            isScreenSharing.value = false;
            // Reattach camera when screen sharing stops
            if (isCameraEnabled.value) {
                attachLocalVideoTrack();
            }
        } else {
            await livekitRoom.localParticipant.setScreenShareEnabled(true);
            isScreenSharing.value = true;
            // Attach screen share track
            attachLocalScreenShareTrack();
        }
        updateParticipants();
    } catch (error) {
        console.error('Failed to toggle screen share:', error);
    }
};

const addTestMessage = () => {
    console.log('Adding test message...');
    console.log('chatMessages before test:', chatMessages.value);

    const testMessage = {
        id: Date.now(),
        sender: 'Test User',
        text: 'This is a test message at ' + new Date().toLocaleTimeString(),
        timestamp: new Date(),
    };

    chatMessages.value.push(testMessage);

    console.log('Test message added, chat messages count:', chatMessages.value.length);
    console.log('chatMessages after test:', chatMessages.value);
};

const sendMessage = async () => {
    if (!newMessage.value.trim() || !livekitRoom) return;

    try {
        const messageData = {
            text: newMessage.value,
            timestamp: new Date().toISOString(),
        };

        await livekitRoom.localParticipant.publishData(new TextEncoder().encode(JSON.stringify(messageData)), { topic: 'chat' });

        // Ensure chatMessages is an array before adding message
        ensureChatMessagesArray();

        const newChatMessage = {
            id: Date.now(),
            sender: livekitRoom.localParticipant.identity,
            text: newMessage.value,
            timestamp: new Date(),
        };

        console.log('About to add message:', newChatMessage);
        console.log('chatMessages before push:', chatMessages.value);
        console.log('Is chatMessages an array?', Array.isArray(chatMessages.value));

        chatMessages.value.push(newChatMessage);

        console.log('Message sent, chat messages count:', chatMessages.value.length);
        console.log('chatMessages after push:', chatMessages.value);
        newMessage.value = '';
        scrollToBottom();

        // Persist to backend (fire-and-forget)
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            await fetch(`/api/livekit/rooms/${props.roomId}/chat`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    ...(csrfToken && { 'X-CSRF-TOKEN': csrfToken }),
                },
                body: JSON.stringify({
                    text: messageData.text,
                    participant_identity: participantIdentity.value || livekitRoom.localParticipant.identity,
                }),
            });
        } catch (persistErr) {
            console.error('Failed to persist chat message:', persistErr);
        }
    } catch (error) {
        console.error('Failed to send message:', error);
    }
};

const scrollToBottom = () => {
    nextTick(() => {
        if (chatMessagesRef.value) {
            chatMessagesRef.value.scrollTop = chatMessagesRef.value.scrollHeight;
        }
    });
};

const leaveRoom = async () => {
    if (livekitRoom) {
        await livekitRoom.disconnect();
    }

    // Notify backend
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        await fetch(`/api/livekit/rooms/${props.roomId}/leave`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                ...(csrfToken && { 'X-CSRF-TOKEN': csrfToken }),
            },
        });
    } catch (error) {
        console.error('Failed to notify backend about leaving:', error);
    }
};

// const testVideoElements = () => {
//     console.log('Testing video elements...');
//     if (livekitRoom?.localParticipant) {
//         const localVideoElement = document.getElementById(`video-${livekitRoom.localParticipant.identity}`);
//         console.log('Local Video Element:', !!localVideoElement);
//         if (localVideoElement) {
//             console.log('Local Video Element ID:', localVideoElement.id);
//             console.log('Local Video Element Ref:', localVideoElement.ref);
//             console.log('Local Video Element Dimensions:', localVideoElement.offsetWidth, 'x', localVideoElement.offsetHeight);
//             console.log('Local Video Element Style:', {
//                 display: localVideoElement.style.display,
//                 visibility: localVideoElement.style.visibility,
//                 opacity: localVideoElement.style.opacity,
//                 zIndex: localVideoElement.style.zIndex,
//             });
//             console.log('Local Video Element Computed Style:', {
//                 display: window.getComputedStyle(localVideoElement).display,
//                 visibility: window.getComputedStyle(localVideoElement).visibility,
//                 opacity: window.getComputedStyle(localVideoElement).opacity,
//                 zIndex: window.getComputedStyle(localVideoElement).zIndex,
//             });

//             // Check if video has srcObject
//             if (localVideoElement.srcObject) {
//                 console.log('Local Video has srcObject:', localVideoElement.srcObject);
//                 const tracks = localVideoElement.srcObject.getTracks();
//                 console.log('Local Video Tracks:', tracks.length);
//                 tracks.forEach((track) => {
//                     console.log('Track:', track.kind, track.enabled, track.readyState);
//                 });
//             } else {
//                 console.log('Local Video has NO srcObject');
//             }

//             // Check CSS properties that might hide video
//             const computedStyle = window.getComputedStyle(localVideoElement);
//             console.log('CSS Debug Info:');
//             console.log('- display:', computedStyle.display);
//             console.log('- visibility:', computedStyle.visibility);
//             console.log('- opacity:', computedStyle.opacity);
//             console.log('- width:', computedStyle.width);
//             console.log('- height:', computedStyle.height);
//             console.log('- position:', computedStyle.position);
//             console.log('- z-index:', computedStyle.zIndex);
//             console.log('- overflow:', computedStyle.overflow);
//             console.log('- background:', computedStyle.background);

//             // Check if video is actually playing
//             console.log('Video readyState:', localVideoElement.readyState);
//             console.log('Video paused:', localVideoElement.paused);
//             console.log('Video ended:', localVideoElement.ended);
//             console.log('Video currentTime:', localVideoElement.currentTime);
//             console.log('Video duration:', localVideoElement.duration);
//         }
//     }
//     if (livekitRoom?.participants) {
//         livekitRoom.participants.forEach((participant) => {
//             const remoteVideoElement = document.getElementById(`video-${participant.identity}`);
//             console.log(`Remote Video Element for ${participant.identity}:`, !!remoteVideoElement);
//             if (remoteVideoElement) {
//                 console.log('Remote Video Element ID:', remoteVideoElement.id);
//                 console.log('Remote Video Element Ref:', remoteVideoElement.ref);
//             }
//         });
//     }
// };

const joinAudio = async () => {
    if (!livekitRoom) return;
    try {
        await livekitRoom.startAudio();
        hasStartedAudio.value = true;
        console.log('Audio started after user gesture');
        if (livekitRoom.participants) {
            livekitRoom.participants.forEach((p) => {
                const el = /** @type {HTMLVideoElement | null} */ document.getElementById(`video-${p.identity}`);
                if (el && typeof el.play === 'function') {
                    el.play().catch(() => {});
                }
                const audioEl = /** @type {HTMLAudioElement | null} */ document.getElementById(`audio-${p.identity}`);
                if (audioEl) {
                    audioEl.play().catch(() => {});
                }
            });
        }
    } catch (e) {
        console.error('Failed to start audio on user gesture:', e);
    }
};
</script>

<style scoped>
.livekit-room {
    display: flex;
    flex-direction: column;
    height: 100vh;
    background: #1a1a1a;
    color: white;
}

.room-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: #2a2a2a;
    border-bottom: 1px solid #333;
}

.error-message {
    background: #ef4444;
    color: white;
    padding: 0.75rem 1rem;
    margin: 0 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
}

.room-status {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
}

.status-connected {
    background: #10b981;
    color: white;
}

.status-connecting {
    background: #f59e0b;
    color: white;
}

.status-disconnected {
    background: #ef4444;
    color: white;
}

.status-unknown {
    background: #6b7280;
    color: white;
}

.room-content {
    display: flex;
    flex: 1;
    overflow: hidden;
}

.video-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    background: #1a1a1a;
    max-height: 100vh; /* Limit height to fit in modal */
    overflow: hidden; /* Prevent scrolling issues */
}

.video-grid {
    flex: 1;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
    padding: 1rem;
    min-height: 0;
    max-height: 100vh; /* Limit video grid height */
    overflow-y: auto; /* Allow scrolling if needed */
}

.video-item {
    position: relative;
    background: #2a2a2a;
    border-radius: 0.5rem;
    overflow: hidden;
    aspect-ratio: 16/9;
}

.video-item video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.video-placeholder {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #404040;
}

.placeholder-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #10b981;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: bold;
}

.participant-info {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 0.5rem;
    font-size: 0.875rem;
}

.participant-name {
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.participant-status {
    display: flex;
    gap: 0.5rem;
    font-size: 0.75rem;
}

.speaking-indicator {
    color: #10b981;
}

.screen-share {
    color: #f59e0b;
}

.camera-off {
    color: #ef4444;
}

.mic-off {
    color: #ef4444;
}

.controls {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: #2a2a2a;
    border-top: 1px solid #333;
    align-items: center;
    position: sticky !important;
    bottom: 0 !important;
    z-index: 1000 !important;
    min-height: 80px !important;
}

.control-btn {
    padding: 0.75rem 1rem;
    border: none;
    border-radius: 0.5rem;
    background: #404040;
    color: white;
    cursor: pointer;
    transition: background-color 0.2s;
}

.control-btn:hover {
    background: #505050;
}

.control-btn.active {
    background: #10b981;
}

.leave-btn {
    background: #ef4444;
}

.leave-btn:hover {
    background: #dc2626;
}

.viewer-indicator {
    padding: 0.5rem 1rem;
    background: #6b7280;
    color: white;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    font-weight: 500;
    margin-right: 0.5rem;
}

.connection-status {
    margin-left: auto;
    display: flex;
    align-items: center;
}

.status-indicator {
    padding: 0.5rem 1rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    font-weight: 500;
}

.status-connected {
    background: #10b981;
    color: white;
}

.status-connecting {
    background: #f59e0b;
    color: white;
}

.status-disconnected {
    background: #ef4444;
    color: white;
}

.status-unknown {
    background: #6b7280;
    color: white;
}

.chat-container {
    width: 300px;
    display: flex;
    flex-direction: column;
    background: #2a2a2a;
    border-left: 1px solid #333;
}

.chat-messages {
    flex: 1;
    padding: 1rem;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.chat-message {
    padding: 0.5rem;
    background: #404040;
    border-radius: 0.5rem;
    font-size: 0.875rem;
}

.message-sender {
    font-weight: 500;
    color: #10b981;
}

.message-text {
    margin-left: 0.5rem;
}

.chat-input {
    display: flex;
    padding: 1rem;
    gap: 0.5rem;
    border-top: 1px solid #333;
}

.message-input {
    flex: 1;
    padding: 0.5rem;
    border: 1px solid #404040;
    border-radius: 0.25rem;
    background: #404040;
    color: white;
}

.message-input::placeholder {
    color: #9ca3af;
}

.send-btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 0.25rem;
    background: #10b981;
    color: white;
    cursor: pointer;
}

.send-btn:hover {
    background: #059669;
}
</style>
