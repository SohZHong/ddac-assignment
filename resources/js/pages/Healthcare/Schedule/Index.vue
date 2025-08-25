<script setup lang="ts">
import axios from '@/axios';
import Icon from '@/components/Icon.vue';
import ScheduleCreateDialog from '@/components/ScheduleCreateDialog.vue';
import ScheduleDeleteDialog from '@/components/ScheduleDeleteDialog.vue';
import Toast from '@/components/Toast.vue';
import Button from '@/components/ui/button/Button.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { LaravelPagination } from '@/types/pagination';
import { Schedule } from '@/types/schedule';
import { CalendarOptions, type DateSelectArg, type EventClickArg, type EventDropArg } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin, { EventResizeDoneArg } from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';
import FullCalendar from '@fullcalendar/vue3';
import { Head, router } from '@inertiajs/vue3';
import { Calendar1 } from 'lucide-vue-next';
import {
    PaginationEllipsis,
    PaginationFirst,
    PaginationLast,
    PaginationList,
    PaginationListItem,
    PaginationNext,
    PaginationPrev,
    PaginationRoot,
} from 'reka-ui';
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
    paginateSchedules: LaravelPagination<Schedule>;
}>();

// const events = ref<Schedule[]>(props.schedules);
const events = ref<Schedule[]>(props.paginateSchedules.data);
const allEvents = ref<Schedule[]>(props.schedules);

const pagination = ref<LaravelPagination<Schedule>>(props.paginateSchedules);
const currentPage = ref(pagination.value.current_page);

const createDialogOpen = ref(false);
const deleteDialogOpen = ref(false);
const tempSelection = ref<{ id?: string; start?: string; end?: string } | null>(null);
const calendarRef = ref<InstanceType<typeof FullCalendar>>();

const toastRef = ref<InstanceType<typeof Toast> | null>(null);
const toastMessage = ref({ title: '', description: '', variant: 'default' as 'default' | 'success' | 'destructive' });

const calendarOptions = ref<CalendarOptions>({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'timeGridWeek',
    timeZone: 'local',
    height: 450, // total calendar height
    contentHeight: 250, // just the event area height
    events: allEvents.value,
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

function handleSelect(info: DateSelectArg) {
    if (createDialogOpen.value) return; // prevent re-opening

    tempSelection.value = { start: info.start.toISOString(), end: info.end.toISOString() };
    createDialogOpen.value = true;
}

const updateScheduleInArray = (array: Schedule[], updated: Schedule) => {
    const index = array.findIndex((item) => item.id === updated.id);
    if (index !== -1) {
        array[index] = updated;
    }
};

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
        .put(route('api.schedule.update', event.id), payload)
        .then((res) => {
            const s = res.data.schedule;

            const updatedSchedule: Schedule = {
                id: s.id,
                start: s.start_time,
                end: s.end_time,
                day_of_week: s.day_of_week,
            };

            // Update paginated events
            updateScheduleInArray(events.value, updatedSchedule);

            // Update all events
            updateScheduleInArray(allEvents.value, updatedSchedule);
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
        .post(route('api.schedule.store'), newSchedule)
        .then((res) => {
            const s = res.data.schedule;

            const data = {
                id: s.id,
                start: s.start_time,
                end: s.end_time,
                day_of_week: s.day_of_week,
            };

            events.value.push(data);
            allEvents.value.push(data);

            toastMessage.value = {
                title: `Schedule Created`,
                description: res.data.message,
                variant: 'success',
            };

            toastRef.value?.showToast();
        })
        .catch((err) => {
            toastMessage.value = {
                title: `Schedule Creation Failed`,
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
        .delete(route('api.schedule.destroy', tempSelection.value.id))
        .then((res) => {
            // remove from local events array
            events.value = events.value.filter((e) => String(e.id) !== String(tempSelection.value?.id));
            allEvents.value = allEvents.value.filter((e) => String(e.id) !== String(tempSelection.value?.id));
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

function goToPage(page: number) {
    if (page === currentPage.value) return; // prevent duplicate navigation
    router.visit(route('healthcare.schedule.index', { page }));
}
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
                        <div class="min-w-[900px]">
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
                    <PaginationRoot
                        :total="pagination.total"
                        :items-per-page="pagination.per_page"
                        :default-page="pagination.current_page"
                        show-edges
                    >
                        <PaginationList v-slot="{ items }">
                            <PaginationFirst
                                class="flex h-9 w-9 items-center justify-center rounded-lg bg-transparent transition hover:bg-white disabled:opacity-50 dark:hover:bg-stone-700/70"
                            >
                                <Icon name="double-arrow-left" icon="radix-icons:double-arrow-left" />
                            </PaginationFirst>
                            <PaginationPrev
                                class="mr-4 flex h-9 w-9 items-center justify-center rounded-lg bg-transparent transition hover:bg-white disabled:opacity-50 dark:hover:bg-stone-700/70"
                            >
                                <Icon name="chevron-left" icon="radix-icons:chevron-left" />
                            </PaginationPrev>
                            <template v-for="(page, index) in items">
                                <PaginationListItem
                                    class="h-9 w-9 rounded-lg border transition hover:bg-white data-[selected]:!bg-white data-[selected]:text-black data-[selected]:shadow-sm dark:border-stone-800 dark:hover:bg-stone-700/70"
                                    v-if="page.type === 'page'"
                                    :key="index"
                                    :value="page.value"
                                    @click="goToPage(page.value)"
                                >
                                    {{ page.value }}
                                </PaginationListItem>

                                <PaginationEllipsis class="flex h-9 w-9 items-center justify-center" v-else :key="page.type" :index="index">
                                    &#8230;
                                </PaginationEllipsis>
                            </template>
                            <PaginationNext
                                class="ml-4 flex h-9 w-9 items-center justify-center rounded-lg bg-transparent transition hover:bg-white disabled:opacity-50 dark:hover:bg-stone-700/70"
                            >
                                <Icon name="chevron-right" icon="radix-icons:chevron-right" />
                            </PaginationNext>
                            <PaginationLast
                                class="flex h-9 w-9 items-center justify-center rounded-lg bg-transparent transition hover:bg-white disabled:opacity-50 dark:hover:bg-stone-700/70"
                            >
                                <Icon name="double-arrow-right" icon="radix-icons:double-arrow-right" />
                            </PaginationLast>
                        </PaginationList>
                    </PaginationRoot>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
