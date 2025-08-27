<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Megaphone, ArrowLeft, Edit, Trash2, Calendar, Target, Users, DollarSign, MapPin, User, Clock, BarChart3 } from 'lucide-vue-next';

interface CampaignEvent {
    id: number;
    title: string;
    type: string;
    status: string;
    start_datetime: string;
    end_datetime: string;
}

interface Campaign {
    id: number;
    title: string;
    description: string;
    type: string;
    status: string;
    start_date: string;
    end_date: string;
    target_audience: string;
    target_reach: number;
    budget: number;
    location: string;
    creator: string;
    created_at: string;
    updated_at: string;
    is_running: boolean;
    progress_percentage: number;
    duration_days: number;
    events: CampaignEvent[];
}

interface Props {
    campaign: Campaign;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Campaigns',
        href: '/campaigns',
    },
    {
        title: props.campaign.title,
        href: `/campaigns/${props.campaign.id}`,
    },
];

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'active':
            return 'default';
        case 'draft':
            return 'secondary';
        case 'completed':
            return 'outline';
        case 'cancelled':
            return 'destructive';
        default:
            return 'outline';
    }
};

const getEventStatusBadge = (status: string) => {
    switch (status) {
        case 'scheduled':
            return 'default';
        case 'ongoing':
            return 'secondary';
        case 'completed':
            return 'outline';
        case 'cancelled':
            return 'destructive';
        default:
            return 'outline';
    }
};

const editCampaign = () => {
    router.visit(`/campaigns/${props.campaign.id}/edit`);
};

const deleteCampaign = () => {
    if (confirm('Are you sure you want to delete this campaign? This action cannot be undone.')) {
        router.delete(`/campaigns/${props.campaign.id}`);
    }
};

const goBack = () => {
    router.visit('/campaigns');
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount || 0);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
};

const formatDateTime = (dateString: string) => {
    return new Date(dateString).toLocaleString();
};

const getDaysRemaining = () => {
    const endDate = new Date(props.campaign.end_date);
    const today = new Date();
    const diffTime = endDate.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
};

const getProgressColor = (percentage: number) => {
    if (percentage >= 80) return 'bg-green-500';
    if (percentage >= 60) return 'bg-yellow-500';
    if (percentage >= 40) return 'bg-orange-500';
    return 'bg-red-500';
};
</script>

