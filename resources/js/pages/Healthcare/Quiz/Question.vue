<script setup lang="ts">
import axios from '@/axios';
import Icon from '@/components/Icon.vue';
import QuestionDeleteDialog from '@/components/QuestionDeleteDialog.vue';
import QuestionUpdateDialog from '@/components/QuestionUpdateDialog.vue';
import Toast from '@/components/Toast.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { LaravelPagination } from '@/types/pagination';
import { QuestionType, Quiz, QuizQuestion } from '@/types/quiz';
import { Head, router } from '@inertiajs/vue3';
import { MessageCircleQuestion } from 'lucide-vue-next';
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

const props = defineProps<{ quiz: Quiz; questions: LaravelPagination<QuizQuestion> }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Healthcare', href: '/healthcare' },
    { title: 'Quizzes', href: '/healthcare/quizzes' },
    { title: `Question: ${props.quiz.title}`, href: '#' },
];

const pagination = ref<LaravelPagination<QuizQuestion>>(props.questions);
const questions = ref<QuizQuestion[]>(props.questions.data);

const currentPage = ref(pagination.value.current_page);

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
        .post(route('api.quizzes.questions.store', props.quiz.id), {
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
        .put(
            route('api.quizzes.questions.update', {
                quiz: props.quiz.id,
                question: payload.id,
            }),
            {
                text: payload.text,
                option: payload.optionText,
            },
        )
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
        .delete(
            route('api.quizzes.questions.destroy', {
                quiz: props.quiz.id,
                question: payload.id,
            }),
        )
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

function goToPage(page: number) {
    if (page === currentPage.value) return; // prevent duplicate navigation
    router.visit(
        route('healthcare.quizzes.show', {
            quiz: props.quiz.id,
            page,
        }),
    );
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
    <!-- Toast -->
    <Toast ref="toastRef" :title="toastMessage.title" :description="toastMessage.description" :variant="toastMessage.variant" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
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
                            class="inline-flex h-[35px] min-w-[160px] items-center justify-between gap-[5px] rounded-lg border bg-white px-[15px] text-xs leading-none shadow-sm outline-none hover:bg-stone-50 focus:shadow-[0_0_0_2px] focus:shadow-black dark:bg-black dark:hover:bg-accent"
                            aria-label="Filter options"
                        >
                            <SelectValue placeholder="Filter" />
                            <MessageCircleQuestion class="h-3.5 w-3.5" />
                        </SelectTrigger>
                        <SelectPortal>
                            <SelectContent
                                class="data-[side=top]:animate-slideDownAndFade data-[side=right]:animate-slideLeftAndFade data-[side=bottom]:animate-slideUpAndFade data-[side=left]:animate-slideRightAndFade z-[100] min-w-[160px] rounded-lg border bg-white shadow-sm will-change-[opacity,transform] dark:bg-black"
                                :side-offset="5"
                            >
                                <SelectScrollUpButton
                                    class="text-violet11 flex h-[25px] cursor-default items-center justify-center bg-white dark:bg-black"
                                >
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
                                <SelectScrollDownButton
                                    class="text-violet11 flex h-[25px] cursor-default items-center justify-center bg-white dark:bg-black"
                                >
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

                <!-- Questions Table -->
                <div class="overflow-x-auto rounded-lg border">
                    <div class="min-w-[800px]">
                        <!-- Table Header -->
                        <div class="grid grid-cols-3 bg-muted text-sm font-semibold text-muted-foreground">
                            <div class="px-4 py-2">Question</div>
                            <div class="px-4 py-2">Type</div>
                            <div class="px-4 py-2">Actions</div>
                        </div>

                        <!-- Table Rows -->
                        <div
                            v-for="q in questions"
                            :key="q.id"
                            class="grid grid-cols-3 items-center border-t bg-white text-sm hover:bg-stone-50 dark:bg-black hover:dark:bg-accent"
                        >
                            <!-- Question Text -->
                            <div class="truncate px-4 py-3 font-medium">
                                {{ q.question_text }}
                            </div>

                            <!-- Type Badge -->
                            <div class="px-4 py-3">
                                <Badge :variant="typeMap[q.type].variant">
                                    {{ typeMap[q.type].text }}
                                </Badge>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2 px-4 py-3">
                                <Button size="sm" variant="default" @click="handleEditClick(q.id, q.question_text, q.type, q.options?.join(';'))">
                                    Edit
                                </Button>
                                <Button size="sm" variant="destructive" @click="handleDeleteClick(q.id)"> Delete </Button>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-if="questions.length === 0" class="px-4 py-6 text-center text-muted-foreground">No questions found.</div>
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
        </div>
    </AppLayout>
</template>
