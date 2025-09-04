<script setup lang="ts">
import ConsultationBookingModal from '@/components/ConsultationBookingModal.vue';
import Toast from '@/components/Toast.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { PatientRiskLevel } from '@/types/booking';
import { Head, router, usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

interface AssessmentResponse {
    id: number;
    quiz: {
        id: number;
        title: string;
        description: string;
        healthcare: {
            id: number;
            name: string;
        };
    };
    completed_at: string;
    answers_count: number;
    is_standalone: boolean;
    doctor_response: {
        comments: string | null;
        risk_level: number | null;
        risk_level_text: string | null;
        has_response: boolean;
    };
    questions_with_answers?: Array<{
        id: number;
        question: string;
        type: string;
        options: Array<{
            id: number;
            text: string;
            is_selected: boolean;
        }>;
        user_answer: string | null;
    }>;
}

defineProps<{
    response: AssessmentResponse;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Health Assessments', href: '/assessments' },
    { title: 'Assessment Results', href: '#' },
];

const showBookingModal = ref(false);
const toastRef = ref<InstanceType<typeof Toast> | null>(null);
const page = usePage();

// Check for flash messages on mount
onMounted(() => {
    const flash = page.props.flash as any;
    if (flash?.success) {
        setTimeout(() => {
            if (toastRef.value) {
                toastRef.value.showToast();
            }
        }, 100);
    }
});

function handleBookingSuccess() {
    // Show success message when booking is successful
    setTimeout(() => {
        if (toastRef.value) {
            toastRef.value.showToast();
        }
    }, 100);
}
</script>

<template>
    <Head title="Assessment Results" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Assessment Complete</h1>
                    <p class="text-muted-foreground">Your assessment has been successfully submitted</p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="router.visit(route('assessment.index'))"> Take Another Assessment </Button>
                    <Button @click="router.visit(route('assessment.history'))"> View All Results </Button>
                </div>
            </div>

            <!-- Results Card -->
            <Card class="max-w-2xl">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle class="text-xl">{{ response.quiz.title }}</CardTitle>
                        <Badge variant="secondary">Completed</Badge>
                    </div>
                    <p class="text-muted-foreground">{{ response.quiz.description }}</p>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-muted-foreground">Healthcare Provider:</span>
                            <p class="font-medium">{{ response.quiz.healthcare.name }}</p>
                        </div>
                        <div>
                            <span class="text-muted-foreground">Completed On:</span>
                            <p class="font-medium">{{ new Date(response.completed_at).toLocaleString() }}</p>
                        </div>
                        <div>
                            <span class="text-muted-foreground">Questions Answered:</span>
                            <p class="font-medium">{{ response.answers_count }}</p>
                        </div>
                        <div>
                            <span class="text-muted-foreground">Assessment Type:</span>
                            <p class="font-medium">{{ response.is_standalone ? 'Health Screening' : 'Appointment Assessment' }}</p>
                        </div>
                    </div>

                    <div class="border-t pt-4">
                        <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                            <div class="flex items-center gap-2">
                                <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="font-medium text-green-800">Assessment Submitted Successfully</span>
                            </div>
                            <p class="mt-2 text-sm text-green-700">
                                Your responses have been recorded and will be reviewed by the healthcare provider. You may be contacted if any
                                follow-up is needed.
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Assessment Answers -->
            <Card v-if="response.questions_with_answers" class="max-w-4xl">
                <CardHeader>
                    <CardTitle class="text-white">Your Answers</CardTitle>
                    <p class="text-gray-300">Review the answers you provided for this assessment</p>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div v-for="(question, index) in response.questions_with_answers" :key="question.id" class="space-y-3">
                        <div class="flex items-start gap-3">
                            <div class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-500 text-sm font-medium text-white">
                                {{ index + 1 }}
                            </div>
                            <div class="flex-1 space-y-3">
                                <h4 class="font-medium text-white">{{ question.question }}</h4>

                                <!-- Multiple Choice Options -->
                                <div class="space-y-2">
                                    <div
                                        v-for="option in question.options"
                                        :key="option.id"
                                        class="flex items-center gap-3 rounded-lg border p-3"
                                        :class="{
                                            'border-blue-400 bg-blue-900/30': option.is_selected,
                                            'border-gray-600 bg-gray-800/50': !option.is_selected,
                                        }"
                                    >
                                        <div
                                            class="h-4 w-4 rounded-full border-2"
                                            :class="{
                                                'border-blue-400 bg-blue-500': option.is_selected,
                                                'border-gray-500': !option.is_selected,
                                            }"
                                        >
                                            <div
                                                v-if="option.is_selected"
                                                class="h-full w-full rounded-full bg-white"
                                                style="transform: scale(0.6)"
                                            ></div>
                                        </div>
                                        <span
                                            :class="{
                                                'font-medium text-blue-200': option.is_selected,
                                                'text-gray-300': !option.is_selected,
                                            }"
                                        >
                                            {{ option.text }}
                                        </span>
                                        <div v-if="option.is_selected" class="ml-auto">
                                            <Badge variant="default" class="bg-blue-600 text-white">Selected</Badge>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Divider between questions -->
                        <div v-if="index < response.questions_with_answers.length - 1" class="border-t border-gray-600"></div>
                    </div>
                </CardContent>
            </Card>

            <!-- Doctor's Response -->
            <Card v-if="response.doctor_response.has_response" class="max-w-4xl">
                <CardHeader>
                    <CardTitle class="text-white">Doctor's Assessment</CardTitle>
                    <p class="text-gray-300">Healthcare provider's review of your assessment</p>
                </CardHeader>
                <CardContent class="space-y-4">
                    <!-- Risk Level -->
                    <div v-if="response.doctor_response.risk_level !== null" class="space-y-2">
                        <h4 class="font-medium text-white">Risk Level</h4>
                        <div class="flex items-center gap-2">
                            <Badge
                                :variant="
                                    response.doctor_response.risk_level === PatientRiskLevel.LOW
                                        ? 'default'
                                        : response.doctor_response.risk_level === PatientRiskLevel.MID
                                          ? 'secondary'
                                          : 'destructive'
                                "
                                :class="{
                                    'bg-green-600 text-white': response.doctor_response.risk_level === PatientRiskLevel.LOW,
                                    'bg-yellow-600 text-white': response.doctor_response.risk_level === PatientRiskLevel.MID,
                                    'bg-red-600 text-white': response.doctor_response.risk_level === PatientRiskLevel.HIGH,
                                }"
                            >
                                {{ response.doctor_response.risk_level_text }} Risk
                            </Badge>
                        </div>
                    </div>

                    <!-- Comments -->
                    <div v-if="response.doctor_response.comments" class="space-y-2">
                        <h4 class="font-medium text-white">Healthcare Provider Comments</h4>
                        <div class="rounded-lg border border-gray-600 bg-gray-800/50 p-4">
                            <p class="whitespace-pre-wrap text-gray-300">{{ response.doctor_response.comments }}</p>
                        </div>
                    </div>

                    <!-- Provider Info -->
                    <div class="flex items-center gap-2 border-t border-gray-600 pt-4 text-sm text-gray-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                            ></path>
                        </svg>
                        <span>Reviewed by {{ response.quiz.healthcare.name }}</span>
                    </div>
                </CardContent>
            </Card>

            <!-- Next Steps -->
            <Card class="max-w-2xl">
                <CardHeader>
                    <CardTitle>What's Next?</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <!-- Consultation Booking Option -->
                    <div class="mb-4 rounded-lg border border-blue-200 bg-blue-300 p-4">
                        <div class="flex items-start gap-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-sm font-medium text-blue-600">📅</div>
                            <div class="flex-1">
                                <p class="font-medium text-blue-900">Want to discuss your results?</p>
                                <p class="mt-1 text-sm text-blue-700">
                                    Book a consultation with {{ response.quiz.healthcare.name }} to discuss your assessment results and get
                                    personalized health advice.
                                </p>
                                <Button @click="showBookingModal = true" class="mt-3 bg-blue-600 text-white hover:bg-blue-700" size="sm">
                                    Book Consultation
                                </Button>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="mr-3 flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-sm font-medium text-blue-600">1</div>
                        <div class="w-9/10">
                            <p class="font-medium">Healthcare Provider Review</p>
                            <p class="text-sm text-muted-foreground">
                                Your responses will be reviewed by the healthcare provider who created this assessment.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="mr-3 flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-sm font-medium text-blue-600">2</div>
                        <div class="w-9/10">
                            <p class="font-medium">Follow-up Communication</p>
                            <p class="text-sm text-muted-foreground">
                                If needed, the healthcare provider may contact you for additional information or to schedule an appointment.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="mr-3 flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-sm font-medium text-blue-600">3</div>
                        <div class="w-9/10">
                            <p class="font-medium">Continue Health Monitoring</p>
                            <p class="text-sm text-muted-foreground">
                                You can take other available assessments or check your assessment history anytime.
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Consultation Booking Modal -->
        <ConsultationBookingModal
            v-model:open="showBookingModal"
            :healthcare-id="response.quiz.healthcare.id"
            :quiz-response-id="response.id"
            @close="showBookingModal = false"
            @booking-success="handleBookingSuccess"
        />

        <!-- Success Toast -->
        <Toast
            ref="toastRef"
            :title="'Booking Successful'"
            :description="page.props.flash?.success || 'Your consultation has been booked successfully!'"
            variant="success"
        />
    </AppLayout>
</template>
