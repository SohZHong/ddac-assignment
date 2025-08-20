<script setup lang="ts">
import axios from '@/axios';
import Icon from '@/components/Icon.vue';
import QuestionDeleteDialog from '@/components/QuestionDeleteDialog.vue';
import QuestionUpdateDialog from '@/components/QuestionUpdateDialog.vue';
import Toast from '@/components/Toast.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { QuestionType, Quiz, QuizQuestion } from '@/types/quiz';
import { Head } from '@inertiajs/vue3';
import { MessageCircleQuestion } from 'lucide-vue-next';

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

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Healthcare', href: '/healthcare' }];

const questionTypeFilterOptions = [
    {
        title: 'Multiple Choice',
        value: QuestionType.MCQ,
    },
    {
        title: 'True/False',
        value: QuestionType.TRUE_FALSE,
    },
    {
        title: 'Text',
        value: QuestionType.TEXT,
    },
];

interface TypeInfo {
    text: string;
    variant?: 'default' | 'destructive' | 'outline' | 'secondary' | null;
}

const typeClass = (type: QuestionType) => {
    switch (type) {
        case QuestionType.MCQ:
            return 'bg-yellow-500';
        case QuestionType.TRUE_FALSE:
            return 'bg-green-500';
        case QuestionType.TEXT:
            return 'bg-red-500';
    }
};

const props = defineProps<{ quiz: Quiz }>();
const questions = ref<QuizQuestion[] | undefined>(props.quiz.questions);

const toastRef = ref<InstanceType<typeof Toast> | null>(null);
const toastMessage = ref({ title: '', description: '', variant: 'default' as 'default' | 'success' | 'destructive' });

const typeMap: Record<QuestionType, TypeInfo> = {
    [QuestionType.MCQ]: { text: 'Multiple Choice', variant: 'secondary' },
    [QuestionType.TRUE_FALSE]: { text: 'True/False', variant: 'default' },
    [QuestionType.TEXT]: { text: 'Text', variant: 'destructive' },
};

const questionId = ref('');
const newQuestionText = ref('');
const newQuestionType = ref<QuestionType>(QuestionType.MCQ);
const newQuestionOptionsText = ref('');
const updateQuestionText = ref('');
const updateQuestionType = ref<QuestionType>(QuestionType.MCQ);
const updateQuestionOptionsText = ref<string>();

const updateDialogOpen = ref(false);
const deleteDialogOpen = ref(false);

async function addQuestion() {
    if (newQuestionText.value === '') return;
    await axios
        .post(`/api/quizzes/${props.quiz.id}/questions`, {
            question_text: newQuestionText.value,
            type: newQuestionType.value,
            options: newQuestionOptionsText.value.split(';').map((s) => s.trim()),
        })
        .then((res) => {
            questions.value?.push(res.data.question);

            toastMessage.value = {
                title: `Question Created`,
                description: `Question has been created successfully`,
                variant: 'success',
            };

            toastRef.value?.showToast();
        })
        .catch((err) => {
            toastMessage.value = {
                title: `Failed to create question`,
                description: err.message,
                variant: 'destructive',
            };
            toastRef.value?.showToast();

            console.error('Error creating a question', err);
        })
        .finally(() => {
            newQuestionText.value = '';
            newQuestionOptionsText.value = '';
        });
}

async function updateQuestion(payload: { id: string; text: string; optionText?: string }) {
    await axios
        .put(`/api/quizzes/${props.quiz.id}/questions/${payload.id}`, {
            text: payload.text,
            option: payload.optionText,
        })
        .then(() => {
            const question = questions.value?.find((q) => String(q.id) === String(payload.id));
            if (question) {
                question.question_text = payload.text;
                question.options = payload.optionText?.split(';').map((s) => s.trim());
            }

            toastMessage.value = {
                title: `Question Updated`,
                description: `Question has been successfully updated`,
                variant: 'success',
            };

            toastRef.value?.showToast();
        })
        .catch((err) => {
            toastMessage.value = {
                title: `Failed to updated question`,
                description: err.message,
                variant: 'destructive',
            };
            toastRef.value?.showToast();
            console.error('Failed to updated question', err);
        })
        .finally(() => {
            updateDialogOpen.value = false;
            questionId.value = '';
            updateQuestionText.value = '';
            updateQuestionType.value = QuestionType.MCQ;
            updateQuestionOptionsText.value = '';
        });
}

