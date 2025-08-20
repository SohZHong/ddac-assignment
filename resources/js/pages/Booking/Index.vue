<script setup lang="ts">
import Badge from '@/components/ui/badge/Badge.vue';
import { Card, CardContent } from '@/components/ui/card';
import Input from '@/components/ui/input/Input.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Booking, BookingList, BookingStatus } from '@/types/booking';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    upcoming: Booking[];
    past: BookingList;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Appointments', href: '/bookings' }];

const searchQuery = ref('');

const statusMap: Record<BookingStatus, { text: string; variant: 'default' | 'destructive' | 'secondary' }> = {
    [BookingStatus.PENDING]: { text: 'Pending', variant: 'secondary' },
    [BookingStatus.CONFIRMED]: { text: 'Confirmed', variant: 'default' },
    [BookingStatus.CANCELLED]: { text: 'Cancelled', variant: 'destructive' },
};

const filteredUpcoming = computed(() =>
    props.upcoming.filter((b) => !searchQuery.value || b.healthcare?.name.toLowerCase().includes(searchQuery.value.toLowerCase())),
);

const filteredPast = computed(() =>
    props.past.data.filter((b) => !searchQuery.value || b.healthcare?.name.toLowerCase().includes(searchQuery.value.toLowerCase())),
);
</script>

<template>
    <Head title="My Appointments" />
    <AppLayout :breadcrumbs="breadcrumbs">
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
