<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type User } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
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

// Sample campaign data (would come from props in real implementation)
const activeCampaigns = [
    {
        id: 1,
        title: 'Diabetes Prevention Awareness',
        status: 'active',
        reach: 2847,
        engagement: '12.4%',
        startDate: '2024-01-15',
        endDate: '2024-03-15'
    },
    {
        id: 2,
        title: 'Mental Health Support Initiative',
        status: 'active',
        reach: 1923,
        engagement: '18.7%',
        startDate: '2024-02-01',
        endDate: '2024-04-01'
    },
    {
        id: 3,
        title: 'Vaccination Drive 2024',
        status: 'draft',
        reach: 0,
        engagement: '0%',
        startDate: '2024-03-01',
        endDate: '2024-05-01'
    }
];

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'active':
            return 'default';
        case 'draft':
            return 'secondary';
        case 'completed':
            return 'outline';
        default:
            return 'outline';
    }
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
                <Button disabled>
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
                        <div class="text-2xl font-bold">2</div>
                        <p class="text-xs text-muted-foreground">
                            +1 from last month
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Reach</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">4,770</div>
                        <p class="text-xs text-muted-foreground">
                            +23% from last month
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Avg. Engagement</CardTitle>
                        <Target class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">15.6%</div>
                        <p class="text-xs text-muted-foreground">
                            +2.1% from last month
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Campaign Performance</CardTitle>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">92%</div>
                        <p class="text-xs text-muted-foreground">
                            Success rate
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
                    <div class="space-y-4">
                        <div 
                            v-for="campaign in activeCampaigns" 
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
                                        <span class="font-medium">Reach:</span> {{ campaign.reach.toLocaleString() }}
                                    </div>
                                    <div>
                                        <span class="font-medium">Engagement:</span> {{ campaign.engagement }}
                                    </div>
                                    <div>
                                        <span class="font-medium">Start:</span> {{ campaign.startDate }}
                                    </div>
                                    <div>
                                        <span class="font-medium">End:</span> {{ campaign.endDate }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <Button variant="outline" size="sm" disabled>
                                    <Eye class="mr-2 h-4 w-4" />
                                    View
                                </Button>
                                <Button variant="outline" size="sm" disabled>
                                    <BarChart3 class="mr-2 h-4 w-4" />
                                    Analytics
                                </Button>
                            </div>
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
                        <Button variant="outline" class="justify-start h-auto py-4" disabled>
                            <div class="flex flex-col items-start gap-1">
                                <div class="flex items-center gap-2">
                                    <Plus class="h-4 w-4" />
                                    <span class="font-medium">Create Campaign</span>
                                </div>
                                <span class="text-xs text-muted-foreground">Launch new health initiative</span>
                            </div>
                        </Button>
                        <Button variant="outline" class="justify-start h-auto py-4" disabled>
                            <div class="flex flex-col items-start gap-1">
                                <div class="flex items-center gap-2">
                                    <Target class="h-4 w-4" />
                                    <span class="font-medium">Target Audience</span>
                                </div>
                                <span class="text-xs text-muted-foreground">Define campaign reach</span>
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
