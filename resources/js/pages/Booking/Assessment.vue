<script setup lang="ts">
import QuizResponseUpdateConfirmDialog from '@/components/QuizResponseUpdateConfirmDialog.vue';
import Toast from '@/components/Toast.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
    { title: 'Appointments', href: '/bookings' },
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
            onError: (errors) => {
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
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toast ref="toastRef" :title="toastMessage.title" :description="toastMessage.description" :variant="toastMessage.variant" />
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Assessment Quiz</h1>
                    <p class="text-muted-foreground">Complete an assessment before your appointment</p>
                </div>
            </div>

            <div class="flex flex-col gap-4">
                <Card v-for="q in localQuiz.questions" :key="q.id" class="w-full">
                    <CardContent class="flex flex-col gap-4">
                        <!-- Question -->
                        <div class="text-lg font-semibold">{{ q.question_text }}</div>

                        <!-- Answer field depending on type -->
                        <div v-if="q.type === QuestionType.MCQ" class="flex flex-col gap-2">
                            <label v-for="opt in q.options" :key="opt" class="flex items-center gap-2">
                                <Input type="radio" :name="`q-${q.id}`" :value="opt" v-model="answers[Number(q.id)]" class="h-3 w-3" />
                                {{ opt }}
                            </label>
                        </div>

                        <div v-else-if="q.type === QuestionType.TRUE_FALSE" class="flex gap-4">
                            <label class="flex items-center gap-2">
                                <Input type="radio" :name="`q-${q.id}`" value="true" v-model="answers[Number(q.id)]" />
                                True
                            </label>
                            <label class="flex items-center gap-2">
                                <Input type="radio" :name="`q-${q.id}`" value="false" v-model="answers[Number(q.id)]" />
                                False
                            </label>
                        </div>

                        <div v-else-if="q.type === QuestionType.TEXT">
                            <Input type="text" v-model="answers[Number(q.id)]" placeholder="Your answer" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="flex justify-end">
                <Button @click="submitAnswers">Submit Assessment</Button>
            </div>
        </div>
    </AppLayout>
</template>
