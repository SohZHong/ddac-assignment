<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="System Activity" />
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">System Activity</h1>
                    <p class="text-muted-foreground">Monitor admin actions, incidents, and public content</p>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <!-- Admin Logs -->
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Admin Action Logs</CardTitle>
                        <CardDescription>Recent administrative actions</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="max-h-[39rem] space-y-3 overflow-auto no-scrollbar">
                            <div v-for="log in logs" :key="log.id" class="rounded-md border p-3">
                                <div class="flex items-center justify-between">
                                    <div class="font-medium">{{ log.user || 'Unknown' }} • {{ log.action }}</div>
                                    <div class="text-xs text-muted-foreground">{{ log.created_at }}</div>
                                </div>
                                <div v-if="log.target" class="mt-1 text-xs text-muted-foreground">Target: {{ log.target }}</div>
                                <pre v-if="log.metadata" class="mt-2 rounded bg-muted p-2 text-xs">{{ log.metadata }}</pre>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Incidents -->
                <Card>
                    <CardHeader>
                        <CardTitle>Incident Reports</CardTitle>
                        <CardDescription>Open and recent reports</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="h-60 space-y-3 overflow-auto no-scrollbar">
                            <form class="mb-4 grid gap-2" @submit.prevent="createIncident">
                                <div class="grid gap-2">
                                    <label class="text-sm font-medium" for="incident-type">New Incident Type</label>
                                    <select
                                        id="incident-type"
                                        v-model="incidentForm.type"
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    >
                                        <option value="content_abuse">Content Abuse</option>
                                        <option value="security">Security</option>
                                        <option value="feedback">Feedback</option>
                                    </select>
                                </div>
                                <div class="grid gap-2">
                                    <label class="text-sm font-medium" for="incident-desc">Description</label>
                                    <textarea
                                        id="incident-desc"
                                        v-model="incidentForm.description"
                                        class="min-h-20 rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    ></textarea>
                                </div>
                                <div>
                                    <Button size="sm" :disabled="creating">{{ creating ? 'Reporting...' : 'Report Incident' }}</Button>
                                </div>
                            </form>
                        </div>

                        <div class="max-h-96 space-y-3 overflow-auto no-scrollbar">
                            <div v-for="incident in incidents" :key="incident.id" class="rounded-md border p-3">
                                <div class="flex items-center justify-between">
                                    <div class="font-medium capitalize">{{ incident.type.replace('_', ' ') }}</div>
                                    <Badge
                                        :variant="incident.status === 'resolved' ? 'secondary' : 'default'"
                                        class="cursor-pointer"
                                        @click="toggleIncident(incident)"
                                        >{{ incident.status }}</Badge
                                    >
                                </div>
                                <p class="mt-1 text-sm">{{ incident.description }}</p>
                                <div class="mt-2 flex gap-2">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        class="cursor-pointer"
                                        :disabled="incident.status === 'resolved'"
                                        @click="confirmResolve(incident.id)"
                                        >Resolve</Button
                                    >
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Public Content -->
            <Card>
                <CardHeader>
                    <CardTitle>Public Content</CardTitle>
                    <CardDescription>Latest blog posts</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="py-2 text-left">Title</th>
                                    <th class="py-2 text-left">Status</th>
                                    <th class="py-2 text-left">Published</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="c in content" :key="c.id" class="border-b">
                                    <td class="py-2">{{ c.title }}</td>
                                    <td class="py-2">
                                        <Badge :variant="c.status === 1 ? 'default' : 'outline'">{{ c.status === 1 ? 'Published' : 'Draft' }}</Badge>
                                    </td>
                                    <td class="py-2 text-sm text-muted-foreground">{{ c.published_at || '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

interface Log {
    id: number;
    user: string;
    action: string;
    target?: string;
    metadata?: any;
    created_at: string;
}
interface Incident {
    id: number;
    type: string;
    description: string;
    status: string;
}
interface Content {
    id: number;
    title: string;
    status: number;
    published_at: string | null;
}

const props = defineProps<{ logs: Log[]; incidents: Incident[]; content: Content[] }>();

const breadcrumbs = [
    { title: 'Admin', href: '/admin' },
    { title: 'System Activity', href: '/admin/system-activity' },
];

const page = usePage();
const logs = computed<Log[]>(() => (page.props as any).logs ?? props.logs);
const incidents = computed<Incident[]>(() => (page.props as any).incidents ?? props.incidents);
const content = computed<Content[]>(() => (page.props as any).content ?? props.content);

const resolve = (id: number) => {
    router.post(
        route('admin.system.resolve-incident', id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => router.reload({ only: ['incidents', 'logs'] }),
        },
    );
};

const creating = ref(false);
const incidentForm = reactive({ type: 'content_abuse', description: '' });
const createIncident = () => {
    if (!incidentForm.description) return;
    creating.value = true;
    router.post(route('admin.system.store-incident'), incidentForm, {
        preserveScroll: true,
        onSuccess: () => {
            creating.value = false;
            incidentForm.description = '';
            router.reload({ only: ['incidents', 'logs'] });
        },
        onFinish: () => (creating.value = false),
    });
};

const confirmResolve = (id: number) => {
    if (confirm('Resolve this incident?')) resolve(id);
};

const toggleIncident = (incident: Incident) => {
    if (incident.status === 'resolved') {
        if (confirm('Reopen this incident?')) {
            router.post(
                route('admin.system.reopen-incident', incident.id),
                {},
                {
                    preserveScroll: true,
                    onSuccess: () => router.reload({ only: ['incidents', 'logs'] }),
                },
            );
        }
    } else {
        confirmResolve(incident.id);
    }
};
</script>
