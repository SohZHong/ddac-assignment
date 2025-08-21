<script setup lang="ts">
import axios from '@/axios';
import Icon from '@/components/Icon.vue';
import ReportCreateDialog from '@/components/ReportCreateDialog.vue';
import Toast from '@/components/Toast.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem, User } from '@/types';
import { LaravelPagination } from '@/types/pagination';
import { Head, router } from '@inertiajs/vue3';
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

interface Patient extends User {
    bookings_count: number;
    pending_bookings_count: number;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Healthcare', href: '/healthcare' },
    { title: 'Patients', href: '#' },
];

const props = defineProps<{
    patients: LaravelPagination<Patient>;
}>();

const patients = ref<Patient[]>(props.patients.data);

const pagination = ref<LaravelPagination<Patient>>(props.patients);
const currentPage = ref(pagination.value.current_page);

const searchQuery = ref('');
const createDialogOpen = ref(false);

const toastRef = ref<InstanceType<typeof Toast> | null>(null);
const toastMessage = ref({ title: '', description: '', variant: 'default' as 'default' | 'success' | 'destructive' });

const tempPatientId = ref<string>('');

const filteredPatients = computed(() => {
    return patients.value.filter((p) => !searchQuery.value || p.name.toLowerCase().includes(searchQuery.value.toLowerCase()));
});

function handleUploadClick(patientId: string) {
    tempPatientId.value = patientId;
    createDialogOpen.value = true;
}

async function uploadReport(event: { file: File | null; notes: string | null }) {
    if (tempPatientId.value === '' || !tempPatientId.value) {
        toastMessage.value = {
            title: `Invalid Patient ID`,
            description: 'Empty Patient ID',
            variant: 'destructive',
        };

        toastRef.value?.showToast();
        return;
    } else if (!event.file) {
        toastMessage.value = {
            title: `Empty File`,
            description: 'No file uploaded',
            variant: 'destructive',
        };

        toastRef.value?.showToast();
        return;
    }

    const formData = new FormData();
    formData.append('patient_id', tempPatientId.value);
    formData.append('report', event.file);
    if (event.notes) formData.append('notes', event.notes);

    await axios
        .post(route('api.report.store'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        })
        .then((res) => {
            toastMessage.value = {
                title: `Report Uploaded`,
                description: res.data.message,
                variant: 'success',
            };

            toastRef.value?.showToast();
        })
        .catch((err) => {
            toastMessage.value = {
                title: `Report Upload Failed`,
                description: err.message,
                variant: 'destructive',
            };

            toastRef.value?.showToast();
        })
        .finally(() => {
            tempPatientId.value = '';
        });
}

function goToPage(page: number) {
    if (page === currentPage.value) return; // prevent duplicate navigation
    router.visit(route('healthcare.patients.index', { page }));
}
</script>

<template>
    <Head title="My Patients" />
    <Toast ref="toastRef" :title="toastMessage.title" :description="toastMessage.description" :variant="toastMessage.variant" />

    <!-- <ScheduleCreateDialog v-model:open="createDialogOpen" :start="tempSelection?.start" :end="tempSelection?.end" @confirm="confirmSchedule" /> -->
    <ReportCreateDialog v-model:open="createDialogOpen" @confirm="uploadReport" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">My Patients</h1>
                <p class="text-muted-foreground">List of patients with confirmed bookings</p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Patients</CardTitle>
                    <CardDescription>Patients who have bookings with you</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto rounded-lg border">
                        <div class="min-w-[900px]">
                            <!-- Table Header -->
                            <div class="grid grid-cols-5 bg-muted text-sm font-semibold text-muted-foreground">
                                <div class="px-4 py-2">Name</div>
                                <div class="px-4 py-2">Email</div>
                                <div class="px-4 py-2">Total Bookings</div>
                                <div class="px-4 py-2">Pending Bookings</div>
                                <div class="px-4 py-2">Actions</div>
                            </div>

                            <!-- Table Rows -->
                            <div
                                v-for="patient in patients"
                                :key="patient.id"
                                class="grid grid-cols-5 items-center border-t bg-white text-sm hover:bg-stone-50 dark:bg-black dark:hover:bg-accent"
                            >
                                <!-- Name -->
                                <div class="px-4 py-3 font-medium">{{ patient.name }}</div>

                                <!-- Email -->
                                <div class="px-4 py-3 text-muted-foreground">{{ patient.email }}</div>

                                <!-- Total Bookings -->
                                <div class="px-4 py-3 text-muted-foreground">
                                    {{ patient.bookings_count ?? patient.bookings_count ?? '-' }}
                                </div>

                                <!-- Pending Bookings -->
                                <div class="px-4 py-3 text-muted-foreground">
                                    {{ patient.pending_bookings_count ?? patient.pending_bookings_count ?? '-' }}
                                </div>

                                <!-- Actions -->
                                <div class="flex flex-wrap justify-between gap-2 px-4 py-3">
                                    <Button size="sm" variant="default" @click="handleUploadClick(String(patient.id))"> Upload Report </Button>
                                    <Button size="sm" variant="outline" disabled> Meeting </Button>
                                </div>
                            </div>

                            <!-- Empty State -->
                            <div v-if="filteredPatients.length === 0" class="px-4 py-6 text-center text-muted-foreground">No appointments found.</div>
                        </div>
                    </div>
                    <PaginationRoot
                        :total="pagination.total"
                        :items-per-page="pagination.per_page"
                        :default-page="pagination.current_page"
                        show-edges
                    >
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
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
