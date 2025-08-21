<script setup lang="ts">
import axios from '@/axios';
import Icon from '@/components/Icon.vue';
import QuizDeleteDialog from '@/components/QuizDeleteDialog.vue';
import QuizUpdateDialog from '@/components/QuizUpdateDialog.vue';
import Toast from '@/components/Toast.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import Label from '@/components/ui/label/Label.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { LaravelPagination } from '@/types/pagination';
import { Quiz } from '@/types/quiz';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    PaginationEllipsis,
    PaginationFirst,
    PaginationLast,
    PaginationList,
    PaginationListItem,
    PaginationNext,
    PaginationPrev,
    PaginationRoot,
} from 'reka-ui';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Healthcare', href: '/healthcare' },
    {
        title: 'Quizzes',
        href: '/healthcare/quizzes',
    },
];

const props = defineProps<{ quizzes: LaravelPagination<Quiz> }>();

const quizzes = ref<Quiz[]>(props.quizzes.data);

const pagination = ref<LaravelPagination<Quiz>>(props.quizzes);
const currentPage = ref(pagination.value.current_page);

const quizId = ref('');
const newQuizTitle = ref('');
const newQuizDesc = ref();
const updateQuizTitle = ref('');
const updateQuizDesc = ref();

const searchQuery = ref('');

const updateDialogOpen = ref(false);
const deleteDialogOpen = ref(false);

const toastRef = ref<InstanceType<typeof Toast> | null>(null);
const toastMessage = ref({ title: '', description: '', variant: 'default' as 'default' | 'success' | 'destructive' });

const filteredQuizzes = computed(() => {
    return quizzes.value.filter((q) => !searchQuery.value || q.title.toLowerCase().includes(searchQuery.value.toLowerCase()));
});

async function createQuiz() {
    await axios
        .post(route('api.quizzes.store'), {
            title: newQuizTitle.value,
            description: newQuizDesc.value,
        })
        .then((res) => {
            quizzes.value.push(res.data.quiz);

            toastMessage.value = {
                title: `Quiz Created`,
                description: `Quiz: "${newQuizTitle.value}" has been created`,
                variant: 'success',
            };

            toastRef.value?.showToast();
        })
        .catch((err) => {
            toastMessage.value = {
                title: `Failed to create quiz`,
                description: err.message,
                variant: 'destructive',
            };
            toastRef.value?.showToast();
            console.error('Failed to create quiz', err);
        })
        .finally(() => {
            newQuizTitle.value = '';
            newQuizDesc.value = '';
        });
}

async function updateQuiz(payload: { id: string; title: string; description?: string }) {
    await axios
        .put(route('api.quizzes.update', payload.id), {
            title: payload.title,
            description: payload.description,
        })
        .then(() => {
            const quiz = quizzes.value.find((q) => String(q.id) === String(payload.id));
            if (quiz) {
                quiz.title = payload.title;
                quiz.description = payload.description;
            }
            toastMessage.value = {
                title: `Quiz Updated`,
                description: `Quiz: "${payload.title}" has been updated`,
                variant: 'success',
            };

            toastRef.value?.showToast();
        })
        .catch((err) => {
            toastMessage.value = {
                title: `Failed to updated quiz`,
                description: err.message,
                variant: 'destructive',
            };
            toastRef.value?.showToast();
            console.error('Failed to updated quiz', err);
        })
        .finally(() => {
            updateDialogOpen.value = false;
            quizId.value = '';
            updateQuizTitle.value = '';
            updateQuizDesc.value = undefined;
        });
}

async function deleteQuiz(payload: { id: string }) {
    await axios
        .delete(route('api.quizzes.destroy', payload.id))
        .then(() => {
            // Remove the quiz from the array
            quizzes.value = quizzes.value.filter((q) => String(q.id) !== String(payload.id));

            toastMessage.value = {
                title: `Quiz Deleted`,
                description: `Quiz has been successfully deleted`,
                variant: 'success',
            };

            toastRef.value?.showToast();
        })
        .catch((err) => {
            toastMessage.value = {
                title: `Failed to delete quiz`,
                description: err.message,
                variant: 'destructive',
            };
            toastRef.value?.showToast();
            console.error('Failed to delete quiz', err);
        })
        .finally(() => {
            deleteDialogOpen.value = false;
            quizId.value = '';
        });
}

