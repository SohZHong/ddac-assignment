<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Calendar, Clock, Edit, Eye, MapPin, Plus, Search, Trash2, Video } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Campaign {
    id: number;
    title: string;
}

interface Event {
    id: number;
    title: string;
    description: string;
    type: string;
    status: string;
    start_datetime: string;
    end_datetime: string;
    location: string;
    online_meeting_url: string;
    capacity: number;
    is_online: boolean;
    requires_registration: boolean;
    campaign: Campaign | null;
    creator: string;
    created_at: string;
    updated_at: string;
    registration_count: number;
    attendance_count: number;
    remaining_capacity: number | null;
    is_at_capacity: boolean;
    duration_minutes: number;
}

interface Props {
    events: Event[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Events',
        href: '/events',
    },
    {
        title: 'All Events',
        href: '/events',
    },
];

// Search and filter state
const searchQuery = ref('');
const statusFilter = ref('all');
const typeFilter = ref('all');
const campaignFilter = ref('all');

// Computed filtered events
const filteredEvents = computed(() => {
    let filtered = props.events;

    // Search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(
            (event) =>
                event.title.toLowerCase().includes(query) ||
                event.description.toLowerCase().includes(query) ||
                event.type.toLowerCase().includes(query) ||
                event.location?.toLowerCase().includes(query),
        );
    }

    // Status filter
    if (statusFilter.value !== 'all') {
        filtered = filtered.filter((event) => event.status === statusFilter.value);
    }

    // Type filter
    if (typeFilter.value !== 'all') {
        filtered = filtered.filter((event) => event.type === typeFilter.value);
    }

    // Campaign filter
    if (campaignFilter.value !== 'all') {
        filtered = filtered.filter((event) => event.campaign?.id === parseInt(campaignFilter.value));
    }

    return filtered;
});

// Get unique event types for filter
const eventTypes = computed(() => {
    const types = [...new Set(props.events.map((e) => e.type))];
    return types.sort();
});

// Get unique campaigns for filter
const campaigns = computed(() => {
    const campaignIds = [...new Set(props.events.map((e) => e.campaign?.id).filter(Boolean))];
    return props.events
        .filter((e) => e.campaign && campaignIds.includes(e.campaign.id))
        .map((e) => e.campaign!)
        .filter((campaign, index, self) => index === self.findIndex((c) => c.id === campaign.id));
});

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

const viewEvent = (eventId: number) => {
    router.visit(`/events/${eventId}`);
};

const editEvent = (eventId: number) => {
    router.visit(`/events/${eventId}/edit`);
};

const deleteEvent = (eventId: number) => {
    if (confirm('Are you sure you want to delete this event? This action cannot be undone.')) {
        router.delete(`/events/${eventId}`);
    }
};

const createNewEvent = () => {
    router.visit('/events/create');
};

const formatDateTime = (dateString: string) => {
    return new Date(dateString).toLocaleString();
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
};

const formatTime = (dateString: string) => {
    return new Date(dateString).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <Head title="All Events" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">All Events</h1>
                    <p class="text-muted-foreground">Manage and monitor all health events and webinars</p>
                </div>
                <Button @click="createNewEvent">
                    <Plus class="mr-2 h-4 w-4" />
                    New Event
                </Button>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Search class="h-5 w-5" />
                        Search & Filters
                    </CardTitle>
                    <CardDescription> Find events by title, description, or filter by status, type, and campaign </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Search</label>
                            <Input v-model="searchQuery" placeholder="Search events..." class="w-full" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Status</label>
                            <Select v-model="statusFilter">
                                <SelectTrigger>
                                    <SelectValue placeholder="All statuses" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Statuses</SelectItem>
                                    <SelectItem value="draft">Draft</SelectItem>
                                    <SelectItem value="published">Published</SelectItem>
                                    <SelectItem value="ongoing">Ongoing</SelectItem>
                                    <SelectItem value="completed">Completed</SelectItem>
                                    <SelectItem value="cancelled">Cancelled</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Type</label>
                            <Select v-model="typeFilter">
                                <SelectTrigger>
                                    <SelectValue placeholder="All types" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Types</SelectItem>
                                    <SelectItem v-for="type in eventTypes" :key="type" :value="type">
                                        {{ type }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Campaign</label>
                            <Select v-model="campaignFilter">
                                <SelectTrigger>
                                    <SelectValue placeholder="All campaigns" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Campaigns</SelectItem>
                                    <SelectItem v-for="campaign in campaigns" :key="campaign.id" :value="campaign.id.toString()">
                                        {{ campaign.title }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Events Table -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Calendar class="h-5 w-5" />
                        Events ({{ filteredEvents.length }})
                    </CardTitle>
                    <CardDescription> All health events, webinars, and workshops </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="filteredEvents.length === 0" class="py-8 text-center">
                        <Calendar class="mx-auto h-12 w-12 text-muted-foreground" />
                        <h3 class="mt-2 text-sm font-medium text-muted-foreground">
                            {{ props.events.length === 0 ? 'No events yet' : 'No events match your filters' }}
                        </h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{
                                props.events.length === 0
                                    ? 'Get started by creating your first health event.'
                                    : 'Try adjusting your search or filters.'
                            }}
                        </p>
                        <div v-if="props.events.length === 0" class="mt-6">
                            <Button @click="createNewEvent">
                                <Plus class="mr-2 h-4 w-4" />
                                Create Event
                            </Button>
                        </div>
                    </div>
                    <div v-else class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Event</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Date & Time</TableHead>
                                    <TableHead>Location</TableHead>
                                    <TableHead>Capacity</TableHead>
                                    <TableHead>Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="event in filteredEvents" :key="event.id">
                                    <TableCell>
                                        <div>
                                            <div class="font-medium">{{ event.title }}</div>
                                            <div class="line-clamp-2 text-sm text-muted-foreground">
                                                {{ event.description }}
                                            </div>
                                            <div class="mt-1 text-xs text-muted-foreground">Created by {{ event.creator }}</div>
                                            <div v-if="event.campaign" class="text-xs text-muted-foreground">
                                                Campaign: {{ event.campaign.title }}
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <Calendar class="h-4 w-4 text-muted-foreground" />
                                            {{ event.type }}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="getStatusBadge(event.status)">
                                            {{ event.status.charAt(0).toUpperCase() + event.status.slice(1) }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <Clock class="h-4 w-4 text-muted-foreground" />
                                            <div class="text-sm">
                                                <div>{{ formatDate(event.start_datetime) }}</div>
                                                <div class="text-muted-foreground">
                                                    {{ formatTime(event.start_datetime) }} - {{ formatTime(event.end_datetime) }}
                                                </div>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <MapPin v-if="!event.is_online" class="h-4 w-4 text-muted-foreground" />
                                            <Video v-else class="h-4 w-4 text-muted-foreground" />
                                            <div class="text-sm">
                                                <div v-if="event.is_online">Online</div>
                                                <div v-else>{{ event.location || 'TBD' }}</div>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="text-sm">
                                            <div v-if="event.capacity">
                                                {{ event.registration_count }}/{{ event.capacity }}
                                                <div class="text-muted-foreground">{{ event.remaining_capacity }} left</div>
                                            </div>
                                            <div v-else>Unlimited</div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <Button variant="outline" size="sm" @click="viewEvent(event.id)">
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                            <Button variant="outline" size="sm" @click="editEvent(event.id)">
                                                <Edit class="h-4 w-4" />
                                            </Button>
                                            <Button variant="outline" size="sm" @click="deleteEvent(event.id)">
                                                <Trash2 class="h-4 w-4" />
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
    </AppLayout>
</template>
