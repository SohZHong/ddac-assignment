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
                        <form class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-3" @submit.prevent="applyFilters">
                            <div class="grid gap-1">
                                <label class="text-sm font-medium">Admin</label>
                                <select v-model="filters.user_id" class="h-9 rounded-md border border-input bg-background px-2 text-sm">
                                    <option value="">All</option>
                                    <option v-for="a in admins" :key="a.id" :value="a.id">{{ a.name }}</option>
                                </select>
                            </div>
                            <div class="grid gap-1">
                                <label class="text-sm font-medium">Action</label>
                                <select v-model="filters.action" class="h-9 rounded-md border border-input bg-background px-2 text-sm">
                                    <option value="">All</option>
                                    <option v-for="a in actions" :key="a" :value="a">{{ a }}</option>
                                </select>
                            </div>
                            <div class="self-end">
                                <Button size="sm" class="cursor-pointer">Apply</Button>
                                <Button size="sm" variant="ghost" type="button" class="ml-2 cursor-pointer" @click="resetFilters">Reset</Button>
                            </div>
                        </form>
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
                        <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div class="inline-flex rounded-md border self-start">
                                <button
                                    class="px-3 py-1 text-sm"
                                    :class="incidentTab === 'open' ? 'bg-muted font-medium' : ''"
                                    @click="incidentTab = 'open'"
                                    type="button"
                                >
                                    Open
                                </button>
                                <button
                                    class="px-3 py-1 text-sm"
                                    :class="incidentTab === 'resolved' ? 'bg-muted font-medium' : ''"
                                    @click="incidentTab = 'resolved'"
                                    type="button"
                                >
                                    Resolved
                                </button>
                            </div>
                            <div class="flex flex-1 flex-col items-start gap-2 md:flex-row md:items-center md:justify-end">
                                <Button size="sm" class="cursor-pointer" @click="showIncidentModal = true">Report Incident</Button>
                            </div>
                        </div>
                        <div class="flex flex-col items-start gap-2 mb-4">
                                <div class="flex items-center gap-2">
                                    <label class="text-sm text-muted-foreground">Type</label>
                                    <select v-model="incidentFilters.type" class="h-9 rounded-md border border-input bg-background px-2 text-sm">
                                        <option value="">All</option>
                                        <option value="content_abuse">Content Abuse</option>
                                        <option value="security">Security</option>
                                        <option value="feedback">Feedback</option>
                                    </select>
                                </div>
                                <div class="flex items-center gap-2">
                                    <label class="text-sm text-muted-foreground">Search</label>
                                    <input v-model.trim="incidentFilters.q" type="text" placeholder="Title or description" class="h-9 w-56 rounded-md border border-input bg-background px-2 text-sm" />
                                </div>
                        </div>

                        <div class="max-h-96 space-y-3 overflow-auto no-scrollbar" v-if="incidentTab === 'open'">
                            <div v-for="incident in filteredOpenIncidents" :key="incident.id" class="rounded-md border p-3">
                                <div class="flex items-center justify-between">
                                    <div class="font-medium capitalize">
                                        {{ incident.title || 'Untitled incident' }}
                                        <span class="ml-2 text-xs lowercase text-muted-foreground">({{ incident.type.replace('_', ' ') }})</span>
                                    </div>
                                    <Badge
                                        :variant="'default'"
                                        class="cursor-pointer"
                                        @click="confirmResolve(incident.id)"
                                        >resolve</Badge
                                    >
                                </div>
                                <p class="mt-1 text-sm">{{ incident.description }}</p>
                            </div>
                        </div>

                        <div class="max-h-96 space-y-3 overflow-auto no-scrollbar" v-else>
                            <div v-for="incident in filteredResolvedIncidents" :key="incident.id" class="rounded-md border p-3">
                                <div class="flex items-center justify-between">
                                    <div class="font-medium capitalize">
                                        {{ incident.title || 'Untitled incident' }}
                                        <span class="ml-2 text-xs lowercase text-muted-foreground">({{ incident.type.replace('_', ' ') }})</span>
                                    </div>
                                    <Badge :variant="'secondary'">resolved</Badge>
                                </div>
                                <p class="mt-1 text-sm">{{ incident.description }}</p>
                                <div class="mt-2">
                                    <Button size="sm" variant="outline" class="cursor-pointer" @click="toggleIncident(incident)">Reopen</Button>
                                </div>
                            </div>
                        </div>

                        <Dialog :open="showIncidentModal" @update:open="(v:boolean)=> showIncidentModal = v">
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Report Incident</DialogTitle>
                                    <DialogDescription>Create a new incident report</DialogDescription>
                                </DialogHeader>
                                <form class="grid gap-2" @submit.prevent="createIncident">
                                    <div class="grid gap-2">
                                        <label class="text-sm font-medium" for="incident-type">Type</label>
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
                                    <DialogFooter>
                                        <Button size="sm" :disabled="creating" class="cursor-pointer">{{ creating ? 'Reporting...' : 'Report' }}</Button>
                                        <DialogClose asChild>
                                            <Button size="sm" type="button" variant="ghost" class="cursor-pointer">Cancel</Button>
                                        </DialogClose>
                                    </DialogFooter>
                                </form>
                            </DialogContent>
                        </Dialog>
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
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogClose } from '@/components/ui/dialog';
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

