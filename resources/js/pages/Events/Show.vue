<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { UserRole } from '@/types/role';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, Clock, MapPin, Megaphone, Users, Video } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Registration {
    user_id: number;
    id: number;
    user_name: string;
    user_email: string;
    registered_at: string;
    attended?: boolean;
}

interface Attendance {
    id: number;
    user_name: string;
    user_email: string;
    attended_at: string;
}

interface CampaignLite {
    id: number;
    title: string;
}

interface EventData {
    id: number;
    title: string;
    description: string;
    type: string;
    status: string;
    start_datetime: string;
    end_datetime: string;
    location: string | null;
    online_meeting_url: string | null;
    capacity: number | null;
    is_online: boolean;
    requires_registration: boolean;
    campaign: CampaignLite | null;
    creator: string;
    created_at: string;
    updated_at: string;
    registration_count: number;
    attendance_count: number;
    remaining_capacity: number | null;
    is_at_capacity: boolean;
    duration_minutes: number;
    registrations: Registration[];
    attendances: Attendance[];
    user_registration: { id: number; status: string } | null;
    can_register: boolean;
    user_attendance: { id: number; status: string; check_in_time: string | null } | null;
    can_check_in: boolean;
}

interface Props {
    event: EventData;
}

const props = defineProps<Props>();
const page = usePage();
const currentUser = computed(() => page.props.auth?.user);
const isManagerOrAdmin = computed(
    () => currentUser.value && (currentUser.value.role === UserRole.HEALTH_CAMPAIGN_MANAGER || currentUser.value.role === UserRole.SYSTEM_ADMIN),
);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Events',
        href: '/events',
    },
    {
        title: props.event.title,
        href: `/events/${props.event.id}`,
    },
];

const formatDateTime = (dateString: string) => new Date(dateString).toLocaleString();
const formatTime = (dateString: string) => new Date(dateString).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'published':
            return 'default';
        case 'draft':
            return 'secondary';
        case 'ongoing':
            return 'outline';
        case 'completed':
            return 'outline';
        case 'cancelled':
            return 'destructive';
        default:
            return 'outline';
    }
};

const goBack = () => router.visit('/events');

const register = () => {
    router.post(`/events/${props.event.id}/registrations`);
};

const unregister = () => {
    router.delete(`/events/${props.event.id}/registrations`);
};

const checkIn = () => {
    router.post(`/events/${props.event.id}/attendances`);
};

const undoCheckIn = () => {
    router.delete(`/events/${props.event.id}/attendances`);
};

// Manager/Admin registration management
const manageUserId = ref('');
const addRegistrationById = () => {
    const id = parseInt(manageUserId.value, 10);
    if (!id) return;
    router.post(`/events/${props.event.id}/registrations`, { user_id: id });
};
const removeRegistrationById = (userId?: number) => {
    const id = userId ?? parseInt(manageUserId.value, 10);
    if (!id) return;
    router.delete(`/events/${props.event.id}/registrations`, { data: { user_id: id } });
};

// Registration status management (manager/admin)
const registrationStatuses = [
    { value: 'registered', label: 'Registered' },
    { value: 'confirmed', label: 'Confirmed' },
    { value: 'cancelled', label: 'Cancelled' },
    { value: 'waitlisted', label: 'Waitlisted' },
];

const updateRegistrationStatus = (registrationId: number, status: string) => {
    router.patch(`/events/${props.event.id}/registrations/${registrationId}`, { status });
};
</script>

