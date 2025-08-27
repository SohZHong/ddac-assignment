<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Calendar as CalendarIcon, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

type ViewMode = 'month' | 'week' | 'day';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Events', href: '/events' },
    { title: 'Calendar', href: '/events/calendar' },
];

const today = new Date();
const currentDate = ref(new Date(today.getFullYear(), today.getMonth(), today.getDate()));
const viewMode = ref<ViewMode>('month');
const events = ref<Array<{ id: number; title: string; start: string; end: string; type: string; status: string }>>([]);
const loading = ref(false);

const startOfWeek = (d: Date) => {
    const x = new Date(d);
    const day = x.getDay();
    const diff = (day + 6) % 7; // make Monday start (0)
    x.setDate(x.getDate() - diff);
    x.setHours(0, 0, 0, 0);
    return x;
};

const endOfWeek = (d: Date) => {
    const x = startOfWeek(d);
    x.setDate(x.getDate() + 6);
    x.setHours(23, 59, 59, 999);
    return x;
};

const startOfMonth = (d: Date) => new Date(d.getFullYear(), d.getMonth(), 1);
const endOfMonth = (d: Date) => new Date(d.getFullYear(), d.getMonth() + 1, 0);

const range = computed(() => {
    if (viewMode.value === 'day') {
        const from = new Date(currentDate.value);
        from.setHours(0, 0, 0, 0);
        const to = new Date(currentDate.value);
        to.setHours(23, 59, 59, 999);
        return { from, to };
    }
    if (viewMode.value === 'week') {
        return { from: startOfWeek(currentDate.value), to: endOfWeek(currentDate.value) };
    }
    return { from: startOfMonth(currentDate.value), to: endOfMonth(currentDate.value) };
});

const fetchEvents = async () => {
    loading.value = true;
    try {
        const qs = new URLSearchParams({
            from: range.value.from.toISOString().slice(0, 10),
            to: range.value.to.toISOString().slice(0, 10),
        });
        const res = await fetch(`/events-feed?${qs.toString()}`, { credentials: 'same-origin' });
        const json = await res.json();
        events.value = json.events ?? [];
    } finally {
        loading.value = false;
    }
};

onMounted(fetchEvents);
watch([currentDate, viewMode], fetchEvents);

const next = () => {
    const d = new Date(currentDate.value);
    if (viewMode.value === 'day') d.setDate(d.getDate() + 1);
    else if (viewMode.value === 'week') d.setDate(d.getDate() + 7);
    else d.setMonth(d.getMonth() + 1);
    currentDate.value = d;
};

const prev = () => {
    const d = new Date(currentDate.value);
    if (viewMode.value === 'day') d.setDate(d.getDate() - 1);
    else if (viewMode.value === 'week') d.setDate(d.getDate() - 7);
    else d.setMonth(d.getMonth() - 1);
    currentDate.value = d;
};

const goToday = () => {
    currentDate.value = new Date();
};

const title = computed(() => {
    const d = currentDate.value;
    if (viewMode.value === 'day') return d.toLocaleDateString();
    if (viewMode.value === 'week') {
        const a = startOfWeek(d),
            b = endOfWeek(d);
        return `${a.toLocaleDateString()} - ${b.toLocaleDateString()}`;
    }
    return `${d.toLocaleString('default', { month: 'long' })} ${d.getFullYear()}`;
});

const openEvent = (id: number) => router.visit(`/events/${id}`);
</script>

<template>
    <Head title="Calendar" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between gap-4">
                    <CardTitle class="flex items-center gap-2">
                        <CalendarIcon class="h-5 w-5" />
                        {{ title }}
                    </CardTitle>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" size="sm" @click="prev"><ChevronLeft class="h-4 w-4" /></Button>
                        <Button variant="outline" size="sm" @click="goToday">Today</Button>
                        <Button variant="outline" size="sm" @click="next"><ChevronRight class="h-4 w-4" /></Button>
                        <Select v-model="viewMode">
                            <SelectTrigger class="w-[140px]"><SelectValue placeholder="View" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="month">Month</SelectItem>
                                <SelectItem value="week">Week</SelectItem>
                                <SelectItem value="day">Day</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="loading" class="py-8 text-center text-sm text-muted-foreground">Loading events…</div>
                    <div v-else>
                        <div v-if="viewMode === 'day'">
                            <div class="space-y-2">
                                <div v-for="e in events" :key="e.id" class="flex items-center justify-between rounded-md border p-3 hover:bg-accent">
                                    <div>
                                        <div class="font-medium">{{ e.title }}</div>
                                        <div class="text-xs text-muted-foreground">
                                            {{ new Date(e.start).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }} -
                                            {{ new Date(e.end).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
                                        </div>
                                    </div>
                                    <Button size="sm" variant="outline" @click="openEvent(e.id)">Open</Button>
                                </div>
                            </div>
                        </div>
                        <div v-else-if="viewMode === 'week'">
                            <div class="grid grid-cols-7 gap-2">
                                <div v-for="i in 7" :key="i" class="min-h-[140px] rounded-md border p-2">
                                    <div class="mb-1 text-xs text-muted-foreground">
                                        {{ new Date(startOfWeek(currentDate).getTime() + (i - 1) * 86400000).toLocaleDateString() }}
                                    </div>
                                    <div class="space-y-1">
                                        <div
                                            v-for="e in events.filter(
                                                (ev) =>
                                                    new Date(ev.start).toDateString() ===
                                                    new Date(startOfWeek(currentDate).getTime() + (i - 1) * 86400000).toDateString(),
                                            )"
                                            :key="e.id"
                                            class="cursor-pointer truncate rounded bg-primary/10 px-2 py-1 text-xs"
                                            @click="openEvent(e.id)"
                                        >
                                            {{ e.title }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else>
                            <div class="grid grid-cols-7 gap-2">
                                <div
                                    v-for="i in new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0).getDate()"
                                    :key="i"
                                    class="min-h-[120px] rounded-md border p-2"
                                >
                                    <div class="mb-1 text-xs text-muted-foreground">
                                        {{ new Date(currentDate.getFullYear(), currentDate.getMonth(), i).toLocaleDateString() }}
                                    </div>
                                    <div class="space-y-1">
                                        <div
                                            v-for="e in events.filter(
                                                (ev) =>
                                                    new Date(ev.start).toDateString() ===
                                                    new Date(currentDate.getFullYear(), currentDate.getMonth(), i).toDateString(),
                                            )"
                                            :key="e.id"
                                            class="cursor-pointer truncate rounded bg-primary/10 px-2 py-1 text-xs"
                                            @click="openEvent(e.id)"
                                        >
                                            {{ e.title }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
