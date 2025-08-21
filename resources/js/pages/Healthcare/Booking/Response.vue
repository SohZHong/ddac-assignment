<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import Button from '@/components/ui/button/Button.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { PatientRiskLevel } from '@/types/booking';
import { LaravelPagination } from '@/types/pagination';
import { QuizQuestion, QuizResponse } from '@/types/quiz';
import { Head, router, useForm } from '@inertiajs/vue3';
import { DiamondMinus } from 'lucide-vue-next';
import {
    PaginationEllipsis,
    PaginationFirst,
    PaginationLast,
    PaginationList,
    PaginationListItem,
    PaginationNext,
    PaginationPrev,
    PaginationRoot,
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
    questions: LaravelPagination<QuizQuestion>;
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

const pagination = ref<LaravelPagination<QuizQuestion>>(props.questions);

const currentPage = ref(pagination.value.current_page);
const localResponse = ref<QuizResponse>(props.quizResponse);
const localQuestion = ref<QuizQuestion[]>(props.questions.data);
const form = useForm({
    healthcare_comments: props.quizResponse.booking?.healthcare_comments ?? '',
    risk_level: props.quizResponse.booking?.risk_level ?? PatientRiskLevel.LOW,
});

function saveForm() {
    form.patch(route('healthcare.appointment.responses.review', localResponse.value.booking_id));
}

function goToPage(page: number) {
    if (page === currentPage.value) return; // prevent duplicate navigation
    router.visit(
        route('ealthcare.appointment.responses.show', {
            page,
            booking: localResponse.value.booking_id,
            response: localResponse.value.id,
        }),
    );
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
            <!-- Consultant Notes -->
            <Card>
                <CardHeader>
                    <CardTitle>Consultant Review</CardTitle>
                    <CardDescription> Add comments and categorize the patient's risk level. </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <SelectRoot v-model="form.risk_level">
                        <SelectTrigger
                            class="inline-flex h-[35px] min-w-[160px] items-center justify-between gap-[5px] rounded-lg border bg-white px-[15px] text-xs leading-none shadow-sm outline-none hover:bg-stone-50 focus:shadow-[0_0_0_2px] focus:shadow-black dark:bg-black dark:hover:bg-accent"
                            aria-label="Risk Level Options"
                        >
                            <SelectValue placeholder="Risk Level" />
                            <DiamondMinus class="h-3.5 w-3.5" />
                        </SelectTrigger>
                        <SelectPortal>
                            <SelectContent
                                class="data-[side=top]:animate-slideDownAndFade data-[side=right]:animate-slideLeftAndFade data-[side=bottom]:animate-slideUpAndFade data-[side=left]:animate-slideRightAndFade z-[100] min-w-[160px] rounded-lg border bg-white shadow-sm will-change-[opacity,transform] dark:bg-black dark:hover:bg-accent"
                                :side-offset="5"
                            >
                                <SelectScrollUpButton
                                    class="text-violet11 flex h-[25px] cursor-default items-center justify-center bg-white dark:bg-black"
                                >
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
                                <SelectScrollDownButton
                                    class="text-violet11 flex h-[25px] cursor-default items-center justify-center bg-white dark:bg-black"
                                >
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
                    <Button @click="saveForm" :disabled="form.processing">Save Review</Button>
                </CardContent>
            </Card>

            <!-- Quiz Responses -->
            <!-- <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Card v-for="question in localResponse.quiz?.questions" :key="question.id">
                    <CardHeader>
                        <CardTitle>{{ question.question_text }}</CardTitle>
                        <CardDescription> Answer: </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        {{ question.answer }}
                    </CardContent>
                </Card>
            </div> -->
            <div class="overflow-x-auto rounded-lg border">
                <div class="min-w-[900px]">
                    <!-- Table Header -->
                    <div class="grid grid-cols-2 bg-muted text-sm font-semibold text-muted-foreground">
                        <div class="px-4 py-2">Question</div>
                        <div class="px-4 py-2">Answer</div>
                    </div>

                    <!-- Table Rows -->
                    <div
                        v-for="question in localQuestion"
                        :key="question.id"
                        class="grid grid-cols-2 items-center border-t bg-white text-sm hover:bg-stone-50 dark:bg-black dark:hover:bg-accent"
                    >
                        <div class="px-4 py-3 font-medium">{{ question.question_text }}</div>
                        <div class="px-4 py-3 text-muted-foreground">{{ question.answer }}</div>
                    </div>
                </div>
            </div>
            <PaginationRoot :total="pagination.total" :items-per-page="pagination.per_page" :default-page="pagination.current_page" show-edges>
                <PaginationList v-slot="{ items }">
                    <PaginationFirst
                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-transparent transition hover:bg-white disabled:opacity-50 dark:hover:bg-stone-700/70"
                    >
                        <Icon name="double-arrow-left" icon="radix-icons:double-arrow-left" />
                    </PaginationFirst>
                    <PaginationPrev
                        class="mr-4 flex h-9 w-9 items-center justify-center rounded-lg bg-transparent transition hover:bg-white disabled:opacity-50 dark:hover:bg-stone-700/70"
                    >
                        <Icon name="chevron-left" icon="radix-icons:chevron-left" />
                    </PaginationPrev>
                    <template v-for="(page, index) in items">
                        <PaginationListItem
                            class="h-9 w-9 rounded-lg border transition hover:bg-white data-[selected]:!bg-white data-[selected]:text-black data-[selected]:shadow-sm dark:border-stone-800 dark:hover:bg-stone-700/70"
                            v-if="page.type === 'page'"
                            :key="index"
                            :value="page.value"
                            @click="goToPage(page.value)"
                        >
                            {{ page.value }}
                        </PaginationListItem>

                        <PaginationEllipsis class="flex h-9 w-9 items-center justify-center" v-else :key="page.type" :index="index">
                            &#8230;
                        </PaginationEllipsis>
                    </template>
                    <PaginationNext
                        class="ml-4 flex h-9 w-9 items-center justify-center rounded-lg bg-transparent transition hover:bg-white disabled:opacity-50 dark:hover:bg-stone-700/70"
                    >
                        <Icon name="chevron-right" icon="radix-icons:chevron-right" />
                    </PaginationNext>
                    <PaginationLast
                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-transparent transition hover:bg-white disabled:opacity-50 dark:hover:bg-stone-700/70"
                    >
                        <Icon name="double-arrow-right" icon="radix-icons:double-arrow-right" />
                    </PaginationLast>
                </PaginationList>
            </PaginationRoot>
        </div>
    </AppLayout>
</template>
