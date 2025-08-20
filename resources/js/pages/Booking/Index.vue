<script setup lang="ts">
import axios from '@/axios';
import Toast from '@/components/Toast.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import { Card, CardContent } from '@/components/ui/card';
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

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Appointments', href: '/bookings' }];

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
        .patch(`/api/bookings/cancel/${id}`, { status })
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
                <div v-if="filteredUpcoming.length > 0" class="flex flex-col gap-4">
                    <Card v-for="booking in filteredUpcoming" :key="booking.id">
                        <CardContent class="flex flex-col md:flex-row md:items-center md:justify-between">
                            <div class="flex flex-col gap-2">
                                <div class="text-lg font-semibold">{{ booking.healthcare?.name }}</div>
                                <div class="text-sm text-muted-foreground">
                                    {{ new Date(booking.start_time).toLocaleString() }} –
                                    {{ new Date(booking.end_time).toLocaleString() }}
                                </div>
                            </div>
                            <div v-if="booking.status !== BookingStatus.CANCELLED" class="flex items-center gap-4">
                                <Button size="sm" variant="default">
                                    <Link :href="route('booking.assessment.index', booking.id)">Start Assessment </Link>
                                </Button>
                                <Button size="sm" variant="destructive" @click="cancelBooking(booking.id!)">Cancel</Button>
                            </div>
                            <Badge :variant="statusMap[booking.status].variant">
                                {{ statusMap[booking.status].text }}
                            </Badge>
                        </CardContent>
                    </Card>
                </div>
                <div v-else class="text-center text-muted-foreground">No upcoming appointments.</div>
            </div>

            <!-- Past -->
            <div>
                <h2 class="mb-4 text-xl font-semibold">Past</h2>
                <div v-if="filteredPast.length > 0" class="flex flex-col gap-4">
                    <Card v-for="booking in filteredPast" :key="booking.id">
                        <CardContent class="flex flex-col md:flex-row md:items-center md:justify-between">
                            <div class="flex flex-col gap-2">
                                <div class="text-lg font-semibold">{{ booking.healthcare?.name }}</div>
                                <div class="text-sm text-muted-foreground">
                                    {{ new Date(booking.start_time).toLocaleString() }} –
                                    {{ new Date(booking.end_time).toLocaleString() }}
                                </div>
                            </div>
                            <Badge :variant="statusMap[booking.status].variant">
                                {{ statusMap[booking.status].text }}
                            </Badge>
                        </CardContent>
                    </Card>
                </div>
                <div v-else class="text-center text-muted-foreground">No past appointments.</div>
            </div>
        </div>
    </AppLayout>
</template>
