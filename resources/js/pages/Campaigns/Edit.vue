<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Megaphone, ArrowLeft, Save } from 'lucide-vue-next';

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
    {
        title: 'Edit',
        href: `/campaigns/${props.campaign.id}/edit`,
    },
];

const form = useForm({
    title: props.campaign.title,
    description: props.campaign.description,
    type: props.campaign.type,
    status: props.campaign.status,
    start_date: props.campaign.start_date,
    end_date: props.campaign.end_date,
    target_audience: props.campaign.target_audience || '',
    target_reach: props.campaign.target_reach?.toString() || '',
    budget: props.campaign.budget?.toString() || '',
    location: props.campaign.location || '',
    metadata: {},
});

const campaignTypes = [
    'Health Awareness',
    'Disease Prevention',
    'Mental Health',
    'Vaccination',
    'Nutrition',
    'Exercise',
    'Substance Abuse Prevention',
    'Sexual Health',
    'Environmental Health',
    'Other'
];

const statusOptions = [
    { value: 'draft', label: 'Draft' },
    { value: 'active', label: 'Active' },
    { value: 'completed', label: 'Completed' },
    { value: 'cancelled', label: 'Cancelled' }
];

const submit = () => {
    form.put(`/campaigns/${props.campaign.id}`, {
        onSuccess: () => {
            // Form will redirect on success
        },
    });
};

const goBack = () => {
    router.visit(`/campaigns/${props.campaign.id}`);
};

const formatCurrency = (value: string) => {
    // Remove non-numeric characters except decimal point
    const numericValue = value.replace(/[^0-9.]/g, '');
    return numericValue;
};

const handleBudgetInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    form.budget = formatCurrency(target.value);
};
</script>

<template>
    <Head title="Edit Campaign" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="outline" size="sm" @click="goBack">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back
                    </Button>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">Edit Campaign</h1>
                        <p class="text-muted-foreground">Update campaign information</p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Basic Information -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Megaphone class="h-5 w-5" />
                            Basic Information
                        </CardTitle>
                        <CardDescription>
                            Update the essential details for your health campaign
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="title">Campaign Title *</Label>
                                <Input
                                    id="title"
                                    v-model="form.title"
                                    placeholder="Enter campaign title"
                                    :class="{ 'border-red-500': form.errors.title }"
                                />
                                <p v-if="form.errors.title" class="text-sm text-red-500">
                                    {{ form.errors.title }}
                                </p>
                            </div>
                            <div class="space-y-2">
                                <Label for="type">Campaign Type *</Label>
                                <Select v-model="form.type">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.type }">
                                        <SelectValue placeholder="Select campaign type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="type in campaignTypes" :key="type" :value="type">
                                            {{ type }}
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
                                placeholder="Describe your campaign goals, objectives, and key messages..."
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
                                <Label for="location">Location</Label>
                                <Input
                                    id="location"
                                    v-model="form.location"
                                    placeholder="e.g., City, State, or Online"
                                    :class="{ 'border-red-500': form.errors.location }"
                                />
                                <p v-if="form.errors.location" class="text-sm text-red-500">
                                    {{ form.errors.location }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Campaign Timeline -->
                <Card>
                    <CardHeader>
                        <CardTitle>Campaign Timeline</CardTitle>
                        <CardDescription>
                            Update the start and end dates for your campaign
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="start_date">Start Date *</Label>
                                <Input
                                    id="start_date"
                                    v-model="form.start_date"
                                    type="date"
                                    :class="{ 'border-red-500': form.errors.start_date }"
                                />
                                <p v-if="form.errors.start_date" class="text-sm text-red-500">
                                    {{ form.errors.start_date }}
                                </p>
                            </div>
                            <div class="space-y-2">
                                <Label for="end_date">End Date *</Label>
                                <Input
                                    id="end_date"
                                    v-model="form.end_date"
                                    type="date"
                                    :class="{ 'border-red-500': form.errors.end_date }"
                                />
                                <p v-if="form.errors.end_date" class="text-sm text-red-500">
                                    {{ form.errors.end_date }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Target Audience & Budget -->
                <Card>
                    <CardHeader>
                        <CardTitle>Target Audience & Budget</CardTitle>
                        <CardDescription>
                            Update your target audience and campaign budget
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="target_audience">Target Audience</Label>
                                <Input
                                    id="target_audience"
                                    v-model="form.target_audience"
                                    placeholder="e.g., Young adults, Seniors, Parents"
                                    :class="{ 'border-red-500': form.errors.target_audience }"
                                />
                                <p v-if="form.errors.target_audience" class="text-sm text-red-500">
                                    {{ form.errors.target_audience }}
                                </p>
                            </div>
                            <div class="space-y-2">
                                <Label for="target_reach">Target Reach</Label>
                                <Input
                                    id="target_reach"
                                    v-model="form.target_reach"
                                    type="number"
                                    placeholder="Number of people to reach"
                                    :class="{ 'border-red-500': form.errors.target_reach }"
                                />
                                <p v-if="form.errors.target_reach" class="text-sm text-red-500">
                                    {{ form.errors.target_reach }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="budget">Budget (USD)</Label>
                            <Input
                                id="budget"
                                :value="form.budget"
                                @input="handleBudgetInput"
                                placeholder="0.00"
                                :class="{ 'border-red-500': form.errors.budget }"
                            />
                            <p v-if="form.errors.budget" class="text-sm text-red-500">
                                {{ form.errors.budget }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Form Actions -->
                <div class="flex justify-end gap-4">
                    <Button type="button" variant="outline" @click="goBack">
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Save v-if="!form.processing" class="mr-2 h-4 w-4" />
                        {{ form.processing ? 'Updating...' : 'Update Campaign' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
