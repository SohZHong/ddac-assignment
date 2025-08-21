<script setup lang="ts">
import axios from '@/axios';
import ScheduleCreateDialog from '@/components/ScheduleCreateDialog.vue';
import ScheduleDeleteDialog from '@/components/ScheduleDeleteDialog.vue';
import Toast from '@/components/Toast.vue';
import Button from '@/components/ui/button/Button.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Schedule } from '@/types/schedule';
import { CalendarOptions, type DateSelectArg, type EventClickArg, type EventDropArg } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin, { EventResizeDoneArg } from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';
import FullCalendar from '@fullcalendar/vue3';
import { Head } from '@inertiajs/vue3';
import { Calendar1 } from 'lucide-vue-next';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Healthcare',
        href: '/healthcare',
    },
    {
        title: 'Schedule',
        href: '#',
    },
];

const props = defineProps<{
    schedules: Schedule[];
}>();

const events = ref<Schedule[]>(props.schedules);

function handleSelect(info: DateSelectArg) {
    if (createDialogOpen.value) return; // prevent re-opening

    tempSelection.value = { start: info.start.toISOString(), end: info.end.toISOString() };
    createDialogOpen.value = true;
}

async function updateSchedule({ event, revert }: EventDropArg | EventResizeDoneArg) {
    const start = event.start;
    const end = event.end;
    // Check if start_time is before now
    if (start && start < new Date()) {
        toastMessage.value = {
            title: `Invalid Schedule`,
            description: `You cannot update a schedule to a past time.`,
            variant: 'destructive',
        };
        toastRef.value?.showToast();

        revert();
        return;
    }

    const payload = {
        id: event.id,
        start_time: start?.toISOString(),
        end_time: end?.toISOString(),
        day_of_week: start!.getUTCDay(),
    };

    await axios
        .put(`/api/schedule/${event.id}`, payload)
        .then((res) => {
            toastMessage.value = {
                title: `Schedule Updated`,
                description: res.data.message,
                variant: 'success',
            };

            toastRef.value?.showToast();
        })
        .catch((err) => {
            console.error('Failed to update schedule', err);

            toastMessage.value = {
                title: `Schedule Update Failed`,
                description: err.message,
                variant: 'destructive',
            };

            toastRef.value?.showToast();
            revert();
        });
}

async function handleClick({ event }: EventClickArg) {
    if (deleteDialogOpen.value) return; // prevent re-opening

    tempSelection.value = { id: event.id };
    deleteDialogOpen.value = true;
}

async function confirmSchedule() {
    if (!tempSelection.value || !tempSelection.value.start || !tempSelection.value.end) return;

    const start = new Date(tempSelection.value.start);
    const end = new Date(tempSelection.value.end);
    // Check if start_time is before now
    if (start < new Date()) {
        toastMessage.value = {
            title: `Invalid Schedule`,
            description: `You cannot create a schedule from a past time.`,
            variant: 'destructive',
        };
        toastRef.value?.showToast();
        return;
    }

    const newSchedule: Schedule = {
        start: start.toISOString(),
        end: end.toISOString(),
        day_of_week: start.getUTCDay(),
    };
    axios
        .post('/api/schedule', newSchedule)
        .then((res) => {
            const s = res.data.schedule;
            console.log(s);
            events.value.push({
                id: s.id,
                start: s.start_time,
                end: s.end_time,
                day_of_week: s.day_of_week,
            });

            toastMessage.value = {
                title: `Schedule Created`,
                description: res.data.message,
                variant: 'success',
            };

            toastRef.value?.showToast();
        })
        .catch((err) => {
            toastMessage.value = {
                title: `Schedule Deletion Failed`,
                description: err.message,
                variant: 'destructive',
            };

            toastRef.value?.showToast();
        })
        .finally(() => {
            tempSelection.value = null;
        });
}

