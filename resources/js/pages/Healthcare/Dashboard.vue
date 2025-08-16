<script setup lang="ts">
import axios from '@/axios';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type User } from '@/types';
import { Schedule } from '@/types/schedule';
import type { DateSelectArg } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';
import FullCalendar from '@fullcalendar/vue3';
import { Head, usePage } from '@inertiajs/vue3';
import { Calendar, Calendar1, Clock, FileText, Heart, TrendingUp, Users } from 'lucide-vue-next';
import { DialogClose, DialogContent, DialogDescription, DialogOverlay, DialogPortal, DialogRoot, DialogTitle } from 'reka-ui';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Healthcare',
        href: '/healthcare',
    },
];

const page = usePage();
const user = page.props.auth.user as User;

const props = defineProps<{
    schedules: Schedule[];
}>();

const events = ref<Schedule[]>(props.schedules);

function handleSelect(info: DateSelectArg) {
    tempSelection.value = { start: info.startStr, end: info.endStr };
    titleInput.value = '';
    dialogOpen.value = true;
}

async function confirmSchedule() {
    if (!tempSelection.value || !titleInput.value.trim()) return;

    const newSchedule: Schedule = {
        title: titleInput.value.trim(),
        start: tempSelection.value.start,
        end: tempSelection.value.end,
        day_of_week: new Date(tempSelection.value.start).getDay(),
    };

    events.value.push(newSchedule);

    axios
        .post('/api/schedule', newSchedule)
        .then((res) => {
            events.value.push(res.data.schedule);
            console.log(res);
        })
        .catch((err) => console.error(err));

    dialogOpen.value = false;
}

const calendarOptions = ref({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'timeGridWeek',
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
});

const dialogOpen = ref(false);
const tempSelection = ref<{ start: string; end: string } | null>(null);
const titleInput = ref('');
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
    <DialogRoot v-model:open="dialogOpen">
        <DialogPortal>
            <DialogOverlay class="bg-opacity-50 fixed inset-0" />
            <DialogContent class="fixed top-1/3 left-1/2 w-96 -translate-x-1/2 transform rounded-lg bg-black p-6 shadow-lg">
                <DialogTitle class="text-lg font-semibold">Add Availability Slot</DialogTitle>
                <DialogDescription class="mt-2">Enter a title for this slot:</DialogDescription>

                <Input v-model="titleInput" type="text" placeholder="e.g. Morning Shift" class="mt-4 w-full rounded border px-2 py-1" />

                <div class="mt-4 flex justify-end space-x-2">
                    <DialogClose asChild>
                        <Button class="rounded border px-4 py-2">Cancel</Button>
                    </DialogClose>
                    <Button class="rounded bg-blue-600 px-4 py-2" @click="confirmSchedule">Confirm</Button>
                </div>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
    <Head title="Healthcare Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Healthcare Dashboard</h1>
                    <p class="text-muted-foreground">Manage patient care and health services</p>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <!-- Patient Management -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Patient Management</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">247</div>
                        <p class="text-xs text-muted-foreground">+12% from last month</p>
                        <Button variant="outline" class="mt-4 w-full" disabled> View Patients </Button>
                    </CardContent>
                </Card>

                <!-- Appointments -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Today's Appointments</CardTitle>
                        <Calendar class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">8</div>
                        <p class="text-xs text-muted-foreground">2 pending confirmations</p>
                        <Button variant="outline" class="mt-4 w-full" disabled> View Schedule </Button>
                    </CardContent>
                </Card>

                <!-- Health Records -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Health Records</CardTitle>
                        <FileText class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">1,432</div>
                        <p class="text-xs text-muted-foreground">Digital health records</p>
                        <Button variant="outline" class="mt-4 w-full" disabled> Access Records </Button>
                    </CardContent>
                </Card>
            </div>

            <!-- Quick Actions -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Heart class="h-5 w-5 text-red-500" />
                        Quick Actions
                    </CardTitle>
                    <CardDescription> Common healthcare tasks and patient management </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <Button variant="outline" class="h-auto justify-start py-4" disabled>
                            <div class="flex flex-col items-start gap-1">
                                <div class="flex items-center gap-2">
                                    <Users class="h-4 w-4" />
                                    <span class="font-medium">New Patient</span>
                                </div>
                                <span class="text-xs text-muted-foreground">Register new patient</span>
                            </div>
                        </Button>
                        <Button variant="outline" class="h-auto justify-start py-4" disabled>
                            <div class="flex flex-col items-start gap-1">
                                <div class="flex items-center gap-2">
                                    <Calendar class="h-4 w-4" />
                                    <span class="font-medium">Schedule</span>
                                </div>
                                <span class="text-xs text-muted-foreground">Book appointment</span>
                            </div>
                        </Button>
                        <Button variant="outline" class="h-auto justify-start py-4" disabled>
                            <div class="flex flex-col items-start gap-1">
                                <div class="flex items-center gap-2">
                                    <FileText class="h-4 w-4" />
                                    <span class="font-medium">Prescription</span>
                                </div>
                                <span class="text-xs text-muted-foreground">Create prescription</span>
                            </div>
                        </Button>
                        <Button variant="outline" class="h-auto justify-start py-4" disabled>
                            <div class="flex flex-col items-start gap-1">
                                <div class="flex items-center gap-2">
                                    <TrendingUp class="h-4 w-4" />
                                    <span class="font-medium">Reports</span>
                                </div>
                                <span class="text-xs text-muted-foreground">View analytics</span>
                            </div>
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Schedule Calendar -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Calendar1 class="h-5 w-5" />
                        Schedule
                    </CardTitle>
                    <CardDescription> Click and Drag to Add Availability Slots </CardDescription>
                </CardHeader>
                <CardContent>
                    <FullCalendar :options="calendarOptions" />
                </CardContent>
            </Card>

            <!-- Recent Activity -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Clock class="h-5 w-5" />
                        Recent Activity
                    </CardTitle>
                    <CardDescription> Latest patient interactions and healthcare events </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div class="flex items-center gap-4 rounded-lg border p-4">
                            <div class="h-2 w-2 rounded-full bg-green-500"></div>
                            <div class="flex-1">
                                <p class="font-medium">Patient consultation completed</p>
                                <p class="text-sm text-muted-foreground">John Doe - 2 hours ago</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 rounded-lg border p-4">
                            <div class="h-2 w-2 rounded-full bg-blue-500"></div>
                            <div class="flex-1">
                                <p class="font-medium">New appointment scheduled</p>
                                <p class="text-sm text-muted-foreground">Jane Smith - 4 hours ago</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 rounded-lg border p-4">
                            <div class="h-2 w-2 rounded-full bg-orange-500"></div>
                            <div class="flex-1">
                                <p class="font-medium">Lab results uploaded</p>
                                <p class="text-sm text-muted-foreground">Michael Johnson - 6 hours ago</p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Professional Info -->
            <div class="mt-auto">
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center gap-4">
                            <div class="flex-1">
                                <p class="text-sm text-muted-foreground">
                                    Healthcare Professional: <span class="font-medium text-foreground">{{ user.name }}</span>
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    Role: <span class="font-medium text-foreground">{{ user.role_label }}</span>
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
