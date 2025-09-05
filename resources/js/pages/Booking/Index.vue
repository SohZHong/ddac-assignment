<script setup lang="ts">
import axios from '@/axios';
import Toast from '@/components/Toast.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Booking, BookingStatus, PatientRiskLevel } from '@/types/booking';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    upcoming: Booking[];
    past: Booking[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Appointments', href: '#' }];

const toastRef = ref<InstanceType<typeof Toast> | null>(null);
const toastMessage = ref({ title: '', description: '', variant: 'default' as 'default' | 'success' | 'destructive' });

const searchQuery = ref('');

// Modal state
const selectedBooking = ref<Booking | null>(null);
const isDetailsModalOpen = ref(false);

const statusMap: Record<BookingStatus, { text: string; variant: 'default' | 'destructive' | 'secondary' }> = {
    [BookingStatus.PENDING]: { text: 'Pending', variant: 'secondary' },
    [BookingStatus.CONFIRMED]: { text: 'Confirmed', variant: 'default' },
    [BookingStatus.CANCELLED]: { text: 'Cancelled', variant: 'destructive' },
};

const upcomingBookings = ref<Booking[]>(props.upcoming);
const pastBookings = ref<Booking[]>(props.past);

const filteredUpcoming = computed(() =>
    upcomingBookings.value.filter((b) => !searchQuery.value || b.healthcare?.name.toLowerCase().includes(searchQuery.value.toLowerCase())),
);

const filteredPast = computed(() =>
    pastBookings.value.filter((b) => !searchQuery.value || b.healthcare?.name.toLowerCase().includes(searchQuery.value.toLowerCase())),
);

async function cancelBooking(id: string) {
    const status = BookingStatus.CANCELLED;
    await axios
        .patch(route('api.booking.cancel', id))
        .then(() => {
            // Update the local booking status
            const booking = upcomingBookings.value.find((b) => b.id === id);
            if (booking) {
                booking.status = status;
            }

            toastMessage.value = {
                title: `Booking Cancelled`,
                description: `Successfully cancelled appointment with ${booking?.healthcare?.name}.`,
                variant: 'destructive',
            };

            // Show toast
            toastRef.value?.showToast();
        })
        .catch((err) => {
            toastMessage.value = {
                title: `Failed to cancael booking`,
                description: err.message,
                variant: 'destructive',
            };
            console.error('Failed to cancel booking', err);
            toastRef.value?.showToast();
        });
}

function showBookingDetails(booking: Booking) {
    selectedBooking.value = booking;
    isDetailsModalOpen.value = true;
}

function closeDetailsModal() {
    isDetailsModalOpen.value = false;
    selectedBooking.value = null;
}

async function startVideoCall() {
    if (!selectedBooking.value?.id) {
        toastMessage.value = {
            title: 'Error',
            description: 'No booking selected for video call',
            variant: 'destructive',
        };
        toastRef.value?.showToast();
        return;
    }

    try {
        // First, check if a video call room already exists for this booking
        const checkResponse = await axios.get(`/api/bookings/${selectedBooking.value.id}/video-call`);

        let roomId;

        if (checkResponse.data.video_call) {
            // Use existing room
            roomId = checkResponse.data.video_call.room_id;
        } else {
            // Create new video call room if none exists
            const createResponse = await axios.post('/api/video-calls/create', {
                booking_id: selectedBooking.value.id,
            });
            roomId = createResponse.data.room_id;
        }

        // Close modal and redirect to video call room
        closeDetailsModal();
        router.visit(`/video-call/${roomId}`);
    } catch (error: any) {
        console.error('Error with video call:', error);
        toastMessage.value = {
            title: 'Video Call Error',
            description: error.response?.data?.message || 'Failed to access video call',
            variant: 'destructive',
        };
        toastRef.value?.showToast();
    }
}

async function startVideoCallForBooking(booking: Booking) {
    if (!booking.id) {
        toastMessage.value = {
            title: 'Error',
            description: 'Invalid booking for video call',
            variant: 'destructive',
        };
        toastRef.value?.showToast();
        return;
    }

    try {
        // First, check if a video call room already exists for this booking
        const checkResponse = await axios.get(`/api/bookings/${booking.id}/video-call`);

        let roomId;

        if (checkResponse.data.video_call) {
            // Use existing room
            roomId = checkResponse.data.video_call.room_id;
        } else {
            // Create new video call room if none exists
            const createResponse = await axios.post('/api/video-calls/create', {
                booking_id: booking.id,
            });
            roomId = createResponse.data.room_id;
        }

        // Redirect to video call room
        router.visit(`/video-call/${roomId}`);
    } catch (error: any) {
        console.error('Error with video call:', error);
        toastMessage.value = {
            title: 'Video Call Error',
            description: error.response?.data?.message || 'Failed to access video call',
            variant: 'destructive',
        };
        toastRef.value?.showToast();
    }
}
</script>

<template>
    <Head title="My Appointments" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toast ref="toastRef" :title="toastMessage.title" :description="toastMessage.description" :variant="toastMessage.variant" />
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">My Appointments</h1>
                    <p class="text-muted-foreground">View your upcoming and past appointments</p>
                </div>

                <div class="flex flex-row items-center gap-4">
                    <Input v-model="searchQuery" placeholder="Search by doctor" icon="search" class="min-w-[200px]" />
                </div>
            </div>
            <!-- Upcoming -->
            <div>
                <h2 class="mb-4 text-xl font-semibold">Upcoming</h2>
                <div v-if="filteredUpcoming.length > 0" class="overflow-x-auto rounded-lg border">
                    <div class="min-w-[700px]">
                        <!-- Table Header -->
                        <div class="grid grid-cols-4 bg-muted text-sm font-semibold text-muted-foreground">
                            <div class="px-4 py-2">Doctor</div>
                            <div class="px-4 py-2">Time</div>
                            <div class="px-4 py-2">Status</div>
                            <div class="px-4 py-2">Actions</div>
                        </div>

                        <!-- Table Rows -->
                        <div
                            v-for="booking in filteredUpcoming"
                            :key="booking.id"
                            class="grid grid-cols-4 items-center border-t bg-white text-sm hover:bg-stone-50 dark:bg-black dark:hover:bg-accent"
                        >
                            <!-- Doctor -->
                            <div class="px-4 py-3 font-medium">{{ booking.healthcare?.name }}</div>

                            <!-- Time -->
                            <div class="px-4 py-3 text-muted-foreground">
                                {{ new Date(booking.start_time).toLocaleString() }} –
                                {{ new Date(booking.end_time).toLocaleString() }}
                            </div>

                            <!-- Status -->
                            <div class="px-4 py-3">
                                <Badge :variant="statusMap[booking.status].variant">
                                    {{ statusMap[booking.status].text }}
                                </Badge>
                            </div>

                            <!-- Actions -->
                            <div class="flex flex-wrap gap-2 px-4 py-3" v-if="booking.status !== BookingStatus.CANCELLED">
                                <Button size="sm" variant="default" v-if="booking.status !== BookingStatus.PENDING">
                                    <Link :href="route('booking.assessment.index', booking.id)">
                                        {{ booking.has_assessment ? 'Reattempt Assessment' : 'Start Assessment' }}
                                    </Link>
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    v-if="booking.status !== BookingStatus.PENDING"
                                    @click="startVideoCallForBooking(booking)"
                                    class="border-green-700 text-green-700 hover:bg-green-700 hover:text-white"
                                >
                                    <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
                                        ></path>
                                    </svg>
                                    Video Call
                                </Button>
                                <Button size="sm" variant="destructive" @click="cancelBooking(booking.id!)">Cancel</Button>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center text-muted-foreground">No upcoming appointments.</div>
            </div>

            <!-- Past -->
            <div>
                <h2 class="mb-4 text-xl font-semibold">Past</h2>
                <div v-if="filteredPast.length > 0" class="overflow-x-auto rounded-lg border">
                    <div class="min-w-[700px]">
                        <!-- Table Header -->
                        <div class="grid grid-cols-4 bg-muted text-sm font-semibold text-muted-foreground">
                            <div class="px-4 py-2">Doctor</div>
                            <div class="px-4 py-2">Time</div>
                            <div class="px-4 py-2">Status</div>
                            <div class="px-4 py-2">Actions</div>
                        </div>

                        <!-- Table Rows -->
                        <div
                            v-for="booking in filteredPast"
                            :key="booking.id"
                            class="grid grid-cols-3 items-center border-t bg-white text-sm hover:bg-stone-50 dark:bg-black dark:hover:bg-accent"
                        >
                            <!-- Doctor -->
                            <div class="px-4 py-3 font-medium">{{ booking.healthcare?.name }}</div>

                            <!-- Time -->
                            <div class="px-4 py-3 text-muted-foreground">
                                {{ new Date(booking.start_time).toLocaleString() }} –
                                {{ new Date(booking.end_time).toLocaleString() }}
                            </div>

                            <!-- Status -->
                            <div class="px-4 py-3">
                                <Badge :variant="statusMap[booking.status].variant">
                                    {{ statusMap[booking.status].text }}
                                </Badge>
                            </div>

                            <!-- Actions -->
                            <div class="flex flex-wrap gap-2 px-4 py-3">
                                <Button size="sm" variant="outline" @click="showBookingDetails(booking)">View Details</Button>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center text-muted-foreground">No past appointments.</div>
            </div>
        </div>

        <div
            v-if="isDetailsModalOpen"
            class="bg-opacity-75 fixed inset-0 z-50 flex items-center justify-center bg-black p-4"
            @click="closeDetailsModal"
        >
            <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg bg-gray-800 shadow-xl" @click.stop>
                <div class="flex items-center justify-between border-b border-gray-600 p-6">
                    <h3 class="text-xl font-semibold text-white">Appointment Details</h3>
                    <button @click="closeDetailsModal" class="text-gray-400 transition-colors hover:text-gray-200">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div v-if="selectedBooking" class="space-y-6 p-6">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <h4 class="mb-2 text-sm font-medium text-gray-300">Healthcare Professional</h4>
                            <p class="text-lg font-semibold text-blue-400">{{ selectedBooking.healthcare?.name }}</p>
                            <p class="text-sm text-gray-400">General Practice</p>
                        </div>

                        <div>
                            <h4 class="mb-2 text-sm font-medium text-gray-300">Status</h4>
                            <Badge :variant="statusMap[selectedBooking.status].variant" class="text-sm">
                                {{ statusMap[selectedBooking.status].text }}
                            </Badge>
                        </div>
                    </div>

                    <div>
                        <h4 class="mb-2 text-sm font-medium text-gray-300">Appointment Schedule</h4>
                        <div class="rounded-lg bg-gray-700 p-4">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <p class="text-sm text-gray-400">Start Time</p>
                                    <p class="font-medium text-white">{{ new Date(selectedBooking.start_time).toLocaleString() }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-400">End Time</p>
                                    <p class="font-medium text-white">{{ new Date(selectedBooking.end_time).toLocaleString() }}</p>
                                </div>
                            </div>
                            <div class="mt-3 border-t border-gray-600 pt-3">
                                <p class="text-sm text-gray-400">Duration</p>
                                <p class="font-medium text-white">
                                    {{
                                        Math.round(
                                            (new Date(selectedBooking.end_time).getTime() - new Date(selectedBooking.start_time).getTime()) /
                                                (1000 * 60),
                                        )
                                    }}
                                    minutes
                                </p>
                            </div>
                        </div>
                    </div>

                    <div v-if="selectedBooking.has_assessment !== undefined">
                        <h4 class="mb-2 text-sm font-medium text-gray-300">Health Assessment</h4>
                        <div class="rounded-lg bg-gray-700 p-4">
                            <div class="flex items-center space-x-3">
                                <div :class="['h-3 w-3 rounded-full', selectedBooking.has_assessment ? 'bg-green-500' : 'bg-gray-500']"></div>
                                <span class="font-medium text-white">
                                    {{ selectedBooking.has_assessment ? 'Assessment Completed' : 'Assessment Pending' }}
                                </span>
                            </div>
                            <p class="mt-2 text-sm text-gray-400">
                                {{
                                    selectedBooking.has_assessment
                                        ? 'You have completed your health assessment for this appointment.'
                                        : 'Please complete your health assessment before the appointment.'
                                }}
                            </p>
                        </div>
                    </div>

                    <div v-if="selectedBooking.has_video_call !== undefined">
                        <h4 class="mb-2 text-sm font-medium text-gray-300">Video Consultation</h4>
                        <div class="rounded-lg bg-gray-700 p-4">
                            <div class="flex items-center space-x-3">
                                <div :class="['h-3 w-3 rounded-full', selectedBooking.has_video_call ? 'bg-green-500' : 'bg-gray-500']"></div>
                                <span class="font-medium text-white">
                                    {{ selectedBooking.has_video_call ? 'Video Room Ready' : 'Video Room Not Created' }}
                                </span>
                            </div>
                            <p class="mt-2 text-sm text-gray-400">
                                {{
                                    selectedBooking.has_video_call
                                        ? `Video consultation room is ready. Status: ${selectedBooking.video_call_status}`
                                        : 'Video room will be created automatically when your appointment is confirmed.'
                                }}
                            </p>
                        </div>
                    </div>

                    <div v-if="selectedBooking.healthcare_comments || selectedBooking.risk_level">
                        <h4 class="mb-2 text-sm font-medium text-gray-300">Doctor's Assessment</h4>
                        <div class="rounded-lg border border-blue-600 bg-blue-900/20 p-4">
                            <div v-if="selectedBooking.risk_level !== undefined" class="mb-3">
                                <p class="text-sm text-gray-400">Risk Level</p>
                                <Badge
                                    :class="[
                                        'mt-1',
                                        selectedBooking.risk_level === PatientRiskLevel.LOW
                                            ? 'bg-green-100 text-green-800'
                                            : selectedBooking.risk_level === PatientRiskLevel.MID
                                              ? 'bg-yellow-100 text-yellow-800'
                                              : 'bg-red-100 text-red-800',
                                    ]"
                                >
                                    {{
                                        selectedBooking.risk_level === PatientRiskLevel.LOW
                                            ? 'LOW'
                                            : selectedBooking.risk_level === PatientRiskLevel.MID
                                              ? 'MEDIUM'
                                              : 'HIGH'
                                    }}
                                </Badge>
                            </div>
                            <div v-if="selectedBooking.healthcare_comments">
                                <p class="text-sm text-gray-400">Comments</p>
                                <p class="mt-1 text-sm text-gray-200">{{ selectedBooking.healthcare_comments }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-600 pt-4">
                        <p class="text-xs text-gray-500">Booking ID: {{ selectedBooking.id }}</p>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 border-t border-gray-600 bg-gray-700 p-6">
                    <Button variant="outline" class="border-gray-500 text-gray-300 hover:bg-gray-600 hover:text-white" @click="closeDetailsModal"
                        >Close</Button
                    >

                    <div
                        v-if="
                            selectedBooking && selectedBooking.status !== BookingStatus.CANCELLED && new Date(selectedBooking.start_time) > new Date()
                        "
                        class="flex space-x-3"
                    >
                        <!-- Assessment Button -->
                        <Button
                            v-if="selectedBooking.status !== BookingStatus.PENDING"
                            variant="default"
                            class="bg-blue-600 text-white hover:bg-blue-700"
                        >
                            <Link :href="route('booking.assessment.index', selectedBooking.id)">
                                {{ selectedBooking.has_assessment ? 'Reattempt Assessment' : 'Start Assessment' }}
                            </Link>
                        </Button>

                        <!-- Video Call Button - Only for upcoming appointments -->
                        <Button
                            v-if="selectedBooking.status !== BookingStatus.PENDING"
                            variant="default"
                            class="bg-green-600 text-white hover:bg-green-700"
                            @click="startVideoCall"
                        >
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
                                ></path>
                            </svg>
                            Join Video Call
                        </Button>

                        <!-- Cancel Button -->
                        <Button
                            variant="destructive"
                            class="bg-red-600 text-white hover:bg-red-700"
                            @click="
                                cancelBooking(selectedBooking.id!);
                                closeDetailsModal();
                            "
                        >
                            Cancel Appointment
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
