<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, Save } from 'lucide-vue-next';

interface Campaign {
    id: number;
    title: string;
}

interface EventData {
    id: number;
    title: string;
    description: string;
    type: string;
    status: 'draft' | 'published' | 'ongoing' | 'completed' | 'cancelled';
    start_datetime: string; // formatted 'Y-m-dTH:i'
    end_datetime: string; // formatted 'Y-m-dTH:i'
    location: string | null;
    online_meeting_url: string | null;
    capacity: number | null;
    is_online: boolean;
    requires_registration: boolean;
    campaign_id: number | 'none' | null;
}

interface Props {
    event: EventData;
    campaigns: Campaign[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Events',
        href: '/events',
    },
    {
        title: `Edit: ${props.event.title}`,
        href: `/events/${props.event.id}/edit`,
    },
];

const form = useForm({
    title: props.event.title || '',
    description: props.event.description || '',
    type: props.event.type || '',
    status: props.event.status || 'draft',
    start_datetime: props.event.start_datetime || '',
    end_datetime: props.event.end_datetime || '',
    location: props.event.location || '',
    online_meeting_url: props.event.online_meeting_url || '',
    capacity: props.event.capacity ? String(props.event.capacity) : '',
    is_online: !!props.event.is_online,
    requires_registration: !!props.event.requires_registration,
    campaign_id: props.event.campaign_id === null || props.event.campaign_id === 'none' ? 'none' : String(props.event.campaign_id),
});

const eventTypes = ['webinar', 'health_event', 'check_up_drive', 'workshop', 'seminar'];

const statusOptions = [
    { value: 'draft', label: 'Draft' },
    { value: 'published', label: 'Published' },
    { value: 'ongoing', label: 'Ongoing' },
    { value: 'completed', label: 'Completed' },
    { value: 'cancelled', label: 'Cancelled' },
];

const submit = () => {
    // Convert "none" to null for campaign_id
    if (form.campaign_id === 'none') {
        form.campaign_id = null as unknown as string | null;
    }

    form.patch(`/events/${props.event.id}`, {
        onSuccess: () => {
            // Redirect handled server-side if any
        },
        onError: (errors: any) => {
            console.log('Form errors:', errors);
        },
    });
};

const goBack = () => {
    router.visit('/events');
};

const formatNumber = (value: string) => {
    const numericValue = value.replace(/[^0-9]/g, '');
    return numericValue;
};

const handleCapacityInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    form.capacity = formatNumber(target.value);
};
</script>

