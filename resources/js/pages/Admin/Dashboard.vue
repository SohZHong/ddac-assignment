<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type User } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Users, Shield, Activity, Settings } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin',
        href: '/admin',
    },
];

const page = usePage();
const user = page.props.auth.user as User;
const pendingApprovalsCount = computed(() => Number((page.props as any).pendingApprovalsCount || 0))
</script>

<template>
    <Head title="Admin Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Admin Dashboard</h1>
                    <p class="text-muted-foreground">Manage users, roles, and system settings</p>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <!-- User Management Card -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">User Management</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <CardDescription class="mb-4"> Manage user accounts, verify professionals, and view user activity </CardDescription>
                        <Button asChild class="w-full">
                            <a href="/admin/users">Manage Users</a>
                        </Button>
                    </CardContent>
                </Card>

                <!-- Approvals Card -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Professional Approvals</CardTitle>
                        <div class="relative">
                            <Shield class="h-4 w-4 text-muted-foreground" />
                            <span v-if="pendingApprovalsCount > 0" class="absolute -right-2 -top-2 inline-flex items-center justify-center rounded-full bg-red-600 px-1.5 text-[10px] font-medium text-white">
                                {{ pendingApprovalsCount }}
                            </span>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <CardDescription class="mb-4">
                            Review and manage professional account applications
                        </CardDescription>
                        <Button asChild class="w-full">
                            <a href="/admin/approvals">Manage Approvals</a>
                        </Button>
                    </CardContent>
                </Card>

                <!-- System Activity Card -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">System Activity</CardTitle>
                        <Activity class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <CardDescription class="mb-4"> Monitor system usage, logs, and administrative activities </CardDescription>
                        <Button variant="outline" class="w-full" disabled> Coming Soon </Button>
                    </CardContent>
                </Card>

                <!-- System Settings Card -->
                <Card class="md:col-span-2 lg:col-span-3">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Settings class="h-5 w-5" />
                            Quick Actions
                        </CardTitle>
                        <CardDescription> Common administrative tasks and system management </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <Button variant="outline" class="justify-start" asChild>
                                <a href="/admin/users">
                                    <Users class="mr-2 h-4 w-4" />
                                    View All Users
                                </a>
                            </Button>
                            <Button variant="outline" class="justify-start" disabled>
                                <Shield class="mr-2 h-4 w-4" />
                                Export Data
                            </Button>
                            <Button variant="outline" class="justify-start" disabled>
                                <Activity class="mr-2 h-4 w-4" />
                                System Reports
                            </Button>
                            <Button variant="outline" class="justify-start" disabled>
                                <Settings class="mr-2 h-4 w-4" />
                                System Config
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Admin Info -->
            <div class="mt-auto">
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center gap-4">
                            <div class="flex-1">
                                <p class="text-sm text-muted-foreground">
                                    Logged in as: <span class="font-medium text-foreground">{{ user.name }}</span>
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    Role: <span class="font-medium text-foreground">{{ user.role_label }}</span>
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
