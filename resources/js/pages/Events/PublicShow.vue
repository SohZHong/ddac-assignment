<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Building2, Calendar, Clock, MapPin, Play, Radio, Square, User, Users, Video } from 'lucide-vue-next';
import { computed } from 'vue';

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
    registration_count: number;
    remaining_capacity: number | null;
    is_at_capacity: boolean;
    duration_minutes: number;
    can_register: boolean;
    user_registration: { id: number; status: string } | null;
    user_attendance: { id: number; status: string; check_in_time: string | null } | null;
    can_check_in: boolean;
    livestream_room?: {
        id: number;
        room_name: string;
        status: 'scheduled' | 'live' | 'ended' | 'cancelled';
        started_at?: string;
        ended_at?: string;
        max_participants: number;
        current_participants: number;
    };
    is_event_creator: boolean;
}

interface Props {
    event: EventData;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Public Events',
        href: '/public/events',
    },
    {
        title: props.event.title,
        href: `/public/events/${props.event.id}`,
    },
];

const formatDateTime = (dateString: string) => new Date(dateString).toLocaleString();
const formatTime = (dateString: string) => new Date(dateString).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
const formatDate = (dateString: string) => new Date(dateString).toLocaleDateString();

const formatDuration = (minutes: number) => {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    if (hours > 0) {
        return `${hours}h ${mins}m`;
    }
    return `${mins}m`;
};

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

const getTypeBadge = (type: string) => {
    switch (type) {
        case 'webinar':
            return 'secondary';
        case 'workshop':
            return 'outline';
        case 'seminar':
            return 'default';
        case 'conference':
            return 'destructive';
        default:
            return 'outline';
    }
};

const isEventUpcoming = computed(() => {
    const now = new Date();
    const eventStart = new Date(props.event.start_datetime);
    return eventStart > now;
});

const isEventOngoing = computed(() => {
    const now = new Date();
    const eventStart = new Date(props.event.start_datetime);
    const eventEnd = new Date(props.event.end_datetime);
    return now >= eventStart && now <= eventEnd;
});

const isEventPast = computed(() => {
    const now = new Date();
    const eventEnd = new Date(props.event.end_datetime);
    return eventEnd < now;
});

// Livestream computed properties
const hasLivestream = computed(() => !!props.event.livestream_room);
const livestreamStatus = computed(() => props.event.livestream_room?.status || 'none');
const canJoinLivestream = computed(() => {
    if (!hasLivestream.value) return false;
    const status = livestreamStatus.value;
    return status === 'scheduled' || status === 'live';
});
const isLivestreamLive = computed(() => livestreamStatus.value === 'live');
const isLivestreamScheduled = computed(() => livestreamStatus.value === 'scheduled');

// Registration and livestream functions
const register = () => {
    router.post(`/events/${props.event.id}/registrations`);
};

const unregister = () => {
    router.delete(`/events/${props.event.id}/registrations`);
};

const goToLivestream = () => {
    router.visit(`/public/events/${props.event.id}/livestream`);
};