<template>
    <Head title="Edit Event" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="outline" size="sm" @click="goBack">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back
                    </Button>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">Edit Event</h1>
                        <p class="text-muted-foreground">Update the details for this event</p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Basic Information -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Calendar class="h-5 w-5" />
                            Basic Information
                        </CardTitle>
                        <CardDescription> Update the essential details for your health event </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="title">Event Title *</Label>
                                <Input
                                    id="title"
                                    v-model="form.title"
                                    placeholder="Enter event title"
                                    :class="{ 'border-red-500': form.errors.title }"
                                />
                                <p v-if="form.errors.title" class="text-sm text-red-500">
                                    {{ form.errors.title }}
                                </p>
                            </div>
                            <div class="space-y-2">
                                <Label for="type">Event Type *</Label>
                                <Select v-model="form.type">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.type }">
                                        <SelectValue placeholder="Select event type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="type in eventTypes" :key="type" :value="type">
                                            {{ type.charAt(0).toUpperCase() + type.slice(1).replace('_', ' ') }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.type" class="text-sm text-red-500">
                                    {{ form.errors.type }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="description">Description *</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                placeholder="Describe your event, agenda, and what participants can expect..."
                                rows="4"
                                :class="{ 'border-red-500': form.errors.description }"
                            />
                            <p v-if="form.errors.description" class="text-sm text-red-500">
                                {{ form.errors.description }}
                            </p>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="status">Status *</Label>
                                <Select v-model="form.status">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.status }">
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="status in statusOptions" :key="status.value" :value="status.value">
                                            {{ status.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.status" class="text-sm text-red-500">
                                    {{ form.errors.status }}
                                </p>
                            </div>
                            <div class="space-y-2">
                                <Label for="campaign_id">Associated Campaign</Label>
                                <Select v-model="form.campaign_id">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.campaign_id }">
                                        <SelectValue placeholder="Select campaign (optional)" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">No Campaign</SelectItem>
                                        <SelectItem v-for="campaign in campaigns" :key="campaign.id" :value="campaign.id.toString()">
                                            {{ campaign.title }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.campaign_id" class="text-sm text-red-500">
                                    {{ form.errors.campaign_id }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Event Schedule -->
                <Card>
                    <CardHeader>
                        <CardTitle>Event Schedule</CardTitle>
                        <CardDescription> Set the date and time for your event </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="start_datetime">Start Date & Time *</Label>
                                <Input
                                    id="start_datetime"
                                    v-model="form.start_datetime"
                                    type="datetime-local"
                                    :class="{ 'border-red-500': form.errors.start_datetime }"
                                />
                                <p v-if="form.errors.start_datetime" class="text-sm text-red-500">
                                    {{ form.errors.start_datetime }}
                                </p>
                            </div>
                            <div class="space-y-2">
                                <Label for="end_datetime">End Date & Time *</Label>
                                <Input
                                    id="end_datetime"
                                    v-model="form.end_datetime"
                                    type="datetime-local"
                                    :class="{ 'border-red-500': form.errors.end_datetime }"
                                />
                                <p v-if="form.errors.end_datetime" class="text-sm text-red-500">
                                    {{ form.errors.end_datetime }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Event Location & Capacity -->
                <Card>
                    <CardHeader>
                        <CardTitle>Location & Capacity</CardTitle>
                        <CardDescription> Define where the event will take place and capacity limits </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center space-x-2">
                            <input
                                id="is_online"
                                type="checkbox"
                                v-model="form.is_online"
                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <Label for="is_online">This is an online event</Label>
                        </div>

                        <div v-if="!form.is_online" class="space-y-2">
                            <Label for="location">Physical Location</Label>
                            <Input
                                id="location"
                                v-model="form.location"
                                placeholder="e.g., Community Center, 123 Main St"
                                :class="{ 'border-red-500': form.errors.location }"
                            />
                            <p v-if="form.errors.location" class="text-sm text-red-500">
                                {{ form.errors.location }}
                            </p>
                        </div>

                        <div v-if="form.is_online" class="space-y-2">
                            <Label for="online_meeting_url">Online Meeting URL</Label>
                            <Input
                                id="online_meeting_url"
                                v-model="form.online_meeting_url"
                                placeholder="https://meet.google.com/..."
                                :class="{ 'border-red-500': form.errors.online_meeting_url }"
                            />
                            <p v-if="form.errors.online_meeting_url" class="text-sm text-red-500">
                                {{ form.errors.online_meeting_url }}
                            </p>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="capacity">Capacity</Label>
                                <Input
                                    id="capacity"
                                    :value="form.capacity"
                                    @input="handleCapacityInput"
                                    placeholder="Leave empty for unlimited"
                                    type="number"
                                    :class="{ 'border-red-500': form.errors.capacity }"
                                />
                                <p v-if="form.errors.capacity" class="text-sm text-red-500">
                                    {{ form.errors.capacity }}
                                </p>
                            </div>
                            <div class="flex items-center space-x-2 pt-8">
                                <Checkbox id="requires_registration" v-model:checked="form.requires_registration" />
                                <Label for="requires_registration">Requires registration</Label>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Form Actions -->
                <div class="flex justify-end gap-4">
                    <Button type="button" variant="outline" @click="goBack"> Cancel </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Save v-if="!form.processing" class="mr-2 h-4 w-4" />
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
