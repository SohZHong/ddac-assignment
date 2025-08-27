<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, Clock, MapPin, Megaphone, Users, Video } from 'lucide-vue-next';

interface Registration {
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
}

interface Props {
    event: EventData;
}

const props = defineProps<Props>();

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
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-for="r in event.registrations" :key="r.id">
                                            <TableCell>{{ r.user_name }}</TableCell>
                                            <TableCell>{{ r.user_email }}</TableCell>
                                            <TableCell>
                                                <Badge variant="outline">{{ r.attended ? 'Attended' : 'Registered' }}</Badge>
                                            </TableCell>
                                            <TableCell>{{ formatDateTime(r.registered_at) }}</TableCell>
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
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
