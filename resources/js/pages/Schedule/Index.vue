<script setup lang="ts">
import axios from '@/axios';
import BookingDialog from '@/components/BookingDialog.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { FreeSchedule } from '@/types/schedule';
import type { CalendarOptions, EventClickArg } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';
import FullCalendar from '@fullcalendar/vue3';
import { Head } from '@inertiajs/vue3';
import { Calendar, Calendar1 } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    schedules: FreeSchedule[];
}>();
console.log(props.schedules);
const selectedSchedule = ref<FreeSchedule | null>(null);
const dialogOpen = ref(false);
const events = ref<FreeSchedule[]>(props.schedules);
const calendarRef = ref<InstanceType<typeof FullCalendar>>();

function handleCalendarClick({ event }: EventClickArg) {
    selectedSchedule.value = {
        id: event.id,
        schedule_id: event.extendedProps.schedule_id as string,
        start: event.start!.toISOString(), // keep ISO, not UTC string
        end: event.end!.toISOString(),
        day_of_week: event.start!.getDay(),
    };
    dialogOpen.value = true;
}

function handleButtonClick(e: MouseEvent, schedule: FreeSchedule) {
    e.preventDefault();

    selectedSchedule.value = schedule;
    dialogOpen.value = true;
}

async function handleConfirmBooking(e: { eventId: string; scheduleId: string; startTime: string; endTime: string }) {
    const payload = {
        schedule_id: e.scheduleId,
        start_time: e.startTime,
        end_time: e.endTime,
    };

    await axios
        .post(route('api.booking.store'), payload)
        .then((res) => {
            dialogOpen.value = false;
            console.log('Booking created:', res.data);

            // Remove from events
            events.value = events.value.filter((data) => data.id !== e.eventId);

            // remove from local events array
            const calendarApi = calendarRef.value!.getApi();
            const calendarEvent = calendarApi.getEventById(e.eventId);
            if (calendarEvent) {
                calendarEvent.remove();
            }
        })
        .catch((err) => {
            console.error('Booking failed:', err);
        });
}

const calendarOptions: CalendarOptions = {
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'timeGridWeek',
    timeZone: 'local',
    height: 600,
    events: events.value,
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay',
    },
    editable: false,
    selectable: false,

    // When event is clicked to book
    eventClick: handleCalendarClick,
};
</script>
<style scoped>
:deep(.fc button),
:deep(.fc h2),
:deep(.fc td),
:deep(.fc thead) {
    font-size: 0.875rem !important;
    font-weight: 600;
    line-height: calc(1.25 / 0.875) !important;
}

:deep(.fc .fc-event-title) {
    font-size: 0.875rem !important;
    font-weight: 600;
    line-height: calc(1.25 / 0.875) !important;
}
</style>
<template>
    <Head title="Available Schedules" />

    <AppLayout>
        <!-- Booking Dialog -->
        <BookingDialog :open="dialogOpen" :schedule="selectedSchedule" @close="dialogOpen = false" @confirm="handleConfirmBooking" />
        <div class="flex flex-col gap-6 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Available Schedules</h1>
                    <p class="text-muted-foreground">Browse consultant availability</p>
                </div>
            </div>

            <!-- Calendar -->
            <div class="rounded-lg border bg-accent">
                <div class="border-b px-6 py-4">
                    <div class="flex items-center gap-2 text-lg font-semibold">
                        <Calendar1 class="h-5 w-5" />
                        Consultant Schedules
                    </div>
                </div>
                <div class="p-4">
                    <FullCalendar ref="calendarRef" :options="calendarOptions" />
                </div>
            </div>

            <!-- List View -->
            <h2 class="mt-8 text-xl font-bold">List of Schedules</h2>
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <Card v-for="schedule in events" :key="schedule.id">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">{{ schedule.title }}</CardTitle>
                        <Calendar class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text- xl font-bold">
                            {{ new Date(schedule.start).toLocaleString() }}
                            —
                            {{ new Date(schedule.end).toLocaleString() }}
                        </div>
                        <Button variant="outline" class="mt-4 w-full" @click="(e: MouseEvent) => handleButtonClick(e, schedule)"> Book Now </Button>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