<template>
    <Head :title="`${event.title} - Event Details`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="outline" size="sm" @click="goBack">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back
                    </Button>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">{{ event.title }}</h1>
                        <p class="text-muted-foreground">{{ event.type }}</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <div class="space-y-6 md:col-span-2">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Megaphone class="h-5 w-5" />
                                Event Overview
                            </CardTitle>
                            <CardDescription>Details for this event</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex items-center gap-3">
                                <Badge :variant="getStatusBadge(event.status)">
                                    {{ event.status.charAt(0).toUpperCase() + event.status.slice(1) }}
                                </Badge>
                                <span class="text-sm text-muted-foreground">Created by {{ event.creator }}</span>
                            </div>

                            <div>
                                <h3 class="mb-2 font-medium">Description</h3>
                                <p class="text-muted-foreground">{{ event.description }}</p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="flex items-center gap-3">
                                    <Calendar class="h-4 w-4 text-muted-foreground" />
                                    <div class="text-sm">
                                        <div>{{ formatDateTime(event.start_datetime) }}</div>
                                        <div class="text-muted-foreground">to {{ formatDateTime(event.end_datetime) }}</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <Clock class="h-4 w-4 text-muted-foreground" />
                                    <div class="text-sm">
                                        Duration: {{ Math.round(event.duration_minutes / 60) }}h {{ event.duration_minutes % 60 }}m
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <MapPin v-if="!event.is_online" class="h-4 w-4 text-muted-foreground" />
                                    <Video v-else class="h-4 w-4 text-muted-foreground" />
                                    <div class="text-sm">
                                        <div v-if="event.is_online">Online</div>
                                        <div v-else>{{ event.location || 'TBD' }}</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <Users class="h-4 w-4 text-muted-foreground" />
                                    <div class="text-sm">
                                        <div>Capacity: {{ event.capacity ?? 'Unlimited' }}</div>
                                        <div v-if="event.capacity" class="text-muted-foreground">Remaining: {{ event.remaining_capacity }}</div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Registrations ({{ event.registration_count }})</CardTitle>
                            <CardDescription>People who have registered</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="mb-3 flex justify-end gap-2">
                                <Button variant="outline" as-child>
                                    <a :href="`/events/${event.id}/registrations.csv`" target="_blank">Export Registrations CSV</a>
                                </Button>
                                <Button variant="outline" as-child>
                                    <a :href="`/events/${event.id}/attendances.csv`" target="_blank">Export Attendances CSV</a>
                                </Button>
                            </div>
                            <div v-if="event.registrations.length === 0" class="py-8 text-center">
                                <Users class="mx-auto h-12 w-12 text-muted-foreground" />
                                <p class="mt-2 text-sm text-muted-foreground">No registrations yet</p>
                            </div>
                            <div v-else class="rounded-md border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Name</TableHead>
                                            <TableHead>Email</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead>Registered At</TableHead>
                                            <TableHead v-if="isManagerOrAdmin">Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-for="r in event.registrations" :key="r.id">
                                            <TableCell>{{ r.user_name }}</TableCell>
                                            <TableCell>{{ r.user_email }}</TableCell>
                                            <TableCell>
                                                <div class="flex items-center gap-2">
                                                    <Badge v-if="r.attended" variant="outline">Attended</Badge>
                                                    <div v-else-if="isManagerOrAdmin" class="min-w-[160px]">
                                                        <Select
                                                            :model-value="(r as any).status || 'registered'"
                                                            @update:modelValue="(v) => updateRegistrationStatus(r.id, v)"
                                                        >
                                                            <SelectTrigger>
                                                                <SelectValue placeholder="Select status" />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                <SelectItem v-for="opt in registrationStatuses" :key="opt.value" :value="opt.value">
                                                                    {{ opt.label }}
                                                                </SelectItem>
                                                            </SelectContent>
                                                        </Select>
                                                    </div>
                                                    <Badge v-else variant="outline">Registered</Badge>
                                                </div>
                                            </TableCell>
                                            <TableCell>{{ formatDateTime(r.registered_at) }}</TableCell>
                                            <TableCell v-if="isManagerOrAdmin">
                                                <div class="flex gap-2">
                                                    <Button
                                                        v-if="!r.attended"
                                                        size="sm"
                                                        @click="router.post(`/events/${event.id}/attendances`, { user_id: r.user_id })"
                                                    >
                                                        Check In
                                                    </Button>
                                                    <Button
                                                        v-else
                                                        size="sm"
                                                        variant="outline"
                                                        @click="router.delete(`/events/${event.id}/attendances`, { data: { user_id: r.user_id } })"
                                                    >
                                                        Undo Check-in
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Quick Actions</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2">
                            <Button v-if="!event.user_registration && event.can_register" class="w-full justify-start" @click="register">
                                Register
                            </Button>
                            <Button v-else-if="event.user_registration" variant="outline" class="w-full justify-start" @click="unregister">
                                Unregister
                            </Button>
                            <Button v-else variant="outline" class="w-full justify-start" disabled> Registration Closed or Full </Button>
                            <div class="my-2 h-px bg-border" />
                            <Button v-if="event.can_check_in" class="w-full justify-start" @click="checkIn"> Check In </Button>
                            <Button v-else-if="event.user_attendance" variant="outline" class="w-full justify-start" @click="undoCheckIn">
                                Undo Check-in
                            </Button>
                            <Button v-else variant="outline" class="w-full justify-start" disabled> Check-in Unavailable </Button>
                        </CardContent>
                    </Card>

                    <Card v-if="isManagerOrAdmin">
                        <CardHeader>
                            <CardTitle>Manage Registrations</CardTitle>
                            <CardDescription>Add or remove participants by User ID</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div class="space-y-2">
                                <Label for="user_id">User ID</Label>
                                <Input id="user_id" v-model="manageUserId" placeholder="Enter user ID" />
                            </div>
                            <div class="flex gap-2">
                                <Button @click="addRegistrationById">Add</Button>
                                <Button variant="outline" @click="removeRegistrationById()">Remove</Button>
                            </div>
                            <div class="text-xs text-muted-foreground">Tip: Use the list below to remove a specific attendee directly.</div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