async function deleteQuestion(payload: { id: string }) {
    await axios
        .delete(`/api/quizzes/${props.quiz.id}/questions/${payload.id}`)
        .then(() => {
            // Remove the question from the array
            questions.value = questions.value?.filter((q) => String(q.id) !== String(payload.id));

            toastMessage.value = {
                title: `Question Deleted`,
                description: `Question has been successfully deleted`,
                variant: 'success',
            };

            toastRef.value?.showToast();
        })
        .catch((err) => {
            toastMessage.value = {
                title: `Failed to delete question`,
                description: err.message,
                variant: 'destructive',
            };
            toastRef.value?.showToast();
            console.error('Failed to delete question', err);
        })
        .finally(() => {
            deleteDialogOpen.value = false;
            questionId.value = '';
        });
}

function handleEditClick(id: string, text: string, type: QuestionType, optionText?: string) {
    questionId.value = String(id);
    updateQuestionText.value = text;
    updateQuestionType.value = type;
    updateQuestionOptionsText.value = optionText;

    updateDialogOpen.value = true;
}

function handleDeleteClick(id: string) {
    questionId.value = String(id);

    deleteDialogOpen.value = true;
}
</script>

<template>
    <Head :title="quiz.title" />
    <QuestionUpdateDialog
        v-model:open="updateDialogOpen"
        :id="questionId"
        :type="updateQuestionType"
        :text="updateQuestionText"
        :option-text="updateQuestionOptionsText"
        @confirm="updateQuestion"
    />
    <QuestionDeleteDialog v-model:open="deleteDialogOpen" :id="questionId" @confirm="deleteQuestion" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Toast -->
            <Toast ref="toastRef" :title="toastMessage.title" :description="toastMessage.description" :variant="toastMessage.variant" />
            <!-- Header -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Assessment: {{ quiz.title }}</h1>
                    <p class="text-muted-foreground">Manage your questions for this "{{ quiz.title }}" assessment quiz</p>
                </div>
            </div>
            <!-- Create New Quiz -->
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <Input v-model="newQuestionText" placeholder="Question text" />
                    <SelectRoot v-model="newQuestionType">
                        <SelectTrigger
                            class="text-grass11 data-[placeholder]:text-green9 inline-flex h-[35px] min-w-[160px] items-center justify-between gap-[5px] rounded-lg border bg-white px-[15px] text-xs leading-none shadow-sm outline-none hover:bg-stone-50 focus:shadow-[0_0_0_2px] focus:shadow-black"
                            aria-label="Filter options"
                        >
                            <SelectValue placeholder="Filter" />
                            <MessageCircleQuestion class="h-3.5 w-3.5" />
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
                                    <SelectLabel class="text-mauve11 px-[25px] text-xs leading-[25px] font-bold"> Question Types </SelectLabel>
                                    <SelectGroup>
                                        <SelectItem
                                            v-for="(option, index) in questionTypeFilterOptions"
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
                </div>
                <Input
                    v-if="newQuestionType === QuestionType.MCQ"
                    v-model="newQuestionOptionsText"
                    placeholder="Semicolon separated options (e.g. option 1; option 2; option 3)"
                />
                <Button @click="addQuestion">Add Question</Button>

                <div class="flex flex-col gap-4">
                    <Card v-for="q in questions" :key="q.id" class="w-full transition-shadow duration-200 hover:shadow-lg">
                        <CardContent class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div class="flex flex-1 flex-col gap-4 md:flex-row md:items-center">
                                <div :class="['h-2 w-2 rounded-full', typeClass(q.type)]"></div>
                                <div class="text-lg font-semibold">{{ q.question_text }}</div>
                            </div>
                            <div class="flex items-center gap-4">
                                <Button size="sm" variant="default" @click="handleEditClick(q.id, q.question_text, q.type, q.options?.join(';'))">
                                    Edit
                                </Button>
                                <Button size="sm" variant="destructive" @click="handleDeleteClick(q.id)">Delete</Button>
                            </div>
                            <Badge :variant="typeMap[q.type].variant">{{ typeMap[q.type].text }}</Badge>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
