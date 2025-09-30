<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Calendar, Clock, Eye, MapPin, Search, Video } from 'lucide-vue-next';
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
    registration_count: number;
    remaining_capacity: number | null;
    is_at_capacity: boolean;
    duration_minutes: number;
}

interface Props {
    events: Event[];
}

const props = defineProps<Props>();

// Search and filter state
const searchQuery = ref('');
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
    router.visit(`/public/events/${eventId}`);
};

const formatDateTime = (dateString: string) => {
    return new Date(dateString).toLocaleString();
};

const formatDuration = (minutes: number) => {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    if (hours > 0) {
        return `${hours}h ${mins}m`;
    }
    return `${mins}m`;
};
</script>

<template>
    <AppLayout>
        <Head title="Public Events" />

        <div class="container mx-auto space-y-6 py-6">
            <!-- Header -->
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">Public Events</h1>
                        <p class="text-muted-foreground">Browse and discover upcoming health events and webinars</p>
                    </div>
                </div>
            </div>

            <!-- Search and Filters -->
            <Card>
                <CardHeader>
                    <CardTitle>Search & Filters</CardTitle>
                    <CardDescription> Find events that match your interests and schedule </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Search</label>
                            <div class="relative">
                                <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input v-model="searchQuery" placeholder="Search events..." class="pl-10" />
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Event Type</label>
                            <Select v-model="typeFilter">
                                <SelectTrigger>
                                    <SelectValue placeholder="All types" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All types</SelectItem>
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
                                    <SelectItem value="all">All campaigns</SelectItem>
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
                    <CardTitle>Available Events</CardTitle>
                    <CardDescription> {{ filteredEvents.length }} event{{ filteredEvents.length !== 1 ? 's' : '' }} found </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Event</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Date & Time</TableHead>
                                    <TableHead>Location</TableHead>
                                    <TableHead>Capacity</TableHead>
                                    <TableHead>Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="event in filteredEvents" :key="event.id" class="hover:bg-muted/50">
                                    <TableCell>
                                        <div class="space-y-1">
                                            <div class="font-medium">{{ event.title }}</div>
                                            <div class="line-clamp-2 text-sm text-muted-foreground">
                                                {{ event.description }}
                                            </div>
                                            <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                                <span>by {{ event.creator }}</span>
                                                <span v-if="event.campaign">• {{ event.campaign.title }}</span>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="getStatusBadge(event.status)">
                                            {{ event.status }}
                                        </Badge>
                                        <div class="mt-1 text-sm text-muted-foreground">
                                            {{ event.type }}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-2">
                                                <Calendar class="h-4 w-4 text-muted-foreground" />
                                                <span class="text-sm">
                                                    {{ formatDateTime(event.start_datetime) }}
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <Clock class="h-4 w-4 text-muted-foreground" />
                                                <span class="text-sm text-muted-foreground">
                                                    {{ formatDuration(event.duration_minutes) }}
                                                </span>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="space-y-1">
                                            <div v-if="event.is_online" class="flex items-center gap-2">
                                                <Video class="h-4 w-4 text-muted-foreground" />
                                                <span class="text-sm">Online Event</span>
                                            </div>
                                            <div v-else-if="event.location" class="flex items-center gap-2">
                                                <MapPin class="h-4 w-4 text-muted-foreground" />
                                                <span class="text-sm">{{ event.location }}</span>
                                            </div>
                                            <div v-else class="text-sm text-muted-foreground">Location TBD</div>
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
                                                View Details
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
