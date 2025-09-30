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
import { Calendar, Edit, Eye, Megaphone, Plus, Search, Target, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

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
}

interface Props {
    campaigns: Campaign[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Campaigns',
        href: '/campaigns',
    },
    {
        title: 'All Campaigns',
        href: '/campaigns/list',
    },
];

// Search and filter state
const searchQuery = ref('');
const statusFilter = ref('all');
const typeFilter = ref('all');

// Computed filtered campaigns
const filteredCampaigns = computed(() => {
    let filtered = props.campaigns;

    // Search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(
            (campaign) =>
                campaign.title.toLowerCase().includes(query) ||
                campaign.description.toLowerCase().includes(query) ||
                campaign.type.toLowerCase().includes(query) ||
                campaign.target_audience?.toLowerCase().includes(query),
        );
    }

    // Status filter
    if (statusFilter.value !== 'all') {
        filtered = filtered.filter((campaign) => campaign.status === statusFilter.value);
    }

    // Type filter
    if (typeFilter.value !== 'all') {
        filtered = filtered.filter((campaign) => campaign.type === typeFilter.value);
    }

    return filtered;
});

// Get unique campaign types for filter
const campaignTypes = computed(() => {
    const types = [...new Set(props.campaigns.map((c) => c.type))];
    return types.sort();
});

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

const viewCampaign = (campaignId: number) => {
    router.visit(`/campaigns/${campaignId}`);
};

const editCampaign = (campaignId: number) => {
    router.visit(`/campaigns/${campaignId}/edit`);
};

const deleteCampaign = (campaignId: number) => {
    if (confirm('Are you sure you want to delete this campaign? This action cannot be undone.')) {
        router.delete(`/campaigns/${campaignId}`);
    }
};

const createNewCampaign = () => {
    router.visit('/campaigns/create');
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
</script>

<template>
    <Head title="All Campaigns" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">All Campaigns</h1>
                    <p class="text-muted-foreground">Manage and monitor all health awareness campaigns</p>
                </div>
                <Button @click="createNewCampaign">
                    <Plus class="mr-2 h-4 w-4" />
                    New Campaign
                </Button>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Search class="h-5 w-5" />
                        Search & Filters
                    </CardTitle>
                    <CardDescription> Find campaigns by title, description, or filter by status and type </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Search</label>
                            <Input v-model="searchQuery" placeholder="Search campaigns..." class="w-full" />
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
                                    <SelectItem value="active">Active</SelectItem>
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
                                    <SelectItem v-for="type in campaignTypes" :key="type" :value="type">
                                        {{ type }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Campaigns Table -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Megaphone class="h-5 w-5" />
                        Campaigns ({{ filteredCampaigns.length }})
                    </CardTitle>
                    <CardDescription> All health awareness campaigns and their current status </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="filteredCampaigns.length === 0" class="py-8 text-center">
                        <Megaphone class="mx-auto h-12 w-12 text-muted-foreground" />
                        <h3 class="mt-2 text-sm font-medium text-muted-foreground">
                            {{ props.campaigns.length === 0 ? 'No campaigns yet' : 'No campaigns match your filters' }}
                        </h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{
                                props.campaigns.length === 0
                                    ? 'Get started by creating your first health campaign.'
                                    : 'Try adjusting your search or filters.'
                            }}
                        </p>
                        <div v-if="props.campaigns.length === 0" class="mt-6">
                            <Button @click="createNewCampaign">
                                <Plus class="mr-2 h-4 w-4" />
                                Create Campaign
                            </Button>
                        </div>
                    </div>
                    <div v-else class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Campaign</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Progress</TableHead>
                                    <TableHead>Duration</TableHead>
                                    <TableHead>Budget</TableHead>
                                    <TableHead>Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="campaign in filteredCampaigns" :key="campaign.id">
                                    <TableCell>
                                        <div>
                                            <div class="font-medium">{{ campaign.title }}</div>
                                            <div class="line-clamp-2 text-sm text-muted-foreground">
                                                {{ campaign.description }}
                                            </div>
                                            <div class="mt-1 text-xs text-muted-foreground">Created by {{ campaign.creator }}</div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <Target class="h-4 w-4 text-muted-foreground" />
                                            {{ campaign.type }}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="getStatusBadge(campaign.status)">
                                            {{ campaign.status.charAt(0).toUpperCase() + campaign.status.slice(1) }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <div class="h-2 w-16 rounded-full bg-secondary">
                                                <div
                                                    class="h-2 rounded-full bg-primary transition-all"
                                                    :style="{ width: `${campaign.progress_percentage}%` }"
                                                ></div>
                                            </div>
                                            <span class="text-sm">{{ campaign.progress_percentage.toFixed(1) }}%</span>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <Calendar class="h-4 w-4 text-muted-foreground" />
                                            <div class="text-sm">
                                                <div>{{ formatDate(campaign.start_date) }}</div>
                                                <div class="text-muted-foreground">to {{ formatDate(campaign.end_date) }}</div>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="text-sm">
                                            {{ formatCurrency(campaign.budget) }}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <Button variant="outline" size="sm" @click="viewCampaign(campaign.id)">
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                            <Button variant="outline" size="sm" @click="editCampaign(campaign.id)">
                                                <Edit class="h-4 w-4" />
                                            </Button>
                                            <Button variant="outline" size="sm" @click="deleteCampaign(campaign.id)">
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