async function deleteSchedule() {
    if (!tempSelection.value || !tempSelection.value.id) return;

    await axios
        .delete(`/api/schedule/${tempSelection.value.id}`)
        .then((res) => {
            // remove from local events array
            events.value = events.value.filter((e) => String(e.id) !== String(tempSelection.value?.id));
            // remove from calendar view
            const calendarApi = calendarRef.value!.getApi();
            const event = calendarApi.getEventById(tempSelection.value!.id!);
            if (event) {
                event.remove();
            }

            toastMessage.value = {
                title: `Schedule Deleted`,
                description: res.data.message,
                variant: 'success',
            };

            toastRef.value?.showToast();
        })
        .catch((err) => {
            toastMessage.value = {
                title: `Schedule Deletion Failed`,
                description: err.message,
                variant: 'destructive',
            };

            toastRef.value?.showToast();
            console.error(err);
        })
        .finally(() => {
            tempSelection.value = null;
        });
}

const calendarOptions = ref<CalendarOptions>({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'timeGridWeek',
    timeZone: 'local',
    height: 450, // total calendar height
    contentHeight: 250, // just the event area height
    events: events.value,
    selectable: true, // allow dragging across cells
    selectMirror: true, // show placeholder when selecting
    nowIndicator: true,
    editable: true,
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay',
    },

    // Triggered when a range is selected
    select: handleSelect,

    // When event is dragged to a new time
    eventDrop: async (info: EventDropArg) => {
        await updateSchedule(info);
    },

    // When event is resized to change duration
    eventResize: async (info: EventResizeDoneArg) => {
        await updateSchedule(info);
    },

    // When event is clicked to delete
    eventClick: handleClick,
});

const createDialogOpen = ref(false);
const deleteDialogOpen = ref(false);
const tempSelection = ref<{ id?: string; start?: string; end?: string } | null>(null);
const calendarRef = ref<InstanceType<typeof FullCalendar>>();

const toastRef = ref<InstanceType<typeof Toast> | null>(null);
const toastMessage = ref({ title: '', description: '', variant: 'default' as 'default' | 'success' | 'destructive' });
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
    <Head title="Healthcare Dashboard" />
    <!-- Toast -->
    <Toast ref="toastRef" :title="toastMessage.title" :description="toastMessage.description" :variant="toastMessage.variant" />
    <ScheduleCreateDialog v-model:open="createDialogOpen" :start="tempSelection?.start" :end="tempSelection?.end" @confirm="confirmSchedule" />
    <ScheduleDeleteDialog v-model:open="deleteDialogOpen" :start="tempSelection?.start" :end="tempSelection?.end" @confirm="deleteSchedule" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Schedule Management</h1>
                    <p class="text-muted-foreground">Manage your availability slots and assign them</p>
                </div>
            </div>

            <!-- Schedule Calendar -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Calendar1 class="h-5 w-5" />
                        Availability Slots
                    </CardTitle>
                    <CardDescription> Click and Drag to Add Availability Slots </CardDescription>
                </CardHeader>
                <CardContent>
                    <FullCalendar ref="calendarRef" :options="calendarOptions" />
                </CardContent>
            </Card>

            <!-- Schedule Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Schedule Slots</CardTitle>
                    <CardDescription>List of all availability slots</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto rounded-lg border">
                        <div class="min-w-[700px]">
                            <!-- Table Header -->
                            <div class="grid grid-cols-4 bg-muted text-sm font-semibold text-muted-foreground">
                                <div class="px-4 py-2">Start</div>
                                <div class="px-4 py-2">End</div>
                                <div class="px-4 py-2">Day of Week</div>
                                <div class="px-4 py-2 text-right">Actions</div>
                            </div>

                            <!-- Table Rows -->
                            <div
                                v-for="schedule in events"
                                :key="schedule.id"
                                class="grid grid-cols-4 items-center border-t bg-white text-sm hover:bg-stone-50 dark:bg-black dark:hover:bg-accent"
                            >
                                <!-- Start -->
                                <div class="px-4 py-3 font-medium">
                                    {{ new Date(schedule.start).toLocaleString() }}
                                </div>

                                <!-- End -->
                                <div class="px-4 py-3 text-muted-foreground">
                                    {{ new Date(schedule.end).toLocaleString() }}
                                </div>

                                <!-- Day of Week -->
                                <div class="px-4 py-3">
                                    {{ ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][schedule.day_of_week] }}
                                </div>

                                <!-- Actions -->
                                <div class="flex justify-end px-4 py-3">
                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        @click="
                                            () => {
                                                tempSelection = { id: schedule.id };
                                                deleteDialogOpen = true;
                                            }
                                        "
                                    >
                                        Delete
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
