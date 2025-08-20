<script setup lang="ts">
import axios from '@/axios';
import QuizDeleteDialog from '@/components/QuizDeleteDialog.vue';
import QuizUpdateDialog from '@/components/QuizUpdateDialog.vue';
import Toast from '@/components/Toast.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import Label from '@/components/ui/label/Label.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Quiz } from '@/types/quiz';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Healthcare', href: '/healthcare' }];

const props = defineProps<{ quizzes: Quiz[] }>();

const quizzes = ref<Quiz[]>(props.quizzes);

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
        .post('/api/quizzes', {
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
        .put(`/api/quizzes/${payload.id}`, {
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
        .delete(`/api/quizzes/${payload.id}`)
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
            await axios.patch(`/api/quizzes/${quiz.id}/activate`).then((res) => {
                // Deactivate all others in the frontend
                console.log(res.data.quiz);
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
            await axios.patch(`/api/quizzes/${quiz.id}/deactivate`).then(() => {
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
</script>

<template>
    <Head title="Manage Quizzes" />

    <QuizUpdateDialog v-model:open="updateDialogOpen" :id="quizId" :title="updateQuizTitle" :description="updateQuizDesc" @confirm="updateQuiz" />
    <QuizDeleteDialog v-model:open="deleteDialogOpen" :id="quizId" @confirm="deleteQuiz" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Toast -->
            <Toast ref="toastRef" :title="toastMessage.title" :description="toastMessage.description" :variant="toastMessage.variant" />
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
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
                <Card v-for="quiz in filteredQuizzes" :key="quiz.id">
                    <CardHeader>
                        <CardTitle>{{ quiz.title }}</CardTitle>
                        <CardDescription>{{ quiz.description }}</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <p>Questions: {{ quiz.questions?.length || 0 }}</p>
                        <!-- Active Checkbox -->
                        <Label class="flex items-center gap-2">
                            <input type="checkbox" :checked="quiz.active" @change="() => handleActiveChange(quiz)" />
                            <span>Active</span>
                        </Label>
                        <div class="flex gap-2">
                            <Button variant="default">
                                <Link :href="route('healthcare.quizzes.show', quiz.id)">View</Link>
                            </Button>
                            <Button @click="() => handleEditClick(quiz.id, quiz.title, quiz.description)" variant="secondary">Edit</Button>
                            <Button @click="() => handleDeleteClick(quiz.id)" variant="destructive">Delete</Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
