<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';

interface Quiz {
    id: number;
    title: string;
    description: string;
    healthcare: {
        id: number;
        name: string;
    };
    questions_count: number;
    has_taken: boolean;
    last_taken?: string;
}

defineProps<{
    quizzes: Quiz[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Health Assessments', href: '#' }];

function takeAssessment(quizId: number) {
    router.visit(route('assessment.show', quizId));
}

function viewHistory() {
    router.visit(route('assessment.history'));
}
</script>

<template>
    <Head title="Health Assessments" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Health Assessments</h1>
                    <p class="text-muted-foreground">Take health assessments created by healthcare professionals</p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="viewHistory"> View History </Button>
                </div>
            </div>

            <!-- Assessments Grid -->
            <div v-if="quizzes.length > 0" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <Card v-for="quiz in quizzes" :key="quiz.id" class="flex flex-col">
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <CardTitle class="text-lg">{{ quiz.title }}</CardTitle>
                                <p class="mt-1 text-sm text-muted-foreground">by {{ quiz.healthcare.name }}</p>
                            </div>
                            <Badge v-if="quiz.has_taken" variant="secondary">Completed</Badge>
                        </div>
                    </CardHeader>
                    <CardContent class="flex flex-1 flex-col">
                        <p class="mb-4 flex-1 text-sm text-muted-foreground">
                            {{ quiz.description || 'No description available' }}
                        </p>

                        <div class="space-y-3">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-muted-foreground">Questions:</span>
                                <span class="font-medium">{{ quiz.questions_count }}</span>
                            </div>

                            <div v-if="quiz.has_taken && quiz.last_taken" class="text-sm">
                                <span class="text-muted-foreground">Last taken:</span>
                                <span class="block font-medium">
                                    {{ new Date(quiz.last_taken).toLocaleDateString() }}
                                </span>
                            </div>

                            <div class="flex gap-2 pt-2">
                                <Button @click="takeAssessment(quiz.id)" class="flex-1" :variant="quiz.has_taken ? 'outline' : 'default'">
                                    {{ quiz.has_taken ? 'Retake' : 'Take Assessment' }}
                                </Button>

                                <Button v-if="quiz.has_taken" @click="viewHistory" variant="outline" size="sm"> Results </Button>
                            </div>
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
                    <h3 class="mb-2 text-lg font-medium text-gray-900">No Assessments Available</h3>
                    <p class="text-muted-foreground">
                        There are no active health assessments available at the moment. Check back later or contact your healthcare provider.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