async function handleActiveChange(quiz: Quiz) {
    try {
        if (!quiz.active) {
            // Activating this quiz
            await axios.patch(route('api.quizzes.activate', quiz.id)).then((res) => {
                // Deactivate all others in the frontend
                quizzes.value.forEach((q) => {
                    q.active = q.id === res.data.quiz.id;
                });
                toastMessage.value = {
                    title: `Quiz Activated`,
                    description: `"${quiz.title}" is now active.`,
                    variant: 'success',
                };
            });
        } else {
            // Deactivating this quiz
            await axios.patch(route('api.quizzes.deactivate', quiz.id)).then(() => {
                quiz.active = false;
                toastMessage.value = {
                    title: `Quiz Deactivated`,
                    description: `"${quiz.title}" has been deactivated.`,
                    variant: 'success',
                };
            });
        }

        toastRef.value?.showToast();
    } catch (err) {
        toastMessage.value = {
            title: `Failed to update quiz`,
            description: (err as unknown as Error).message,
            variant: 'destructive',
        };
        toastRef.value?.showToast();
        console.error('Failed to update active state', err);
    }
}

function handleEditClick(id: string, title: string, description?: string) {
    quizId.value = String(id);
    updateQuizTitle.value = title;
    updateQuizDesc.value = description;

    updateDialogOpen.value = true;
}

function handleDeleteClick(id: string) {
    quizId.value = String(id);

    deleteDialogOpen.value = true;
}

function goToPage(page: number) {
    if (page === currentPage.value) return; // prevent duplicate navigation
    router.visit(route('healthcare.quizzes.index', { page }));
}
</script>

<template>
    <Head title="Manage Quizzes" />
    <!-- Toast -->
    <Toast ref="toastRef" :title="toastMessage.title" :description="toastMessage.description" :variant="toastMessage.variant" />

    <QuizUpdateDialog v-model:open="updateDialogOpen" :id="quizId" :title="updateQuizTitle" :description="updateQuizDesc" @confirm="updateQuiz" />
    <QuizDeleteDialog v-model:open="deleteDialogOpen" :id="quizId" @confirm="deleteQuiz" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Assessment Quizzes</h1>
                    <p class="text-muted-foreground">Manage your assessment quizzes for patients</p>
                </div>
            </div>
            <!-- Create New Quiz -->
            <Card>
                <CardHeader>
                    <CardTitle>Create New Quiz</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <Input v-model="newQuizTitle" placeholder="Quiz Title" />
                    <Input v-model="newQuizDesc" placeholder="Description" />
                    <Button @click="createQuiz">Create Quiz</Button>
                </CardContent>
            </Card>
            <!-- Search -->
            <div class="flex flex-row items-center gap-4">
                <Input v-model="searchQuery" placeholder="Search by quiz title" icon="search" class="min-w-[200px]" />
            </div>
            <!-- Existing Quizzes -->
            <div class="overflow-x-auto rounded-lg border">
                <div class="min-w-[900px]">
                    <!-- Table Header -->
                    <div class="grid grid-cols-5 bg-muted text-sm font-semibold text-muted-foreground">
                        <div class="px-4 py-2">Title</div>
                        <div class="px-4 py-2">Description</div>
                        <div class="px-4 py-2">Questions</div>
                        <div class="px-4 py-2">Active</div>
                        <div class="px-4 py-2">Actions</div>
                    </div>

                    <!-- Table Rows -->
                    <div
                        v-for="quiz in filteredQuizzes"
                        :key="quiz.id"
                        class="grid grid-cols-5 items-center border-t bg-white text-sm hover:bg-stone-50 dark:bg-black dark:hover:bg-accent"
                    >
                        <!-- Title -->
                        <div class="px-4 py-3 font-medium">{{ quiz.title }}</div>

                        <!-- Description -->
                        <div class="truncate px-4 py-3 text-muted-foreground">
                            {{ quiz.description }}
                        </div>

                        <!-- Questions Count -->
                        <div class="px-4 py-3">
                            {{ quiz.questions?.length || 0 }}
                        </div>

                        <!-- Active Checkbox -->
                        <div class="px-4 py-3">
                            <Label class="flex items-center gap-2">
                                <input type="checkbox" :checked="quiz.active" @change="() => handleActiveChange(quiz)" />
                                <span class="sr-only">Active</span>
                            </Label>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-wrap justify-between gap-2 px-4 py-3">
                            <Button variant="default">
                                <Link :href="route('healthcare.quizzes.show', quiz.id)">View</Link>
                            </Button>
                            <Button @click="() => handleEditClick(quiz.id, quiz.title, quiz.description)" variant="secondary"> Edit </Button>
                            <Button @click="() => handleDeleteClick(quiz.id)" variant="destructive"> Delete </Button>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="filteredQuizzes.length === 0" class="px-4 py-6 text-center text-muted-foreground">No quizzes found.</div>
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
