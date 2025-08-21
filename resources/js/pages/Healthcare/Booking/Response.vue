<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import Button from '@/components/ui/button/Button.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { PatientRiskLevel } from '@/types/booking';
import { QuizResponse } from '@/types/quiz';
import { Head, useForm } from '@inertiajs/vue3';
import { DiamondMinus } from 'lucide-vue-next';
import {
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectItemIndicator,
    SelectItemText,
    SelectLabel,
    SelectPortal,
    SelectRoot,
    SelectScrollDownButton,
    SelectScrollUpButton,
    SelectTrigger,
    SelectValue,
    SelectViewport,
} from 'reka-ui';
import { ref } from 'vue';

const props = defineProps<{
    quizResponse: QuizResponse;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Healthcare', href: '/healthcare' },
    { title: 'Appointments', href: '/healthcare/appointments' },
    { title: 'Assessment Results', href: '#' },
];

const riskLevelOptions = [
    {
        title: 'Low Risk',
        value: PatientRiskLevel.LOW,
    },
    {
        title: 'Medium Risk',
        value: PatientRiskLevel.MID,
    },
    {
        title: 'High Risk',
        value: PatientRiskLevel.HIGH,
    },
];

const localResponse = ref<QuizResponse>(props.quizResponse);

const form = useForm({
    healthcare_comments: props.quizResponse.booking?.healthcare_comments ?? '',
    risk_level: props.quizResponse.booking?.risk_level ?? PatientRiskLevel.LOW,
});

function saveForm() {
    form.patch(route('healthcare.appointment.responses.review', localResponse.value.booking_id));
}
</script>

<template>
    <Head :title="localResponse.booking?.patient.name" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">{{ localResponse.booking?.patient.name }}'s Assessment Response</h1>
                    <p class="text-muted-foreground">Quiz Title: {{ localResponse.quiz?.title }}</p>
                </div>
            </div>

            <!-- Quiz Responses -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Card v-for="question in localResponse.quiz?.questions" :key="question.id">
                    <CardHeader>
                        <CardTitle>{{ question.question_text }}</CardTitle>
                        <CardDescription> Answer: </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        {{ question.answer }}
                    </CardContent>
                </Card>
            </div>
            <!-- Consultant Notes -->
            <Card>
                <CardHeader>
                    <CardTitle>Consultant Review</CardTitle>
                    <CardDescription> Add comments and categorize the patient's risk level. </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <SelectRoot v-model="form.risk_level">
                        <SelectTrigger
                            class="text-grass11 data-[placeholder]:text-green9 inline-flex h-[35px] min-w-[160px] items-center justify-between gap-[5px] rounded-lg border bg-white px-[15px] text-xs leading-none shadow-sm outline-none hover:bg-stone-50 focus:shadow-[0_0_0_2px] focus:shadow-black"
                            aria-label="Risk Level Options"
                        >
                            <SelectValue placeholder="Risk Level" />
                            <DiamondMinus class="h-3.5 w-3.5" />
                        </SelectTrigger>
                        <SelectPortal>
                            <SelectContent
                                class="data-[side=top]:animate-slideDownAndFade data-[side=right]:animate-slideLeftAndFade data-[side=bottom]:animate-slideUpAndFade data-[side=left]:animate-slideRightAndFade z-[100] min-w-[160px] rounded-lg border bg-white shadow-sm will-change-[opacity,transform]"
                                :side-offset="5"
                            >
                                <SelectScrollUpButton class="text-violet11 flex h-[25px] cursor-default items-center justify-center bg-white">
                                    <Icon name="chevron up" icon="radix-icons:chevron-up" />
                                </SelectScrollUpButton>
                                <SelectViewport class="p-[5px]">
                                    <SelectLabel class="text-mauve11 px-[25px] text-xs leading-[25px]"> All Risk Levels </SelectLabel>
                                    <SelectGroup>
                                        <SelectItem
                                            v-for="(option, index) in riskLevelOptions"
                                            :key="index"
                                            class="text-grass11 data-[disabled]:text-mauve8 data-[highlighted]:bg-green9 data-[highlighted]:text-green1 relative flex h-[25px] items-center rounded-[3px] pr-[35px] pl-[25px] text-xs leading-none select-none data-[disabled]:pointer-events-none data-[highlighted]:outline-none"
                                            :value="option.value"
                                        >
                                            <SelectItemIndicator class="absolute left-0 inline-flex w-[25px] items-center justify-center">
                                                <Icon name="check" icon="radix-icons:check" />
                                            </SelectItemIndicator>
                                            <SelectItemText>
                                                {{ option.title }}
                                            </SelectItemText>
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectViewport>
                                <SelectScrollDownButton class="text-violet11 flex h-[25px] cursor-default items-center justify-center bg-white">
                                    <Icon name="chevron down" icon="radix-icons:chevron-down" />
                                </SelectScrollDownButton>
                            </SelectContent>
                        </SelectPortal>
                    </SelectRoot>
                    <!-- Comments -->
                    <textarea
                        v-model="form.healthcare_comments"
                        placeholder="Enter consultant comments here..."
                        class="w-full rounded-md border p-2"
                        rows="4"
                    ></textarea>

                    <!-- Save Button -->
                    <Button @click="saveForm" class="rounded bg-primary px-4 py-2 text-white" :disabled="form.processing">Save Review</Button>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
