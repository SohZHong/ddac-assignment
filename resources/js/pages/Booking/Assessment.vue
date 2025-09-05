<script setup lang="ts">
import QuizResponseUpdateConfirmDialog from '@/components/QuizResponseUpdateConfirmDialog.vue';
import Toast from '@/components/Toast.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Booking } from '@/types/booking';
import { QuestionType, Quiz, QuizResponse } from '@/types/quiz';
import { Head, router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
const props = defineProps<{
    booking: Booking;
    quiz: Quiz;
    response?: QuizResponse;
}>();
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Appointments', href: '/appointments' },
    { title: `Assessment: ${props.quiz.title}`, href: '#' },
];

const answers = ref<Record<number, string | number>>({});
const localQuiz = ref<Quiz>(props.quiz);
const localBooking = ref<Booking>(props.booking);
const toastRef = ref<InstanceType<typeof Toast> | null>(null);
const toastMessage = ref({ title: '', description: '', variant: 'default' as 'default' | 'success' | 'destructive' });
const dialogOpen = ref(false);

function submitAnswers() {
    const unanswered = localQuiz.value.questions?.filter((q) => {
        const ans = answers.value[Number(q.id)];
        return ans === '' || ans === null || ans === undefined;
    });

    if (unanswered && unanswered.length > 0) {
        toastMessage.value = {
            title: `Empty Details`,
            description: 'Please answer all the questions!',
            variant: 'destructive',
        };
        toastRef.value?.showToast();
        return;
    }

    if (props.response) {
        dialogOpen.value = true;
    } else {
        handleSubmitAnswer();
    }
}

function handleSubmitAnswer() {
    router.post(
        route('booking.assessment.submit', localBooking.value.id),
        {
            quiz_id: props.quiz.id,
            booking_id: localBooking.value.id,
            answers: Object.entries(answers.value).map(([qId, answer]) => ({
                question_id: qId,
                answer,
            })),
        },
        {
            onSuccess: () => {
                router.visit(route('booking.index'));
            },
            onError: (errors: any) => {
                console.error(errors);
                toastMessage.value = {
                    title: `Assessment Submission Failed`,
                    description: 'Failed to submit assessment',
                    variant: 'destructive',
                };

                toastRef.value?.showToast();
                console.error('Failed to submit assessment');
            },
        },
    );
}

function getRiskLevelText(riskLevel: number): string {
    switch (riskLevel) {
        case 0:
            return 'Low Risk';
        case 1:
            return 'Medium Risk';
        case 2:
            return 'High Risk';
        default:
            return 'Unknown';
    }
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

onMounted(() => {
    localQuiz.value.questions?.forEach((q) => {
        if (!(q.id in answers.value)) {
            answers.value[Number(q.id)] = '';
        }
    });

    if (props.response && props.response.answers) {
        props.response.answers.forEach((answer) => {
            answers.value[Number(answer.question_id)] = answer.answer;
        });
    }
});
</script>

<template>
    <Head :title="`Assessment: ${localQuiz.title}`" />
    <QuizResponseUpdateConfirmDialog v-model:open="dialogOpen" @confirm="submitAnswers" />
    <Toast ref="toastRef" :title="toastMessage.title" :description="toastMessage.description" :variant="toastMessage.variant" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">{{ localQuiz.title }}</h1>
                    <p class="text-muted-foreground">{{ localQuiz.description }}</p>
                    <p class="mt-1 text-sm text-muted-foreground">Created by {{ localBooking.healthcare?.name }}</p>
                </div>

                <!-- Existing Response Warning -->
                <div v-if="response" class="rounded-md border border-amber-200 bg-amber-50 p-4">
                    <p class="text-amber-800">You have already completed this assessment. Submitting again will update your previous responses.</p>
                </div>
            </div>

            <!-- Questions -->
            <div class="flex flex-col gap-4">
                <Card v-for="(question, index) in localQuiz.questions" :key="question.id" class="w-full">
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
                                <input
                                    type="radio"
                                    :name="`q-${question.id}`"
                                    :value="opt"
                                    v-model="answers[Number(question.id)]"
                                    class="h-4 w-4 border-gray-600 bg-gray-700 text-blue-600 focus:ring-blue-500"
                                />
                                <span>{{ opt }}</span>
                            </label>
                        </div>

                        <!-- True/False Questions -->
                        <div v-else-if="question.type === QuestionType.TRUE_FALSE" class="flex gap-4">
                            <label class="flex cursor-pointer items-center gap-2 rounded p-2 hover:bg-gray-600">
                                <input
                                    type="radio"
                                    :name="`q-${question.id}`"
                                    value="true"
                                    v-model="answers[Number(question.id)]"
                                    class="h-4 w-4 border-gray-600 bg-gray-700 text-blue-600 focus:ring-blue-500"
                                />
                                <span>True</span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-2 rounded p-2 hover:bg-gray-600">
                                <input
                                    type="radio"
                                    :name="`q-${question.id}`"
                                    value="false"
                                    v-model="answers[Number(question.id)]"
                                    class="h-4 w-4 border-gray-600 bg-gray-700 text-blue-600 focus:ring-blue-500"
                                />
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

            <!-- Doctor's Feedback Section -->
            <div v-if="localBooking.healthcare_comments || localBooking.risk_level !== null" class="mt-8">
                <Card class="border-black bg-blue-50 dark:border-black dark:bg-black">
                    <CardHeader>
                        <CardTitle class="text-lg text-white dark:text-white"> Doctor's Assessment & Feedback </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Risk Level -->
                        <div v-if="localBooking.risk_level !== null" class="flex items-center gap-3">
                            <span class="text-sm font-medium text-black dark:text-blue-300">Risk Level:</span>
                            <span
                                :class="{
                                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': localBooking.risk_level === 0,
                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': localBooking.risk_level === 1,
                                    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': localBooking.risk_level === 2,
                                }"
                                class="rounded-full px-3 py-1 text-sm font-medium"
                            >
                                {{ getRiskLevelText(localBooking.risk_level) }}
                            </span>
                        </div>

                        <div v-if="localBooking.healthcare_comments" class="space-y-2">
                            <h4 class="text-sm font-medium text-black dark:text-blue-300">Comments from Dr. {{ localBooking.healthcare?.name }}:</h4>
                            <div class="rounded-lg border border-black bg-white p-4 dark:border-black dark:bg-gray-800">
                                <p class="leading-relaxed text-gray-700 dark:text-gray-300">
                                    {{ localBooking.healthcare_comments }}
                                </p>
                            </div>
                        </div>

                        <!-- Assessment Date -->
                        <div class="dark:white text-dark text-xs">Assessment completed on {{ formatDate(localBooking.start_time) }}</div>
                    </CardContent>
                </Card>
            </div>

            <!-- Submit Button -->
            <div class="flex items-center justify-between">
                <Button variant="outline" @click="router.visit(route('booking.index'))"> Back to Appointments </Button>
                <Button @click="submitAnswers" class="px-8"> Submit Assessment </Button>
            </div>
        </div>
    </AppLayout>
</template>
