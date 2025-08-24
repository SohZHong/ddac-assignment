<script setup lang="ts">
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type User } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Check, MoreHorizontal, Trash2, UserCog, X } from 'lucide-vue-next';
import { ref } from 'vue';

interface UserData {
    id: number;
    name: string;
    email: string;
    work_email?: string;
    role: string;
    role_label: string;
    email_verified_at: string | null;
    is_verified: boolean;
    verified_at: string | null;
    created_at: string;
    needs_verification: boolean;
    // Professional details
    license_number?: string;
    medical_specialty?: string;
    institution_name?: string;
    organization_name?: string;
    job_title?: string;
    organization_type?: string;
    focus_areas?: string;
    registration_body?: string;
}

interface Props {
    users: {
        data: UserData[];
        links: any[];
        meta: any;
    };
    available_roles: Array<{
        value: string;
        label: string;
    }>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin',
        href: '/admin',
    },
    {
        title: 'Users',
        href: '/admin/users',
    },
];

const page = usePage();
const currentUser = page.props.auth.user as User;
const searchQuery = ref<string>((page.props as any).q ?? '');

// Role update functionality
const selectedUser = ref<UserData | null>(null);
const isRoleDialogOpen = ref(false);
const isVerificationDialogOpen = ref(false);

const roleUpdateForm = useForm({
    role: '',
});

const verificationForm = useForm({});

const openRoleDialog = (user: UserData) => {
    selectedUser.value = user;
    roleUpdateForm.role = user.role;
    isRoleDialogOpen.value = true;
};

const updateUserRole = () => {
    if (!selectedUser.value) return;

    roleUpdateForm.patch(route('admin.users.update-role', selectedUser.value.id), {
        onSuccess: () => {
            isRoleDialogOpen.value = false;
            selectedUser.value = null;
            roleUpdateForm.reset();
        },
    });
};

// Delete user functionality
const deleteUserForm = useForm({});

const deleteUser = (user: UserData) => {
    if (confirm(`Are you sure you want to delete ${user.name}? This action cannot be undone.`)) {
        deleteUserForm.delete(route('admin.users.destroy', user.id));
    }
};

// Verification functionality
const openVerificationDialog = (user: UserData) => {
    selectedUser.value = user;
    isVerificationDialogOpen.value = true;
};

const verifyUser = () => {
    if (!selectedUser.value) return;

    verificationForm.patch(route('admin.users.verify', selectedUser.value.id), {
        onSuccess: () => {
            isVerificationDialogOpen.value = false;
            selectedUser.value = null;
            verificationForm.reset();
        },
    });
};

const unverifyUser = (user: UserData) => {
    if (confirm(`Are you sure you want to revoke ${user.name}'s verification?`)) {
        verificationForm.patch(route('admin.users.unverify', user.id), {
            onSuccess: () => {
                verificationForm.reset();
            },
        });
    }
};

