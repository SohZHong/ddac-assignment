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
                        <div class="max-h-[44rem] space-y-3 overflow-auto no-scrollbar">
                            <div v-for="log in logs" :key="log.id" class="rounded-md border p-3">
                                <div class="flex items-center justify-between">
                                    <div class="font-medium">{{ log.user || 'Unknown' }} • {{ log.action }}</div>
                                    <div class="text-xs text-muted-foreground">{{ log.created_at }}</div>
                                </div>
                                <div v-if="log.target" class="mt-1 text-xs text-muted-foreground">Target: {{ log.target }}</div>
                                <div v-if="formatLog(log)" class="mt-2 text-sm">{{ formatLog(log) }}</div>
                                <pre v-else-if="log.metadata" class="mt-2 rounded bg-muted p-2 text-xs">{{ log.metadata }}</pre>
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
                        <div class="h-fit space-y-3 overflow-auto no-scrollbar">
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
                                    <label class="text-sm font-medium" for="incident-title">Title (optional)</label>
                                    <input
                                        id="incident-title"
                                        v-model="incidentForm.title"
                                        type="text"
                                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                        placeholder="Short title"
                                    />
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
                                    <div class="font-medium capitalize">
                                        {{ incident.title || 'Untitled incident' }}
                                        <span class="ml-2 text-xs lowercase text-muted-foreground">({{ incident.type.replace('_', ' ') }})</span>
                                    </div>
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
    title?: string | null;
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

const roleLabelFromValue = (value: unknown): string => {
    const v = String(value ?? '');
    switch (v) {
        case '1':
            return 'Public User';
        case '2':
            return 'Healthcare Professional';
        case '3':
            return 'Health Campaign Manager';
        case '4':
            return 'System Administrator';
        default:
            return v || 'Unknown';
    }
};

const formatLog = (log: Log): string => {
    const m = (log.metadata as any) || {};
    const actor = m.actor_name || log.user || 'Someone';

    if (log.action === 'user.role_changed') {
        const target = m.target_name || (log.target ?? 'user');
        const fromRole = m.old_role_label || roleLabelFromValue(m.old_role);
        const toRole = m.new_role_label || roleLabelFromValue(m.new_role);
        return `${actor} changed ${target}'s role from ${fromRole} to ${toRole}`;
    }

    if (log.action === 'user.deleted') {
        const target = m.target_name || m.email || (log.target ?? 'user');
        return `${actor} deleted ${target}`;
    }

    if (log.action === 'approval.approved') {
        const target = m.target_name || (log.target ?? 'user');
        const role = roleLabelFromValue(m.approved_role);
        return `${actor} approved ${target}'s application (role: ${role})`;
    }
    if (log.action === 'approval.rejected') {
        const target = m.target_name || (log.target ?? 'user');
        return `${actor} rejected ${target}'s application${m.reason ? `: ${m.reason}` : ''}`;
    }

    if (log.action === 'incident.created' && (m.type || m.title)) {
        const typeText = (m.type || '').toString().replace('_', ' ');
        return `${actor} reported a new incident (${m.title || 'Untitled incident'} • ${typeText})`;
    }
    if (log.action === 'incident.resolved' && m.incident_id) {
        const typeText = (m.type || '').toString().replace('_', ' ');
        return `${actor} resolved incident ${m.title ? `"${m.title}"` : `#${m.incident_id}`} ${typeText ? `(${typeText})` : ''}`.trim();
    }
    if (log.action === 'incident.reopened' && m.incident_id) {
        const typeText = (m.type || '').toString().replace('_', ' ');
        return `${actor} reopened incident ${m.title ? `"${m.title}"` : `#${m.incident_id}`} ${typeText ? `(${typeText})` : ''}`.trim();
    }
    return '';
};

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
const incidentForm = reactive({ type: 'content_abuse', title: '', description: '' });
const createIncident = () => {
    if (!incidentForm.description) return;
    creating.value = true;
    router.post(route('admin.system.store-incident'), incidentForm, {
        preserveScroll: true,
        onSuccess: () => {
            creating.value = false;
            incidentForm.description = '';
            incidentForm.title = '';
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