const startLivestream = async () => {
    try {
        const response = await fetch(`/api/livekit/rooms/${props.event.livestream_room?.id}/start`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (response.ok) {
            // Refresh the page to get updated livestream status
            window.location.reload();
        } else {
            throw new Error('Failed to start livestream');
        }
    } catch (error) {
        console.error('Failed to start livestream:', error);
    }
};

const endLivestream = async () => {
    try {
        const response = await fetch(`/api/livekit/rooms/${props.event.livestream_room?.id}/end`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (response.ok) {
            // Refresh the page to get updated livestream status
            window.location.reload();
        } else {
            throw new Error('Failed to end livestream');
        }
    } catch (error) {
        console.error('Failed to end livestream:', error);
    }
};

const goBack = () => router.visit('/public/events');
</script>

<template>
    <AppLayout>
        <Head :title="event.title" />

        <div class="container mx-auto space-y-6 py-6">
            <!-- Header -->
            <div class="space-y-4">
                <div class="flex items-center gap-4">
                    <Badge :variant="getStatusBadge(event.status)" class="text-sm">
                        {{ event.status }}
                    </Badge>
                    <Badge :variant="getTypeBadge(event.type)" class="text-sm">
                        {{ event.type }}
                    </Badge>
                </div>
                <h1 class="text-4xl font-bold tracking-tight">{{ event.title }}</h1>
                <p class="max-w-3xl text-xl text-muted-foreground">{{ event.description }}</p>
            </div>

            <!-- Event Details Grid -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Main Content -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Event Information -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Event Details</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="flex items-center gap-3">
                                    <Calendar class="h-5 w-5 text-muted-foreground" />
                                    <div>
                                        <div class="font-medium">Date</div>
                                        <div class="text-sm text-muted-foreground">
                                            {{ formatDate(event.start_datetime) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <Clock class="h-5 w-5 text-muted-foreground" />
                                    <div>
                                        <div class="font-medium">Time</div>
                                        <div class="text-sm text-muted-foreground">
                                            {{ formatTime(event.start_datetime) }} - {{ formatTime(event.end_datetime) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <Clock class="h-5 w-5 text-muted-foreground" />
                                    <div>
                                        <div class="font-medium">Duration</div>
                                        <div class="text-sm text-muted-foreground">
                                            {{ formatDuration(event.duration_minutes) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <Users class="h-5 w-5 text-muted-foreground" />
                                    <div>
                                        <div class="font-medium">Capacity</div>
                                        <div class="text-sm text-muted-foreground">
                                            <span v-if="event.capacity">
                                                {{ event.registration_count }}/{{ event.capacity }}
                                                <span v-if="event.remaining_capacity !== null"> ({{ event.remaining_capacity }} available) </span>
                                            </span>
                                            <span v-else>Unlimited</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <Separator />

                            <div class="space-y-3">
                                <div class="flex items-center gap-3">
                                    <MapPin class="h-5 w-5 text-muted-foreground" />
                                    <div>
                                        <div class="font-medium">Location</div>
                                        <div class="text-sm text-muted-foreground">
                                            <span v-if="event.is_online">Online Event</span>
                                            <span v-else-if="event.location">{{ event.location }}</span>
                                            <span v-else>Location to be determined</span>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="event.online_meeting_url" class="flex items-center gap-3">
                                    <Video class="h-5 w-5 text-muted-foreground" />
                                    <div>
                                        <div class="font-medium">Meeting URL</div>
                                        <div class="text-sm">
                                            <a
                                                :href="event.online_meeting_url"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="text-primary hover:underline"
                                            >
                                                Join Meeting
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Event Status -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Event Status</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div v-if="isEventUpcoming" class="flex items-center gap-3 text-green-600">
                                    <div class="h-3 w-3 rounded-full bg-green-600"></div>
                                    <span class="font-medium">Upcoming Event</span>
                                </div>
                                <div v-else-if="isEventOngoing" class="flex items-center gap-3 text-blue-600">
                                    <div class="h-3 w-3 animate-pulse rounded-full bg-blue-600"></div>
                                    <span class="font-medium">Event in Progress</span>
                                </div>
                                <div v-else-if="isEventPast" class="flex items-center gap-3 text-gray-600">
                                    <div class="h-3 w-3 rounded-full bg-gray-600"></div>
                                    <span class="font-medium">Event Completed</span>
                                </div>

                                <div class="text-sm text-muted-foreground">
                                    <div v-if="event.requires_registration">
                                        <span v-if="event.is_at_capacity" class="font-medium text-red-600"> Event is at full capacity </span>
                                        <span v-else-if="event.can_register" class="font-medium text-green-600"> Registration is open </span>
                                        <span v-else class="font-medium text-yellow-600"> Registration may be limited </span>
                                    </div>
                                    <div v-else>No registration required for this event</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Livestream Information -->
                    <Card v-if="event.livestream_room">
                        <CardHeader>
                            <CardTitle>Livestream</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <Badge
                                            :variant="isLivestreamLive ? 'default' : isLivestreamScheduled ? 'outline' : 'secondary'"
                                            class="flex items-center gap-2"
                                        >
                                            <Radio v-if="isLivestreamLive" class="h-3 w-3" />
                                            {{ isLivestreamLive ? 'LIVE' : isLivestreamScheduled ? 'Scheduled' : 'Ended' }}
                                        </Badge>
                                        <span class="text-sm text-muted-foreground">
                                            {{
                                                livestreamStatus === 'live'
                                                    ? 'Currently streaming'
                                                    : livestreamStatus === 'scheduled'
                                                      ? 'Stream will start soon'
                                                      : 'Stream has ended'
                                            }}
                                        </span>
                                    </div>

                                    <div class="flex gap-2">
                                        <!-- Join Livestream Button -->
                                        <Button
                                            v-if="canJoinLivestream"
                                            @click="goToLivestream"
                                            class="flex items-center gap-2"
                                        >
                                            <Play class="h-4 w-4" />
                                            {{ isLivestreamLive ? 'Join Stream' : 'Join Scheduled Stream' }}
                                        </Button>

                                        <!-- Livestream Management Buttons (for event creators) -->
                                        <Button
                                            v-if="event.is_event_creator && isLivestreamScheduled"
                                            @click="startLivestream"
                                            variant="default"
                                            class="flex items-center gap-2"
                                        >
                                            <Play class="h-4 w-4" />
                                            Start Stream
                                        </Button>

                                        <Button
                                            v-if="event.is_event_creator && isLivestreamLive"
                                            @click="endLivestream"
                                            variant="destructive"
                                            class="flex items-center gap-2"
                                        >
                                            <Square class="h-4 w-4" />
                                            End Stream
                                        </Button>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div class="flex items-center gap-3">
                                        <Video class="h-5 w-5 text-muted-foreground" />
                                        <div>
                                            <div class="font-medium">Room: {{ event.livestream_room.room_name }}</div>
                                            <div class="text-sm text-muted-foreground">Status: {{ event.livestream_room.status }}</div>
                                        </div>
                                    </div>
                                    <div class="text-sm text-muted-foreground">
                                        <div v-if="event.livestream_room.started_at">Started: {{ formatDateTime(event.livestream_room.started_at) }}</div>
                                        <div v-if="event.livestream_room.ended_at">Ended: {{ formatDateTime(event.livestream_room.ended_at) }}</div>
                                        <div>
                                            Participants: {{ event.livestream_room.current_participants }}/{{ event.livestream_room.max_participants }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Quick Actions -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Quick Actions</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <!-- Registration Button -->
                            <div v-if="event.requires_registration">
                                <Button v-if="!event.user_registration && event.can_register" class="w-full justify-start" @click="register">
                                    Register for Event
                                </Button>
                                <Button v-else-if="event.user_registration" variant="outline" class="w-full justify-start" @click="unregister">
                                    Unregister
                                    <span v-if="event.user_registration.status === 'waitlisted'" class="ml-2 text-xs"> (Waitlisted) </span>
                                </Button>
                                <Button v-else variant="outline" class="w-full justify-start" disabled> Registration Closed or Full </Button>
                            </div>
                            <div v-else class="py-2 text-center text-sm text-muted-foreground">No registration required</div>

                            <Separator />

                            <!-- Livestream Button -->
                            <div v-if="hasLivestream && canJoinLivestream">
                                <Button @click="goToLivestream" class="w-full justify-start" :variant="isLivestreamLive ? 'default' : 'outline'">
                                    <Play class="mr-2 h-4 w-4" />
                                    {{ isLivestreamLive ? 'Join Live Stream' : 'Join Scheduled Stream' }}
                                </Button>
                            </div>
                            <div v-else-if="hasLivestream" class="py-2 text-center text-sm text-muted-foreground">Livestream not available</div>
                            <div v-else class="py-2 text-center text-sm text-muted-foreground">No livestream for this event</div>
                        </CardContent>
                    </Card>

                    <!-- Event Creator -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Event Creator</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-center gap-3">
                                <User class="h-5 w-5 text-muted-foreground" />
                                <div>
                                    <div class="font-medium">{{ event.creator }}</div>
                                    <div class="text-sm text-muted-foreground">Event Organizer</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Campaign Information -->
                    <Card v-if="event.campaign">
                        <CardHeader>
                            <CardTitle>Campaign</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-center gap-3">
                                <Building2 class="h-5 w-5 text-muted-foreground" />
                                <div>
                                    <div class="font-medium">{{ event.campaign.title }}</div>
                                    <div class="text-sm text-muted-foreground">Related Campaign</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Registration Status -->
                    <Card v-if="event.requires_registration">
                        <CardHeader>
                            <CardTitle>Registration</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div class="text-center">
                                    <div class="text-2xl font-bold">{{ event.registration_count }}</div>
                                    <div class="text-sm text-muted-foreground">Registered</div>
                                </div>
                                <div v-if="event.capacity" class="text-center">
                                    <div class="text-2xl font-bold">{{ event.remaining_capacity }}</div>
                                    <div class="text-sm text-muted-foreground">Available Spots</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-sm text-muted-foreground">
                                        <span v-if="event.is_at_capacity" class="font-medium text-red-600"> Event Full </span>
                                        <span v-else-if="event.can_register" class="font-medium text-green-600"> Registration Open </span>
                                        <span v-else class="font-medium text-yellow-600"> Limited Availability </span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