// Helper functions
const getUserInitials = (name: string) => {
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const getRoleBadgeVariant = (role: string) => {
    switch (role) {
        case '4': // System Admin
            return 'destructive';
        case '3': // Health Campaign Manager
            return 'default';
        case '2': // Healthcare Professional
            return 'secondary';
        case '1': // Public User
        default:
            return 'outline';
    }
};

const canManageUser = (user: UserData) => {
    // Users can't manage themselves
    if (user.id === currentUser.id) return false;

    // System admins can manage everyone
    if (currentUser.role === '4') return true;

    // Health campaign managers can manage public users and healthcare professionals
    if (currentUser.role === '3') {
        return user.role === '1' || user.role === '2';
    }

    return false;
};
</script>

<template>
    <Head title="User Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">User Management</h1>
                    <p class="text-muted-foreground">Manage user accounts and roles</p>
                </div>
                <form class="flex items-center gap-2" @submit.prevent="() => router.get(route('admin.users'), { q: searchQuery }, { preserveState: true, preserveScroll: true })">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search name or email..."
                        class="h-9 w-64 rounded-md border border-input bg-background px-3 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    />
                    <Button type="submit" size="sm">Search</Button>
                </form>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>All Users</CardTitle>
                    <CardDescription> A list of all users in the system. You can update roles and manage user accounts. </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="px-4 py-3 text-left font-medium">User</th>
                                    <th class="px-4 py-3 text-left font-medium">Role</th>
                                    <th class="px-4 py-3 text-left font-medium">Email Status</th>
                                    <th class="px-4 py-3 text-left font-medium">Verification</th>
                                    <th class="px-4 py-3 text-left font-medium">Joined</th>
                                    <th class="px-4 py-3 text-right font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="user in props.users.data" :key="user.id" class="border-b hover:bg-muted/50">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <Avatar class="h-8 w-8">
                                                <AvatarFallback class="text-xs">
                                                    {{ getUserInitials(user.name) }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div>
                                                <div class="font-medium">{{ user.name }}</div>
                                                <div class="text-sm text-muted-foreground">{{ user.email }}</div>
                                                <div v-if="user.work_email" class="text-xs text-muted-foreground">Work: {{ user.work_email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <Badge :variant="getRoleBadgeVariant(user.role)">
                                            {{ user.role_label }}
                                        </Badge>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <Check v-if="user.email_verified_at" class="h-4 w-4 text-green-600" />
                                            <X v-else class="h-4 w-4 text-red-600" />
                                            <span class="text-sm">
                                                {{ user.email_verified_at ? 'Email Verified' : 'Email Pending' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div v-if="user.needs_verification" class="flex items-center gap-2">
                                            <Check v-if="user.is_verified" class="h-4 w-4 text-green-600" />
                                            <X v-else class="h-4 w-4 text-yellow-600" />
                                            <span class="text-sm">
                                                {{ user.is_verified ? 'Admin Verified' : 'Pending Verification' }}
                                            </span>
                                            <div v-if="user.verified_at" class="text-xs text-muted-foreground">
                                                {{ user.verified_at }}
                                            </div>
                                        </div>
                                        <div v-else class="text-sm text-muted-foreground">No verification required</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">
                                        {{ user.created_at }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <DropdownMenu v-if="canManageUser(user)">
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" size="sm">
                                                    <MoreHorizontal class="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem @click="openRoleDialog(user)">
                                                    <UserCog class="mr-2 h-4 w-4" />
                                                    Change Role
                                                </DropdownMenuItem>

                                                <!-- Verification actions for professionals -->
                                                <template v-if="user.needs_verification && currentUser.role === '4'">
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem
                                                        v-if="!user.is_verified"
                                                        @click="openVerificationDialog(user)"
                                                        class="text-green-600"
                                                    >
                                                        <Check class="mr-2 h-4 w-4" />
                                                        Verify Professional
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem v-else @click="unverifyUser(user)" class="text-yellow-600">
                                                        <X class="mr-2 h-4 w-4" />
                                                        Revoke Verification
                                                    </DropdownMenuItem>
                                                </template>

                                                <DropdownMenuSeparator v-if="currentUser.role === '4'" />
                                                <DropdownMenuItem v-if="currentUser.role === '4'" @click="deleteUser(user)" class="text-red-600">
                                                    <Trash2 class="mr-2 h-4 w-4" />
                                                    Delete User
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                        <span v-else class="text-sm text-muted-foreground">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination would go here if needed -->
                    <div v-if="props.users.meta" class="mt-4 flex items-center justify-between">
                        <div class="text-sm text-muted-foreground">
                            Showing {{ props.users.meta.from || 0 }} to {{ props.users.meta.to || 0 }} of {{ props.users.meta.total || 0 }} users
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Role Change Dialog -->
        <Dialog v-model:open="isRoleDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Change User Role</DialogTitle>
                    <DialogDescription> Update the role for {{ selectedUser?.name }}. This will change their access permissions. </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="role">New Role</Label>
                        <select
                            id="role"
                            v-model="roleUpdateForm.role"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <option v-for="role in props.available_roles" :key="role.value" :value="role.value">
                                {{ role.label }}
                            </option>
                        </select>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="isRoleDialogOpen = false">Cancel</Button>
                    <Button @click="updateUserRole" :disabled="roleUpdateForm.processing">
                        {{ roleUpdateForm.processing ? 'Updating...' : 'Update Role' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Professional Verification Dialog -->
        <Dialog v-model:open="isVerificationDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Verify Professional Credentials</DialogTitle>
                    <DialogDescription> Review and verify the professional credentials for {{ selectedUser?.name }}. </DialogDescription>
                </DialogHeader>

                <div v-if="selectedUser" class="grid gap-4 py-4">
                    <!-- Professional Details -->
                    <div class="space-y-3">
                        <h4 class="font-medium">Professional Information</h4>

                        <div v-if="selectedUser.role === '2'" class="space-y-2">
                            <div class="text-sm"><strong>License Number:</strong> {{ selectedUser.license_number || 'Not provided' }}</div>
                            <div class="text-sm"><strong>Medical Specialty:</strong> {{ selectedUser.medical_specialty || 'Not provided' }}</div>
                            <div class="text-sm"><strong>Institution:</strong> {{ selectedUser.institution_name || 'Not provided' }}</div>
                            <div class="text-sm">
                                <strong>Professional Registration Body:</strong> {{ selectedUser.registration_body || 'Not provided' }}
                            </div>
                            <div class="text-sm"><strong>Work email:</strong> {{ selectedUser.work_email || 'Not provided' }}</div>
                        </div>

                        <div v-if="selectedUser.role === '3'" class="space-y-2">
                            <div class="text-sm"><strong>Organization:</strong> {{ selectedUser.organization_name || 'Not provided' }}</div>
                            <div class="text-sm"><strong>Job Title:</strong> {{ selectedUser.job_title || 'Not provided' }}</div>
                            <div class="text-sm"><strong>Organization Type:</strong> {{ selectedUser.organization_type || 'Not provided' }}</div>
                            <div class="text-sm"><strong>Primary Focus Area:</strong> {{ selectedUser.focus_areas || 'Not provided' }}</div>
                            <div class="text-sm"><strong>Work email:</strong> {{ selectedUser.work_email || 'Not provided' }}</div>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="isVerificationDialogOpen = false">Cancel</Button>
                    <Button @click="verifyUser" :disabled="verificationForm.processing" class="bg-green-600 hover:bg-green-700">
                        {{ verificationForm.processing ? 'Verifying...' : 'Verify Professional' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
