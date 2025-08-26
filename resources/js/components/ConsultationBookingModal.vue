<script setup lang="ts">
import Toast from '@/components/Toast.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { router } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

interface Schedule {
    id: number;
    healthcare_id: number;
    day_of_week: number;
    start_time: string;
    end_time: string;
    healthcare: {
        id: number;
        name: string;
    };
    available_slots: Array<{
        date: string;
        time: string;
        datetime: string;
    }>;
}

interface Props {
    open: boolean;
    healthcareId: number;
    quizResponseId: number;
}

interface Emits {
    (e: 'update:open', value: boolean): void;
    (e: 'close'): void;
    (e: 'booking-success'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const schedules = ref<Schedule[]>([]);
const selectedSlot = ref<{ schedule_id: number; date: string; time: string; datetime: string } | null>(null);
const loading = ref(false);
const toastRef = ref<InstanceType<typeof Toast> | null>(null);
const toastMessage = ref({
    title: '',
    description: '',
    variant: 'default' as 'default' | 'success' | 'destructive',
});

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

function generateAvailableSlots(schedule: Schedule) {
    const slots = [];
    const today = new Date();

    for (let i = 1; i <= 14; i++) {
        const date = new Date(today);
        date.setDate(today.getDate() + i);

        if (date.getDay() === schedule.day_of_week) {
            const startTime = new Date(schedule.start_time);
            const endTime = new Date(schedule.end_time);

            const startHour = startTime.getHours();
            const startMinute = startTime.getMinutes();
            const endHour = endTime.getHours();
            const endMinute = endTime.getMinutes();

            // Generate hourly slots from start to end time
            const current = new Date(date);
            current.setHours(startHour, startMinute, 0, 0);

            const endDateTime = new Date(date);
            endDateTime.setHours(endHour, endMinute, 0, 0);

            while (current < endDateTime) {
                slots.push({
                    date: date.toISOString().split('T')[0],
                    time: current.toTimeString().slice(0, 5),
                    datetime: new Date(current).toISOString(),
                });
                current.setHours(current.getHours() + 1);
            }
        }
    }

    return slots;
}

async function fetchSchedules() {
    if (!isOpen.value) return;

    loading.value = true;
    try {
        const response = await fetch(`/api/schedules/healthcare/${props.healthcareId}`);
        const data = await response.json();

        schedules.value = data.map((schedule: Schedule) => ({
            ...schedule,
            available_slots: generateAvailableSlots(schedule),
        }));
    } catch (error) {
        console.error('Error fetching schedules:', error);
        toastMessage.value = {
            title: 'Error',
            description: 'Failed to load available appointments.',
            variant: 'destructive',
        };
        toastRef.value?.showToast();
    } finally {
        loading.value = false;
    }
}

function selectSlot(schedule_id: number, slot: { date: string; time: string; datetime: string }) {
    selectedSlot.value = {
        schedule_id,
        ...slot,
    };
}

async function bookConsultation() {
    if (!selectedSlot.value) return;

    loading.value = true;
    try {
        router.post(
            '/bookings',
            {
                schedule_id: selectedSlot.value.schedule_id,
                start_time: selectedSlot.value.datetime,
                end_time: new Date(new Date(selectedSlot.value.datetime).getTime() + 60 * 60 * 1000).toISOString(), // +1 hour
                assessment_response_id: props.quizResponseId, // Link to the assessment
            },
            {
                onSuccess: () => {
                    toastMessage.value = {
                        title: 'Consultation Booked',
                        description: 'Your consultation has been successfully booked.',
                        variant: 'success',
                    };
                    toastRef.value?.showToast();
                    emit('booking-success');
                    emit('close');

                    setTimeout(() => {
                        router.visit('/bookings');
                    }, 2000);
                },
                onError: (errors: any) => {
                    console.error('Booking error:', errors);
                    toastMessage.value = {
                        title: 'Booking Failed',
                        description: 'Failed to book consultation. Please try again.',
                        variant: 'destructive',
                    };
                    toastRef.value?.showToast();
                },
                onFinish: () => {
                    loading.value = false;
                },
            },
        );
    } catch (error) {
        console.error('Error booking consultation:', error);
        loading.value = false;
    }
}

function closeModal() {
    emit('close');
    selectedSlot.value = null;
}

onMounted(() => {
    if (isOpen.value) {
        fetchSchedules();
    }
});

// Watch for modal opening
watch(
    () => props.open,
    (newValue: boolean) => {
        if (newValue) {
            fetchSchedules();
        }
    },
);
</script>

<template>
    <Toast ref="toastRef" :title="toastMessage.title" :description="toastMessage.description" :variant="toastMessage.variant" />

    <!-- Modal Backdrop -->
    <div v-if="isOpen" class="bg-opacity-50 fixed inset-0 z-50 flex items-center justify-center bg-black" @click="closeModal">
        <div class="m-4 max-h-[90vh] w-full max-w-4xl overflow-hidden rounded-lg bg-gray-800" @click.stop>
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b p-6">
                <h2 class="text-2xl font-bold">Book Consultation</h2>
                <Button variant="ghost" size="sm" @click="closeModal">
                    <span class="sr-only">Close</span>
                    ✕
                </Button>
            </div>

            <!-- Modal Content -->
            <div class="max-h-[calc(90vh-140px)] overflow-y-auto p-6">
                <div v-if="loading" class="py-8 text-center">
                    <div class="mx-auto h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600"></div>
                    <p class="mt-2 text-muted-foreground">Loading available appointments...</p>
                </div>

                <div v-else-if="schedules.length === 0" class="py-8 text-center">
                    <p class="text-muted-foreground">No available appointments found.</p>
                </div>

                <div v-else class="space-y-6">
                    <div v-for="schedule in schedules" :key="schedule.id">
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center justify-between">
                                    <span>{{ dayNames[schedule.day_of_week] }} Appointments</span>
                                    <Badge variant="secondary">{{ schedule.healthcare.name }}</Badge>
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div v-if="schedule.available_slots.length === 0" class="py-4 text-center">
                                    <p class="text-muted-foreground">No available slots for this day in the next 2 weeks.</p>
                                </div>
                                <div v-else class="grid grid-cols-2 gap-2 md:grid-cols-4 lg:grid-cols-6">
                                    <Button
                                        v-for="slot in schedule.available_slots"
                                        :key="`${slot.date}-${slot.time}`"
                                        :variant="
                                            selectedSlot?.schedule_id === schedule.id && selectedSlot?.datetime === slot.datetime
                                                ? 'default'
                                                : 'outline'
                                        "
                                        size="sm"
                                        class="flex h-auto flex-col py-3"
                                        @click="selectSlot(schedule.id, slot)"
                                    >
                                        <span class="text-xs">{{
                                            new Date(slot.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
                                        }}</span>
                                        <span class="font-medium">{{ slot.time }}</span>
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-between border-t p-6">
                <div class="text-sm text-muted-foreground">
                    <span v-if="selectedSlot"> Selected: {{ new Date(selectedSlot.datetime).toLocaleDateString() }} at {{ selectedSlot.time }} </span>
                    <span v-else>Please select an appointment time</span>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="closeModal">Cancel</Button>
                    <Button @click="bookConsultation" :disabled="!selectedSlot || loading" class="px-6">
                        {{ loading ? 'Booking...' : 'Book Consultation' }}
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
