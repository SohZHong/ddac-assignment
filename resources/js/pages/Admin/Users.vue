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
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Check, MoreHorizontal, Trash2, UserCog, X } from 'lucide-vue-next';
import { ref } from 'vue';

interface UserData {
    id: number;
    name: string;
    email: string;
    role: string;
    role_label: string;
    email_verified_at: string | null;
    created_at: string;
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

// Role update functionality
const selectedUser = ref<UserData | null>(null);
const isRoleDialogOpen = ref(false);

const roleUpdateForm = useForm({
    role: '',
});

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
        case 'system_admin':
            return 'destructive';
        case 'health_campaign_manager':
            return 'default';
        case 'healthcare_professional':
            return 'secondary';
        default:
            return 'outline';
    }
};

const canManageUser = (user: UserData) => {
    // Users can't manage themselves
    if (user.id === currentUser.id) return false;

    // System admins can manage everyone
    if (currentUser.role === 'system_admin') return true;

    // Health campaign managers can manage public users and healthcare professionals
    if (currentUser.role === 'health_campaign_manager') {
        return user.role === 'public_user' || user.role === 'healthcare_professional';
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
                                    <th class="px-4 py-3 text-left font-medium">Status</th>
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
                                                {{ user.email_verified_at ? 'Verified' : 'Unverified' }}
                                            </span>
                                        </div>
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
                                                <DropdownMenuSeparator v-if="currentUser.role === 'system_admin'" />
                                                <DropdownMenuItem
                                                    v-if="currentUser.role === 'system_admin'"
                                                    @click="deleteUser(user)"
                                                    class="text-red-600"
                                                >
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
    </AppLayout>
</template>
