<template>
    <Head title="Event Livestream" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="outline" size="sm" @click="goBack">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back to Event
                    </Button>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">{{ eventTitle }} - Livestream</h1>
                        <p class="text-muted-foreground">Join the livestream for this event</p>
                    </div>
                </div>
            </div>

            <!-- Livestream Room Component -->
            <div class="flex-1">
                <LivekitRoom :room-id="roomId" :event-title="eventTitle" />
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import LivekitRoom from '@/components/LivekitRoom.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

interface Props {
    roomId: number;
    eventTitle: string;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Events',
        href: '/events',
    },
    {
        title: props.eventTitle,
        href: `/events/${props.roomId}`,
    },
    {
        title: 'Livestream',
        href: `/events/${props.roomId}/livestream`,
    },
];

const goBack = () => {
    router.visit(`/events/${props.roomId}`);
};
</script>
