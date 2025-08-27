<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type User } from '@/types';
import { Head, usePage, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Megaphone, Target, Users, TrendingUp, Calendar, BarChart3, Plus, Eye } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Campaigns',
        href: '/campaigns',
    },
];

const page = usePage();
const user = page.props.auth.user as User;

// Get campaigns from props
const campaigns = page.props.campaigns || [];

// Calculate stats from real data
const activeCampaigns = campaigns.filter(c => c.status === 'active');
const totalReach = campaigns.reduce((sum, c) => sum + (c.target_reach || 0), 0);
const avgEngagement = campaigns.length > 0 ? 
    campaigns.reduce((sum, c) => sum + (c.progress_percentage || 0), 0) / campaigns.length : 0;

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

const createNewCampaign = () => {
    router.visit('/campaigns/create');
};
</script>

<template>
    <Head title="Campaigns Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Health Campaigns</h1>
                    <p class="text-muted-foreground">Manage and monitor health awareness campaigns</p>
                </div>
                <Button @click="createNewCampaign">
                    <Plus class="mr-2 h-4 w-4" />
                    New Campaign
                </Button>
            </div>

            <!-- Campaign Stats -->
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Active Campaigns</CardTitle>
                        <Megaphone class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ activeCampaigns.length }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ campaigns.length }} total campaigns
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Reach</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ totalReach.toLocaleString() }}</div>
                        <p class="text-xs text-muted-foreground">
                            Target audience across all campaigns
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Avg. Progress</CardTitle>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ avgEngagement.toFixed(1) }}%</div>
                        <p class="text-xs text-muted-foreground">
                            Average campaign progress
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Campaign Types</CardTitle>
                        <Target class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ new Set(campaigns.map(c => c.type)).size }}</div>
                        <p class="text-xs text-muted-foreground">
                            Different campaign types
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Active Campaigns List -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Megaphone class="h-5 w-5" />
                        Campaign Overview
                    </CardTitle>
                    <CardDescription>
                        Monitor all health awareness campaigns and their performance
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="campaigns.length === 0" class="text-center py-8">
                        <Megaphone class="mx-auto h-12 w-12 text-muted-foreground" />
                        <h3 class="mt-2 text-sm font-medium text-muted-foreground">No campaigns yet</h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Get started by creating your first health campaign.
                        </p>
                        <div class="mt-6">
                            <Button @click="createNewCampaign">
                                <Plus class="mr-2 h-4 w-4" />
                                Create Campaign
                            </Button>
                        </div>
                    </div>
                    <div v-else class="space-y-4">
                        <div 
                            v-for="campaign in campaigns.slice(0, 5)" 
                            :key="campaign.id"
                            class="flex items-center justify-between p-4 border rounded-lg hover:bg-muted/50"
                        >
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="font-medium">{{ campaign.title }}</h3>
                                    <Badge :variant="getStatusBadge(campaign.status)">
                                        {{ campaign.status.charAt(0).toUpperCase() + campaign.status.slice(1) }}
                                    </Badge>
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm text-muted-foreground">
                                    <div>
                                        <span class="font-medium">Type:</span> {{ campaign.type }}
                                    </div>
                                    <div>
                                        <span class="font-medium">Progress:</span> {{ campaign.progress_percentage?.toFixed(1) }}%
                                    </div>
                                    <div>
                                        <span class="font-medium">Start:</span> {{ campaign.start_date }}
                                    </div>
                                    <div>
                                        <span class="font-medium">End:</span> {{ campaign.end_date }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <Button variant="outline" size="sm" @click="viewCampaign(campaign.id)">
                                    <Eye class="mr-2 h-4 w-4" />
                                    View
                                </Button>
                            </div>
                        </div>
                        <div v-if="campaigns.length > 5" class="text-center pt-4">
                            <Button variant="outline" @click="router.visit('/campaigns/list')">
                                View All Campaigns
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Quick Actions -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Calendar class="h-5 w-5" />
                        Campaign Management
                    </CardTitle>
                    <CardDescription>
                        Essential tools for creating and managing health campaigns
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <Button variant="outline" class="justify-start h-auto py-4" @click="createNewCampaign">
                            <div class="flex flex-col items-start gap-1">
                                <div class="flex items-center gap-2">
                                    <Plus class="h-4 w-4" />
                                    <span class="font-medium">Create Campaign</span>
                                </div>
                                <span class="text-xs text-muted-foreground">Launch new health initiative</span>
                            </div>
                        </Button>
                        <Button variant="outline" class="justify-start h-auto py-4" @click="router.visit('/campaigns/list')">
                            <div class="flex flex-col items-start gap-1">
                                <div class="flex items-center gap-2">
                                    <Target class="h-4 w-4" />
                                    <span class="font-medium">View All Campaigns</span>
                                </div>
                                <span class="text-xs text-muted-foreground">Manage existing campaigns</span>
                            </div>
                        </Button>
                        <Button variant="outline" class="justify-start h-auto py-4" disabled>
                            <div class="flex flex-col items-start gap-1">
                                <div class="flex items-center gap-2">
                                    <BarChart3 class="h-4 w-4" />
                                    <span class="font-medium">Analytics</span>
                                </div>
                                <span class="text-xs text-muted-foreground">Track performance</span>
                            </div>
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Campaign Manager Info -->
            <div class="mt-auto">
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center gap-4">
                            <div class="flex-1">
                                <p class="text-sm text-muted-foreground">
                                    Campaign Manager: <span class="font-medium text-foreground">{{ user.name }}</span>
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
