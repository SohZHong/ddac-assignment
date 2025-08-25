<script setup lang="ts">
import axios from '@/axios';
import Toast from '@/components/Toast.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Booking, BookingStatus } from '@/types/booking';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    upcoming: Booking[];
    past: Booking[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Appointments', href: '#' }];

const toastRef = ref<InstanceType<typeof Toast> | null>(null);
const toastMessage = ref({ title: '', description: '', variant: 'default' as 'default' | 'success' | 'destructive' });

const searchQuery = ref('');

const statusMap: Record<BookingStatus, { text: string; variant: 'default' | 'destructive' | 'secondary' }> = {
    [BookingStatus.PENDING]: { text: 'Pending', variant: 'secondary' },
    [BookingStatus.CONFIRMED]: { text: 'Confirmed', variant: 'default' },
    [BookingStatus.CANCELLED]: { text: 'Cancelled', variant: 'destructive' },
};

const upcomingBookings = ref<Booking[]>(props.upcoming);
const pastBookings = ref<Booking[]>(props.past);

const filteredUpcoming = computed(() =>
    upcomingBookings.value.filter((b) => !searchQuery.value || b.healthcare?.name.toLowerCase().includes(searchQuery.value.toLowerCase())),
);

const filteredPast = computed(() =>
    pastBookings.value.filter((b) => !searchQuery.value || b.healthcare?.name.toLowerCase().includes(searchQuery.value.toLowerCase())),
);

async function cancelBooking(id: string) {
    const status = BookingStatus.CANCELLED;
    await axios
        .patch(route('api.booking.cancel', id))
        .then(() => {
            // Update the local booking status
            const booking = upcomingBookings.value.find((b) => b.id === id);
            if (booking) {
                booking.status = status;
            }

            toastMessage.value = {
                title: `Booking Cancelled`,
                description: `Successfully cancelled appointment with ${booking?.healthcare?.name}.`,
                variant: 'destructive',
            };

            // Show toast
            toastRef.value?.showToast();
        })
        .catch((err) => {
            toastMessage.value = {
                title: `Failed to cancael booking`,
                description: err.message,
                variant: 'destructive',
            };
            console.error('Failed to cancel booking', err);
            toastRef.value?.showToast();
        });
}
</script>

<template>
    <Head title="My Appointments" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toast ref="toastRef" :title="toastMessage.title" :description="toastMessage.description" :variant="toastMessage.variant" />
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">My Appointments</h1>
                    <p class="text-muted-foreground">View your upcoming and past appointments</p>
                </div>

                <div class="flex flex-row items-center gap-4">
                    <Input v-model="searchQuery" placeholder="Search by doctor" icon="search" class="min-w-[200px]" />
                </div>
            </div>
            <!-- Upcoming -->
            <div>
                <h2 class="mb-4 text-xl font-semibold">Upcoming</h2>
                <div v-if="filteredUpcoming.length > 0" class="overflow-x-auto rounded-lg border">
                    <div class="min-w-[700px]">
                        <!-- Table Header -->
                        <div class="grid grid-cols-4 bg-muted text-sm font-semibold text-muted-foreground">
                            <div class="px-4 py-2">Doctor</div>
                            <div class="px-4 py-2">Time</div>
                            <div class="px-4 py-2">Status</div>
                            <div class="px-4 py-2">Actions</div>
                        </div>

                        <!-- Table Rows -->
                        <div
                            v-for="booking in filteredUpcoming"
                            :key="booking.id"
                            class="grid grid-cols-4 items-center border-t bg-white text-sm hover:bg-stone-50 dark:bg-black dark:hover:bg-accent"
                        >
                            <!-- Doctor -->
                            <div class="px-4 py-3 font-medium">{{ booking.healthcare?.name }}</div>

                            <!-- Time -->
                            <div class="px-4 py-3 text-muted-foreground">
                                {{ new Date(booking.start_time).toLocaleString() }} –
                                {{ new Date(booking.end_time).toLocaleString() }}
                            </div>

                            <!-- Status -->
                            <div class="px-4 py-3">
                                <Badge :variant="statusMap[booking.status].variant">
                                    {{ statusMap[booking.status].text }}
                                </Badge>
                            </div>

                            <!-- Actions -->
                            <div class="flex flex-wrap justify-between gap-2 px-4 py-3" v-if="booking.status !== BookingStatus.CANCELLED">
                                <Button size="sm" variant="default">
                                    <Link :href="route('booking.assessment.index', booking.id)">Start Assessment</Link>
                                </Button>
                                <Button size="sm" variant="destructive" @click="cancelBooking(booking.id!)">Cancel</Button>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center text-muted-foreground">No upcoming appointments.</div>
            </div>

            <!-- Past -->
            <div>
                <h2 class="mb-4 text-xl font-semibold">Past</h2>
                <div v-if="filteredPast.length > 0" class="overflow-x-auto rounded-lg border">
                    <div class="min-w-[700px]">
                        <!-- Table Header -->
                        <div class="grid grid-cols-3 bg-muted text-sm font-semibold text-muted-foreground">
                            <div class="px-4 py-2">Doctor</div>
                            <div class="px-4 py-2">Time</div>
                            <div class="px-4 py-2">Status</div>
                        </div>

                        <!-- Table Rows -->
                        <div
                            v-for="booking in filteredPast"
                            :key="booking.id"
                            class="grid grid-cols-3 items-center border-t bg-white text-sm hover:bg-stone-50 dark:bg-black dark:hover:bg-accent"
                        >
                            <!-- Doctor -->
                            <div class="px-4 py-3 font-medium">{{ booking.healthcare?.name }}</div>

                            <!-- Time -->
                            <div class="px-4 py-3 text-muted-foreground">
                                {{ new Date(booking.start_time).toLocaleString() }} –
                                {{ new Date(booking.end_time).toLocaleString() }}
                            </div>

                            <!-- Status -->
                            <div class="px-4 py-3">
                                <Badge :variant="statusMap[booking.status].variant">
                                    {{ statusMap[booking.status].text }}
                                </Badge>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center text-muted-foreground">No past appointments.</div>
            </div>
        </div>
    </AppLayout>
</template>
