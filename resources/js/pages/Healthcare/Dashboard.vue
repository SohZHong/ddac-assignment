<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type User } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Heart, Calendar, FileText, Users, TrendingUp, Clock } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Healthcare',
        href: '/healthcare',
    },
];

const page = usePage();
const user = page.props.auth.user as User;
</script>

<template>
    <Head title="Healthcare Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Healthcare Dashboard</h1>
                    <p class="text-muted-foreground">Manage patient care and health services</p>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <!-- Patient Management -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Patient Management</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">247</div>
                        <p class="text-xs text-muted-foreground">
                            +12% from last month
                        </p>
                        <Button variant="outline" class="w-full mt-4" disabled>
                            View Patients
                        </Button>
                    </CardContent>
                </Card>

                <!-- Appointments -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Today's Appointments</CardTitle>
                        <Calendar class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">8</div>
                        <p class="text-xs text-muted-foreground">
                            2 pending confirmations
                        </p>
                        <Button variant="outline" class="w-full mt-4" disabled>
                            View Schedule
                        </Button>
                    </CardContent>
                </Card>

                <!-- Health Records -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Health Records</CardTitle>
                        <FileText class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">1,432</div>
                        <p class="text-xs text-muted-foreground">
                            Digital health records
                        </p>
                        <Button variant="outline" class="w-full mt-4" disabled>
                            Access Records
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <!-- Quick Actions -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Heart class="h-5 w-5 text-red-500" />
                        Quick Actions
                    </CardTitle>
                    <CardDescription>
                        Common healthcare tasks and patient management
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <Button variant="outline" class="justify-start h-auto py-4" disabled>
                            <div class="flex flex-col items-start gap-1">
                                <div class="flex items-center gap-2">
                                    <Users class="h-4 w-4" />
                                    <span class="font-medium">New Patient</span>
                                </div>
                                <span class="text-xs text-muted-foreground">Register new patient</span>
                            </div>
                        </Button>
                        <Button variant="outline" class="justify-start h-auto py-4" disabled>
                            <div class="flex flex-col items-start gap-1">
                                <div class="flex items-center gap-2">
                                    <Calendar class="h-4 w-4" />
                                    <span class="font-medium">Schedule</span>
                                </div>
                                <span class="text-xs text-muted-foreground">Book appointment</span>
                            </div>
                        </Button>
                        <Button variant="outline" class="justify-start h-auto py-4" disabled>
                            <div class="flex flex-col items-start gap-1">
                                <div class="flex items-center gap-2">
                                    <FileText class="h-4 w-4" />
                                    <span class="font-medium">Prescription</span>
                                </div>
                                <span class="text-xs text-muted-foreground">Create prescription</span>
                            </div>
                        </Button>
                        <Button variant="outline" class="justify-start h-auto py-4" disabled>
                            <div class="flex flex-col items-start gap-1">
                                <div class="flex items-center gap-2">
                                    <TrendingUp class="h-4 w-4" />
                                    <span class="font-medium">Reports</span>
                                </div>
                                <span class="text-xs text-muted-foreground">View analytics</span>
                            </div>
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Recent Activity -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Clock class="h-5 w-5" />
                        Recent Activity
                    </CardTitle>
                    <CardDescription>
                        Latest patient interactions and healthcare events
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div class="flex items-center gap-4 p-4 border rounded-lg">
                            <div class="h-2 w-2 bg-green-500 rounded-full"></div>
                            <div class="flex-1">
                                <p class="font-medium">Patient consultation completed</p>
                                <p class="text-sm text-muted-foreground">John Doe - 2 hours ago</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 p-4 border rounded-lg">
                            <div class="h-2 w-2 bg-blue-500 rounded-full"></div>
                            <div class="flex-1">
                                <p class="font-medium">New appointment scheduled</p>
                                <p class="text-sm text-muted-foreground">Jane Smith - 4 hours ago</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 p-4 border rounded-lg">
                            <div class="h-2 w-2 bg-orange-500 rounded-full"></div>
                            <div class="flex-1">
                                <p class="font-medium">Lab results uploaded</p>
                                <p class="text-sm text-muted-foreground">Michael Johnson - 6 hours ago</p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Professional Info -->
            <div class="mt-auto">
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center gap-4">
                            <div class="flex-1">
                                <p class="text-sm text-muted-foreground">
                                    Healthcare Professional: <span class="font-medium text-foreground">{{ user.name }}</span>
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
