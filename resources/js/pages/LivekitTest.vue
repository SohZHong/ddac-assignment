<template>
    <div class="livekit-test">
        <div class="container mx-auto p-6">
            <h1 class="mb-6 text-2xl font-bold">LiveKit Test</h1>

            <!-- Room Creation -->
            <div class="mb-8 rounded-lg bg-gray-100 p-4">
                <h2 class="mb-4 text-lg font-semibold">Create Room</h2>
                <div class="flex gap-4">
                    <select v-model="selectedEvent" class="rounded border px-3 py-2">
                        <option value="">Select an event</option>
                        <option v-for="event in events" :key="event.id" :value="event.id">
                            {{ event.title }}
                        </option>
                    </select>
                    <button
                        @click="createRoom"
                        :disabled="!selectedEvent || creating"
                        class="rounded bg-blue-500 px-4 py-2 text-white disabled:opacity-50"
                    >
                        {{ creating ? 'Creating...' : 'Create Room' }}
                    </button>
                </div>
            </div>

            <!-- Room Management -->
            <div v-if="room" class="mb-8 rounded-lg bg-gray-100 p-4">
                <h2 class="mb-4 text-lg font-semibold">Room Management</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="rounded border bg-white p-4">
                        <h3 class="font-semibold">Room Info</h3>
                        <p>ID: {{ room.id }}</p>
                        <p>Name: {{ room.room_name }}</p>
                        <p>Status: {{ room.status }}</p>
                    </div>

                    <div class="rounded border bg-white p-4">
                        <h3 class="font-semibold">Actions</h3>
                        <div class="space-y-2">
                            <button
                                @click="startRoom"
                                :disabled="room.status === 'live' || starting"
                                class="w-full rounded bg-green-500 px-3 py-1 text-sm text-white disabled:opacity-50"
                            >
                                {{ starting ? 'Starting...' : 'Start Room' }}
                            </button>
                            <button
                                @click="endRoom"
                                :disabled="room.status !== 'live' || ending"
                                class="w-full rounded bg-red-500 px-3 py-1 text-sm text-white disabled:opacity-50"
                            >
                                {{ ending ? 'Ending...' : 'End Room' }}
                            </button>
                        </div>
                    </div>

                    <div class="rounded border bg-white p-4">
                        <h3 class="font-semibold">Join Room</h3>
                        <p class="mb-2 text-xs text-gray-600">You can join rooms that are scheduled or live.</p>
                        <button
                            @click="joinRoom"
                            :disabled="(room.status !== 'live' && room.status !== 'scheduled') || joining"
                            class="w-full rounded bg-purple-500 px-3 py-1 text-sm text-white disabled:opacity-50"
                        >
                            {{ joining ? 'Joining...' : 'Join Room' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- LiveKit Room Component -->
            <div v-if="showLivekitRoom" class="h-screen">
                <LivekitRoom :room-id="room.id" />
            </div>
        </div>
    </div>
</template>

<script setup>
import LivekitRoom from '@/components/LivekitRoom.vue';
import { onMounted, ref } from 'vue';

const events = ref([]);
const selectedEvent = ref('');
const room = ref(null);
const showLivekitRoom = ref(false);
const creating = ref(false);
const starting = ref(false);
const ending = ref(false);
const joining = ref(false);

onMounted(async () => {
    await loadEvents();
});

const loadEvents = async () => {
    try {
        // Get CSRF token from meta tag or use a fallback
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const response = await fetch('/api/events', {
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                ...(csrfToken && { 'X-CSRF-TOKEN': csrfToken }),
            },
        });
        if (response.ok) {
            events.value = await response.json();
        } else {
            console.error('Failed to load events:', response.status, response.statusText);
        }
    } catch (error) {
        console.error('Failed to load events:', error);
    }
};

const createRoom = async () => {
    if (!selectedEvent.value) return;

    creating.value = true;
    try {
        const response = await fetch('/api/livekit/rooms', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({
                event_id: selectedEvent.value,
                max_participants: 100,
            }),
        });

        if (response.ok) {
            const data = await response.json();
            room.value = data.room;
        } else {
            const error = await response.json();
            alert('Failed to create room: ' + error.error);
        }
    } catch (error) {
        console.error('Failed to create room:', error);
        alert('Failed to create room');
    } finally {
        creating.value = false;
    }
};

const startRoom = async () => {
    if (!room.value) return;

    starting.value = true;
    try {
        const response = await fetch(`/api/livekit/rooms/${room.value.id}/start`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
        });

        if (response.ok) {
            const data = await response.json();
            room.value = data.room;
        } else {
            const error = await response.json();
            alert('Failed to start room: ' + error.error);
        }
    } catch (error) {
        console.error('Failed to start room:', error);
        alert('Failed to start room');
    } finally {
        starting.value = false;
    }
};

const endRoom = async () => {
    if (!room.value) return;

    ending.value = true;
    try {
        const response = await fetch(`/api/livekit/rooms/${room.value.id}/end`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
        });

        if (response.ok) {
            const data = await response.json();
            room.value = data.room;
            showLivekitRoom.value = false;
        } else {
            const error = await response.json();
            alert('Failed to end room: ' + error.error);
        }
    } catch (error) {
        console.error('Failed to end room:', error);
        alert('Failed to end room');
    } finally {
        ending.value = false;
    }
};

const joinRoom = async () => {
    if (!room.value) return;

    joining.value = true;
    try {
        const response = await fetch(`/api/livekit/rooms/${room.value.id}/join`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
        });

        if (response.ok) {
            showLivekitRoom.value = true;
        } else {
            const error = await response.json();
            alert('Failed to join room: ' + error.error);
        }
    } catch (error) {
        console.error('Failed to join room:', error);
        alert('Failed to join room');
    } finally {
        joining.value = false;
    }
};
</script>