const props = defineProps<{ logs: Log[]; incidents: Incident[]; content: Content[]; admins: { id: number; name: string }[]; actions: string[]; filters?: { user_id?: string | number | null; action?: string | null } }>();

const breadcrumbs = [
    { title: 'Admin', href: '/admin' },
    { title: 'System Activity', href: '/admin/system-activity' },
];

const page = usePage();
const logs = computed<Log[]>(() => (page.props as any).logs ?? props.logs);
const incidents = computed<Incident[]>(() => (page.props as any).incidents ?? props.incidents);
const content = computed<Content[]>(() => (page.props as any).content ?? props.content);
const admins = computed<{ id: number; name: string }[]>(() => (page.props as any).admins ?? props.admins ?? []);
const actions = computed<string[]>(() => (page.props as any).actions ?? props.actions ?? []);

const filters = reactive<{ user_id: string | number | ''; action: string | '' }>({
    user_id: ((page.props as any).filters?.user_id ?? props.filters?.user_id ?? '') as any,
    action: ((page.props as any).filters?.action ?? props.filters?.action ?? '') as any,
});

const applyFilters = () => {
    router.get(route('admin.system.index'), { user_id: filters.user_id || undefined, action: filters.action || undefined }, { preserveScroll: true, preserveState: true });
};
const resetFilters = () => {
    filters.user_id = '';
    filters.action = '';
    applyFilters();
};

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
const showIncidentModal = ref(false);
const incidentTab = ref<'open' | 'resolved'>('open');
const incidentForm = reactive({ type: 'content_abuse', title: '', description: '' });
const openIncidents = computed(() => incidents.value.filter((i) => i.status !== 'resolved'));
const resolvedIncidents = computed(() => incidents.value.filter((i) => i.status === 'resolved'));

const incidentFilters = reactive<{ type: string; q: string }>({ type: '', q: '' });
const matchesIncidentFilters = (i: Incident) => {
    if (incidentFilters.type && i.type !== incidentFilters.type) return false;
    if (incidentFilters.q) {
        const q = incidentFilters.q.toLowerCase();
        const title = (i.title || '').toLowerCase();
        const desc = (i as any).description?.toLowerCase?.() || '';
        if (!title.includes(q) && !desc.includes(q)) return false;
    }
    return true;
};
const filteredOpenIncidents = computed(() => openIncidents.value.filter(matchesIncidentFilters));
const filteredResolvedIncidents = computed(() => resolvedIncidents.value.filter(matchesIncidentFilters));
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
            showIncidentModal.value = false;
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
