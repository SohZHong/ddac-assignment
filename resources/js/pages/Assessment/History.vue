<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { PatientRiskLevel } from '@/types/booking';
import { Head, router } from '@inertiajs/vue3';
import { onMounted } from 'vue';

interface PaginatedResponses {
    data: Array<{
        id: number;
        quiz: {
            title: string;
            healthcare: {
                name: string;
            };
        };
        completed_at: string;
        is_standalone: boolean;
        doctor_response: {
            comments: string | null;
            risk_level: number | null;
            risk_level_text: string | null;
            has_response: boolean;
        };
    }>;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

const props = defineProps<{
    responses?: PaginatedResponses;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Health Assessments', href: '/assessments' },
    { title: 'Assessment History', href: '#' },
];

// Debug: Log the received data
onMounted(() => {
    console.log('Assessment History Props:', props.responses);
    console.log('Data array:', props.responses?.data);
    console.log('Data length:', props.responses?.data?.length);
});
</script>

<template>
    <Head title="Assessment History" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Assessment History</h1>
                    <p class="text-muted-foreground">View all your completed health assessments</p>
                </div>
                <div class="flex gap-2">
                    <Button @click="router.visit(route('assessment.index'))"> Take New Assessment </Button>
                </div>
            </div>

            <!-- Assessments List -->
            <div v-if="responses?.data && responses.data.length > 0" class="space-y-4">
                <Card v-for="response in responses.data" :key="response.id">
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <CardTitle class="text-lg">{{ response.quiz.title }}</CardTitle>
                                <p class="mt-1 text-sm text-muted-foreground">by {{ response.quiz.healthcare.name }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <Badge :variant="response.is_standalone ? 'secondary' : 'default'">
                                    {{ response.is_standalone ? 'Health Screening' : 'Appointment Assessment' }}
                                </Badge>
                                <Badge
                                    v-if="response.doctor_response.risk_level !== null"
                                    :class="{
                                        'border-green-300 bg-green-100 text-green-800': response.doctor_response.risk_level === PatientRiskLevel.LOW,
                                        'border-yellow-300 bg-yellow-100 text-yellow-800':
                                            response.doctor_response.risk_level === PatientRiskLevel.MID,
                                        'border-red-300 bg-red-100 text-red-800': response.doctor_response.risk_level === PatientRiskLevel.HIGH,
                                    }"
                                    variant="outline"
                                >
                                    {{ response.doctor_response.risk_level_text }} Risk
                                </Badge>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <!-- Doctor's Response Preview -->
                        <div v-if="response.doctor_response.comments" class="mb-3 rounded-lg border border-blue-200 bg-black p-3">
                            <div class="flex items-start gap-2">
                                <svg class="mt-0.5 h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                    ></path>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-white">Healthcare Provider Response:</p>
                                    <p v-if="response.doctor_response.comments" class="mt-1 line-clamp-2 text-lg text-green-700">
                                        {{
                                            response.doctor_response.comments.length > 100
                                                ? response.doctor_response.comments.substring(0, 100) + '...'
                                                : response.doctor_response.comments
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="text-sm text-muted-foreground">
                                Completed on {{ new Date(response.completed_at).toLocaleDateString() }} at
                                {{ new Date(response.completed_at).toLocaleTimeString() }}
                            </div>
                            <Button variant="outline" size="sm" @click="router.visit(route('assessment.results', response.id))">
                                View Details
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <div v-else class="py-12 text-center">
                <div class="mx-auto max-w-md">
                    <div class="mb-4">
                        <svg class="mx-auto h-12 w-12 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            ></path>
                        </svg>
                    </div>
                    <h3 class="mb-2 text-lg font-medium text-gray-900">No Assessment History</h3>
                    <p class="mb-4 text-muted-foreground">You haven't completed any health assessments yet.</p>
                    <Button @click="router.visit(route('assessment.index'))"> Take Your First Assessment </Button>
                </div>
            </div>

            <!-- Pagination -->
            <div
                v-if="responses?.data && responses.data.length > 0 && responses.meta && responses.meta.last_page > 1"
                class="flex items-center justify-center gap-2"
            >
                <Button
                    v-for="link in responses.links"
                    :key="link.label"
                    :variant="link.active ? 'default' : 'outline'"
                    :disabled="!link.url"
                    size="sm"
                    @click="link.url && router.visit(link.url)"
                >
                    {{ link.label.replace('&laquo;', '«').replace('&raquo;', '»') }}
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
