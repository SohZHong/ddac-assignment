<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Schedule } from '@/types/schedule';
import type { CalendarOptions } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';
import FullCalendar from '@fullcalendar/vue3';
import { Head } from '@inertiajs/vue3';
import { Calendar1 } from 'lucide-vue-next';

const props = defineProps<{
    schedules: Schedule[];
}>();

const calendarOptions: CalendarOptions = {
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'timeGridWeek',
    timeZone: 'local',
    height: 600,
    events: props.schedules,
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay',
    },
    editable: false,
    selectable: false,
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
                    <FullCalendar :options="calendarOptions" />
                </div>
            </div>

            <!-- List View -->
            <div class="mt-8">
                <h2 class="mb-4 text-xl font-bold">List of Schedules</h2>
                <div class="grid gap-4 md:grid-cols-2">
                    <div v-for="schedule in schedules" :key="schedule.id" class="rounded-lg border p-4 hover:bg-muted/50">
                        <h3 class="font-semibold">{{ schedule.title }}</h3>
                        <p class="text-sm text-gray-600">
                            {{ new Date(schedule.start).toLocaleString() }}
                            —
                            {{ new Date(schedule.end).toLocaleString() }}
                        </p>
                        <p class="text-sm text-gray-500">Consultant: {{ schedule.title }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