<template>
    <Head :title="`${campaign.title} - Campaign Details`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="outline" size="sm" @click="goBack">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back
                    </Button>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">{{ campaign.title }}</h1>
                        <p class="text-muted-foreground">{{ campaign.type }} Campaign</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Button variant="outline" @click="editCampaign">
                        <Edit class="mr-2 h-4 w-4" />
                        Edit
                    </Button>
                    <Button variant="outline" @click="deleteCampaign">
                        <Trash2 class="mr-2 h-4 w-4" />
                        Delete
                    </Button>
                </div>
            </div>

            <!-- Campaign Overview -->
            <div class="grid gap-6 md:grid-cols-3">
                <!-- Main Campaign Info -->
                <div class="md:col-span-2 space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Megaphone class="h-5 w-5" />
                                Campaign Overview
                            </CardTitle>
                            <CardDescription>
                                Detailed information about this health campaign
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex items-center gap-3">
                                <Badge :variant="getStatusBadge(campaign.status)">
                                    {{ campaign.status.charAt(0).toUpperCase() + campaign.status.slice(1) }}
                                </Badge>
                                <span class="text-sm text-muted-foreground">
                                    Created by {{ campaign.creator }}
                                </span>
                            </div>
                            
                            <div>
                                <h3 class="font-medium mb-2">Description</h3>
                                <p class="text-muted-foreground">{{ campaign.description }}</p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="flex items-center gap-3">
                                    <Target class="h-4 w-4 text-muted-foreground" />
                                    <div>
                                        <p class="text-sm font-medium">Type</p>
                                        <p class="text-sm text-muted-foreground">{{ campaign.type }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <MapPin class="h-4 w-4 text-muted-foreground" />
                                    <div>
                                        <p class="text-sm font-medium">Location</p>
                                        <p class="text-sm text-muted-foreground">{{ campaign.location || 'Not specified' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <Users class="h-4 w-4 text-muted-foreground" />
                                    <div>
                                        <p class="text-sm font-medium">Target Audience</p>
                                        <p class="text-sm text-muted-foreground">{{ campaign.target_audience || 'Not specified' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <DollarSign class="h-4 w-4 text-muted-foreground" />
                                    <div>
                                        <p class="text-sm font-medium">Budget</p>
                                        <p class="text-sm text-muted-foreground">{{ formatCurrency(campaign.budget) }}</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Progress Tracking -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <BarChart3 class="h-5 w-5" />
                                Progress Tracking
                            </CardTitle>
                            <CardDescription>
                                Campaign progress and timeline information
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span>Campaign Progress</span>
                                    <span>{{ campaign.progress_percentage.toFixed(1) }}%</span>
                                </div>
                                <Progress :value="campaign.progress_percentage" class="h-2" />
                                <div class="flex justify-between text-xs text-muted-foreground">
                                    <span>Start: {{ formatDate(campaign.start_date) }}</span>
                                    <span>End: {{ formatDate(campaign.end_date) }}</span>
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-3">
                                <div class="text-center p-4 border rounded-lg">
                                    <div class="text-2xl font-bold">{{ campaign.duration_days }}</div>
                                    <div class="text-sm text-muted-foreground">Total Days</div>
                                </div>
                                <div class="text-center p-4 border rounded-lg">
                                    <div class="text-2xl font-bold" :class="getDaysRemaining() >= 0 ? 'text-green-600' : 'text-red-600'">
                                        {{ getDaysRemaining() >= 0 ? getDaysRemaining() : 'Overdue' }}
                                    </div>
                                    <div class="text-sm text-muted-foreground">
                                        {{ getDaysRemaining() >= 0 ? 'Days Remaining' : 'Days Overdue' }}
                                    </div>
                                </div>
                                <div class="text-center p-4 border rounded-lg">
                                    <div class="text-2xl font-bold">{{ campaign.target_reach?.toLocaleString() || 'N/A' }}</div>
                                    <div class="text-sm text-muted-foreground">Target Reach</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Associated Events -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Calendar class="h-5 w-5" />
                                Associated Events
                            </CardTitle>
                            <CardDescription>
                                Events related to this campaign
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="campaign.events.length === 0" class="text-center py-8">
                                <Calendar class="mx-auto h-12 w-12 text-muted-foreground" />
                                <h3 class="mt-2 text-sm font-medium text-muted-foreground">No events yet</h3>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    No events have been associated with this campaign.
                                </p>
                            </div>
                            <div v-else class="rounded-md border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Event</TableHead>
                                            <TableHead>Type</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead>Date & Time</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-for="event in campaign.events" :key="event.id">
                                            <TableCell>
                                                <div class="font-medium">{{ event.title }}</div>
                                            </TableCell>
                                            <TableCell>{{ event.type }}</TableCell>
                                            <TableCell>
                                                <Badge :variant="getEventStatusBadge(event.status)">
                                                    {{ event.status.charAt(0).toUpperCase() + event.status.slice(1) }}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <div class="text-sm">
                                                    <div>{{ formatDateTime(event.start_datetime) }}</div>
                                                    <div class="text-muted-foreground">to {{ formatDateTime(event.end_datetime) }}</div>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Campaign Stats Sidebar -->
                <div class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Campaign Stats</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex items-center gap-3">
                                <Clock class="h-4 w-4 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium">Created</p>
                                    <p class="text-sm text-muted-foreground">{{ formatDateTime(campaign.created_at) }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <User class="h-4 w-4 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium">Last Updated</p>
                                    <p class="text-sm text-muted-foreground">{{ formatDateTime(campaign.updated_at) }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <Calendar class="h-4 w-4 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-medium">Campaign Status</p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ campaign.is_running ? 'Currently Running' : 'Not Running' }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Quick Actions</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2">
                            <Button variant="outline" class="w-full justify-start" @click="editCampaign">
                                <Edit class="mr-2 h-4 w-4" />
                                Edit Campaign
                            </Button>
                            <Button variant="outline" class="w-full justify-start" disabled>
                                <BarChart3 class="mr-2 h-4 w-4" />
                                View Analytics
                            </Button>
                            <Button variant="outline" class="w-full justify-start" disabled>
                                <Calendar class="mr-2 h-4 w-4" />
                                Add Event
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
