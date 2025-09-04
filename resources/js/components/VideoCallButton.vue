<template>
    <div>
        <!-- Start Video Call Button -->
        <button
            v-if="!hasActiveCall"
            @click="startVideoCall"
            :disabled="isCreatingCall"
            class="flex w-full items-center justify-center space-x-2 rounded-lg bg-blue-600 px-4 py-3 font-medium text-white transition-colors hover:bg-blue-700 disabled:bg-blue-400"
        >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
                ></path>
            </svg>
            <span v-if="!isCreatingCall">Start Video Consultation</span>
            <span v-else>Starting Call...</span>
        </button>

        <!-- Join Video Call Button -->
        <button
            v-else
            @click="joinVideoCall"
            class="flex w-full items-center justify-center space-x-2 rounded-lg bg-green-600 px-4 py-3 font-medium text-white transition-colors hover:bg-green-700"
        >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
                ></path>
            </svg>
            <span>Join Video Consultation</span>
        </button>

        <!-- Call Status -->
        <div v-if="callStatus" class="mt-2 text-center text-sm text-gray-600">
            {{ callStatus }}
        </div>
    </div>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, ref } from 'vue';

interface Props {
    bookingId: number;
    userRole: 'doctor' | 'patient';
}

const props = defineProps<Props>();

const isCreatingCall = ref(false);
const hasActiveCall = ref(false);
const callStatus = ref('');
const activeRoomId = ref<string | null>(null);

const checkExistingCall = async () => {
    try {
        // This would need to be implemented in the backend
        // Check if there's an existing active call for this booking
        const response = await axios.get(`/api/bookings/${props.bookingId}/video-call`);
        if (response.data.video_call) {
            hasActiveCall.value = true;
            activeRoomId.value = response.data.video_call.room_id;
            callStatus.value = `Video call is ${response.data.video_call.status}`;
        }
    } catch {
        // No existing call
        hasActiveCall.value = false;
    }
};

const startVideoCall = async () => {
    if (props.userRole !== 'doctor') {
        callStatus.value = 'Only doctors can start video calls';
        return;
    }

    isCreatingCall.value = true;
    callStatus.value = 'Creating video call room...';

    try {
        const response = await axios.post('/api/video-calls/create', {
            booking_id: props.bookingId,
        });

        const roomId = response.data.room_id;

        // Redirect to the video call room
        router.visit(`/video-call/${roomId}`);
    } catch (error: any) {
        console.error('Error creating video call:', error);
        callStatus.value = error.response?.data?.message || 'Failed to create video call';
        isCreatingCall.value = false;
    }
};

const joinVideoCall = async () => {
    if (!activeRoomId.value) {
        callStatus.value = 'No active call to join';
        return;
    }

    // Redirect to the video call room
    router.visit(`/video-call/${activeRoomId.value}`);
};

onMounted(() => {
    checkExistingCall();
});
</script>
