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
// Keep track of answers
const answers = ref<Record<number, string | number>>({});
const localQuiz = ref<Quiz>(props.quiz);
const localBooking = ref<Booking>(props.booking);
const toastRef = ref<InstanceType<typeof Toast> | null>(null);
const toastMessage = ref({ title: '', description: '', variant: 'default' as 'default' | 'success' | 'destructive' });
const dialogOpen = ref(false);

function submitAnswers() {
    // Check if the user has answered before
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
                // Add toast
                toastMessage.value = {
                    title: `Assessment Submission Failed`,
                    description: 'Failed to submit assessment',
                    variant: 'destructive',
                };

                // Show toast
                toastRef.value?.showToast();
                console.error('Failed to submit assessment');
            },
        },
    );
}

onMounted(() => {
    // Initialize answers
    localQuiz.value.questions?.forEach((q) => {
        if (!(q.id in answers.value)) {
            answers.value[Number(q.id)] = '';
        }
    });
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
                <Button variant="outline" @click="router.visit(route('booking.index'))"> Back to Appointments </Button>
                <Button @click="submitAnswers" class="px-8"> Submit Assessment </Button>
            </div>
        </div>
    </AppLayout>
</template>
