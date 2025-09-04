<script setup lang="ts">
import Toast from '@/components/Toast.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { QuestionType } from '@/types/quiz';
import { Head, router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

interface Quiz {
    id: number;
    title: string;
    description: string;
    healthcare: {
        id: number;
        name: string;
    };
    questions: Array<{
        id: number;
        question_text: string;
        type: QuestionType;
        options?: string[];
    }>;
}

interface RecentResponse {
    id: number;
    completed_at: string;
}

const props = defineProps<{
    quiz: Quiz;
    recentResponse?: RecentResponse;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Health Assessments', href: '/assessments' },
    { title: props.quiz.title, href: '#' },
];

const answers = ref<Record<number, string | number>>({});
const toastRef = ref<InstanceType<typeof Toast> | null>(null);
const toastMessage = ref({
    title: '',
    description: '',
    variant: 'default' as 'default' | 'success' | 'destructive',
});

function submitAnswers() {
    const unanswered = props.quiz.questions.filter((q) => {
        const ans = answers.value[Number(q.id)];
        return ans === '' || ans === null || ans === undefined;
    });

    if (unanswered.length > 0) {
        toastMessage.value = {
            title: 'Incomplete Assessment',
            description: 'Please answer all questions before submitting.',
            variant: 'destructive',
        };
        toastRef.value?.showToast();
        return;
    }

    router.post(
        route('assessment.submit', props.quiz.id),
        {
            answers: Object.entries(answers.value).map(([qId, answer]) => ({
                question_id: qId,
                answer,
            })),
        },
        {
            onSuccess: () => {
                toastMessage.value = {
                    title: 'Assessment Completed',
                    description: 'Your assessment has been submitted successfully.',
                    variant: 'success',
                };
                toastRef.value?.showToast();
            },
            onError: (errors: any) => {
                console.error(errors);
                toastMessage.value = {
                    title: 'Submission Failed',
                    description: 'Failed to submit assessment. Please try again.',
                    variant: 'destructive',
                };
                toastRef.value?.showToast();
            },
        },
    );
}

onMounted(() => {
    // Initialize answers
    props.quiz.questions.forEach((q) => {
        if (!(q.id in answers.value)) {
            answers.value[Number(q.id)] = '';
        }
    });
});
</script>

<template>
    <Head :title="`Assessment: ${quiz.title}`" />
    <Toast ref="toastRef" :title="toastMessage.title" :description="toastMessage.description" :variant="toastMessage.variant" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">{{ quiz.title }}</h1>
                    <p class="text-muted-foreground">{{ quiz.description }}</p>
                    <p class="mt-1 text-sm text-muted-foreground">Created by {{ quiz.healthcare.name }}</p>
                </div>

                <!-- Recent Response Warning -->
                <div v-if="recentResponse" class="rounded-md border border-amber-200 bg-amber-50 p-4">
                    <p class="text-amber-800">
                        You completed this assessment recently on
                        {{ new Date(recentResponse.completed_at).toLocaleString() }}. Taking it again will create a new submission.
                    </p>
                </div>
            </div>

            <!-- Questions -->
            <div class="flex flex-col gap-4">
                <Card v-for="(question, index) in quiz.questions" :key="question.id" class="w-full">
                    <CardHeader>
                        <CardTitle class="text-lg"> {{ index + 1 }}. {{ question.question_text }} </CardTitle>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-4">
                        <!-- Multiple Choice Questions -->
                        <div v-if="question.type === QuestionType.MCQ" class="flex flex-col gap-2">
                            <label
                                v-for="opt in question.options"
                                :key="opt"
                                class="flex cursor-pointer items-center gap-2 rounded p-2 hover:bg-gray-600"
                            >
                                <Input type="radio" :name="`q-${question.id}`" :value="opt" v-model="answers[Number(question.id)]" class="h-4 w-4" />
                                <span>{{ opt }}</span>
                            </label>
                        </div>

                        <!-- True/False Questions -->
                        <div v-else-if="question.type === QuestionType.TRUE_FALSE" class="flex gap-4">
                            <label class="flex cursor-pointer items-center gap-2 rounded p-2 hover:bg-gray-600">
                                <Input type="radio" :name="`q-${question.id}`" value="true" v-model="answers[Number(question.id)]" class="h-4 w-4" />
                                <span>True</span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-2 rounded p-2 hover:bg-gray-600">
                                <Input type="radio" :name="`q-${question.id}`" value="false" v-model="answers[Number(question.id)]" class="h-4 w-4" />
                                <span>False</span>
                            </label>
                        </div>

                        <!-- Text Questions -->
                        <div v-else-if="question.type === QuestionType.TEXT">
                            <Input type="text" v-model="answers[Number(question.id)]" placeholder="Enter your answer..." class="w-full" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Submit Button -->
            <div class="flex items-center justify-between">
                <Button variant="outline" @click="router.visit(route('assessment.index'))"> Back to Assessments </Button>
                <Button @click="submitAnswers" class="px-8"> Submit Assessment </Button>
            </div>
        </div>
    </AppLayout>
</template>
