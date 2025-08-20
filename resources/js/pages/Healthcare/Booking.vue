<script setup lang="ts">
import axios from '@/axios';
import Icon from '@/components/Icon.vue';
import Toast from '@/components/Toast.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import { Card, CardContent } from '@/components/ui/card';
import Input from '@/components/ui/input/Input.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Booking, BookingStatus } from '@/types/booking';
import { Head } from '@inertiajs/vue3';
import { Filter } from 'lucide-vue-next';
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
import { computed, ref } from 'vue';

interface StatusInfo {
    text: string;
    variant?: 'default' | 'destructive' | 'outline' | 'secondary' | null;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Healthcare', href: '/healthcare' }];

const statusFilterOptions = [
    {
        title: 'Pending',
        value: BookingStatus.PENDING,
    },
    {
        title: 'Confirmed',
        value: BookingStatus.CONFIRMED,
    },
    {
        title: 'Cancelled',
        value: BookingStatus.CANCELLED,
    },
];
const statusMap: Record<BookingStatus, StatusInfo> = {
    [BookingStatus.PENDING]: { text: 'Pending', variant: 'secondary' },
    [BookingStatus.CONFIRMED]: { text: 'Confirmed', variant: 'default' },
    [BookingStatus.CANCELLED]: { text: 'Cancelled', variant: 'destructive' },
};

// Toast ref
const toastRef = ref<InstanceType<typeof Toast> | null>(null);
const toastMessage = ref({ title: '', description: '', variant: 'default' as 'default' | 'success' | 'destructive' });

const statusClass = (status: BookingStatus) => {
    switch (status) {
        case BookingStatus.PENDING:
            return 'bg-yellow-500';
        case BookingStatus.CONFIRMED:
            return 'bg-green-500';
        case BookingStatus.CANCELLED:
            return 'bg-red-500';
    }
};

const props = defineProps<{ bookings: Booking[] }>();
const bookings = ref<Booking[]>(props.bookings);

const searchQuery = ref('');
const statusFilter = ref<BookingStatus | undefined>();

const filteredBookings = computed(() => {
    return bookings.value
        .filter((b) => !searchQuery.value || b.patient.name.toLowerCase().includes(searchQuery.value.toLowerCase()))
        .filter((b) => !statusFilter.value || b.status === statusFilter.value);
});

async function approveBooking(id: string) {
    const status = BookingStatus.CONFIRMED;
    await axios
        .patch(`/api/bookings/approve/${id}`, { status })
        .then(() => {
            // Update the local booking status
            const booking = bookings.value.find((b) => b.id === id);
            if (booking) {
                booking.status = status;
            }

            toastMessage.value = {
                title: `Booking Confirmed`,
                description: `${booking?.patient.name}'s appointment has been confirmed`,
                variant: 'success',
            };

            // Show toast
            toastRef.value?.showToast();
        })
        .catch((err) => {
            toastMessage.value = {
                title: `Failed to approve booking`,
                description: err.message,
                variant: 'destructive',
            };
            console.error('Failed to approve booking', err);
            toastRef.value?.showToast();
        });
}

async function declineBooking(id: string) {
    const status = BookingStatus.CANCELLED;
    await axios
        .patch(`/api/bookings/decline/${id}`, { status })
        .then(() => {
            // Update the local booking status
            const booking = bookings.value.find((b) => b.id === id);
            if (booking) {
                booking.status = status;
            }

            toastMessage.value = {
                title: `Booking Cancelled`,
                description: `${booking?.patient.name}'s appointment has been cancelled.`,
                variant: 'destructive',
            };

            // Show toast
            toastRef.value?.showToast();
        })
        .catch((err) => {
            toastMessage.value = {
                title: `Failed to decline booking`,
                description: err.message,
                variant: 'destructive',
            };
            console.error('Failed to decline booking', err);
            toastRef.value?.showToast();
        });
}
</script>

<template>
    <Head title="Manage Bookings" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toast ref="toastRef" :title="toastMessage.title" :description="toastMessage.description" :variant="toastMessage.variant" />
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Appointments</h1>
                    <p class="text-muted-foreground">Manage your appointments</p>
                </div>

                <!-- Search + Filter -->
                <div class="flex flex-row items-center gap-4">
                    <Input v-model="searchQuery" placeholder="Search by patient name" icon="search" class="min-w-[200px]" />
                    <SelectRoot v-model="statusFilter">
                        <SelectTrigger
                            class="text-grass11 data-[placeholder]:text-green9 inline-flex h-[35px] min-w-[160px] items-center justify-between gap-[5px] rounded-lg border bg-white px-[15px] text-xs leading-none shadow-sm outline-none hover:bg-stone-50 focus:shadow-[0_0_0_2px] focus:shadow-black"
                            aria-label="Filter options"
                        >
                            <SelectValue placeholder="Filter" />
                            <Filter class="h-3.5 w-3.5" />
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
                                    <SelectLabel class="text-mauve11 px-[25px] text-xs leading-[25px]"> All Status </SelectLabel>
                                    <SelectGroup>
                                        <SelectItem
                                            v-for="(option, index) in statusFilterOptions"
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
            </div>
            <div class="flex flex-col gap-4">
                <div v-if="filteredBookings.length === 0" class="text-center text-muted-foreground">No appointments found.</div>
                <Card v-for="booking in filteredBookings" :key="booking.id" class="w-full transition-shadow duration-200 hover:shadow-lg">
                    <CardContent class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div class="flex flex-1 flex-col gap-4 md:flex-row md:items-center">
                            <div :class="['h-2 w-2 rounded-full', statusClass(booking.status)]"></div>
                            <div class="text-lg font-semibold">{{ booking.patient.name }}</div>
                            <div class="text-sm text-muted-foreground">{{ booking.patient.email }}</div>
                            <div class="text-sm text-muted-foreground">
                                {{ new Date(booking.start_time).toLocaleString() }} - {{ new Date(booking.end_time).toLocaleString() }}
                            </div>
                        </div>
                        <div v-if="booking.status !== BookingStatus.CANCELLED" class="flex items-center gap-4">
                            <Button
                                size="sm"
                                variant="default"
                                @click="approveBooking(booking.id!)"
                                :disabled="booking.status === BookingStatus.CONFIRMED"
                            >
                                Confirm
                            </Button>
                            <Button size="sm" variant="destructive" @click="declineBooking(booking.id!)">Cancel</Button>
                        </div>
                        <Badge :variant="statusMap[booking.status].variant">{{ statusMap[booking.status].text }}</Badge>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
