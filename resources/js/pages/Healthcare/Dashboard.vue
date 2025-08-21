<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type User } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Calendar, FileText, Heart, Timer, TrendingUp, Users } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Healthcare',
        href: '/healthcare',
    },
];

const page = usePage();
const user = page.props.auth.user as User;

const props = defineProps<{
    profile: {
        id: string;
        name: string;
        email: string;
    };
    stats: {
        todayBookingsCount: number;
        totalBookings: number;
        confirmedPatients: number;
        pendingBookings: number;
        totalSchedules: number;
        totalHours: number;
        quizResponses: number;
    };
}>();
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
                        <div class="text-2xl font-bold">{{ props.stats.confirmedPatients }}</div>
                        <p class="text-xs text-muted-foreground">Confirmed Patients</p>
                        <Button variant="outline" class="mt-4 w-full">
                            <Link :href="route('healthcare.patient.index')"> View Patients</Link>
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
                        <div class="text-2xl font-bold">{{ props.stats.todayBookingsCount }}</div>
                        <p class="text-xs text-muted-foreground">{{ props.stats.pendingBookings }} pending confirmations</p>
                        <Button variant="outline" class="mt-4 w-full">
                            <Link :href="route('healthcare.appointment.index')"> View Appointments </Link>
                        </Button>
                    </CardContent>
                </Card>

                <!-- Availability Slots -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Availability Slots</CardTitle>
                        <Timer class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ props.stats.totalSchedules }}</div>
                        <p class="text-xs text-muted-foreground">{{ Number(props.stats.totalHours).toPrecision(1) }} hours available</p>
                        <Button variant="outline" class="mt-4 w-full">
                            <Link :href="route('healthcare.schedule.index')"> Manage Schedule </Link>
                        </Button>
                    </CardContent>
                </Card>

                <!-- Health Records -->
                <!-- <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Assessment Responses</CardTitle>
                        <FileText class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">1,432</div>
                        <p class="text-xs text-muted-foreground">Digital health records</p>
                        <Button variant="outline" class="mt-4 w-full" disabled> Access Records </Button>
                    </CardContent>
                </Card> -->
            </div>

            <!-- Quick Actions -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Heart class="h-5 w-5 text-red-500" />
                        Quick Actions
                    </CardTitle>
                    <CardDescription> Common healthcare tasks and patient management </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <Button variant="outline" class="h-auto justify-start py-4">
                            <Link :href="route('healthcare.quizzes.index')">
                                <div class="flex flex-col items-start gap-1">
                                    <div class="flex items-center gap-2">
                                        <Users class="h-4 w-4" />
                                        <span class="font-medium"> Quizzes </span>
                                    </div>
                                    <span class="text-xs text-muted-foreground"> Create Assessment Quizzes</span>
                                </div>
                            </Link>
                        </Button>
                        <Button variant="outline" class="h-auto justify-start py-4">
                            <Link :href="route('healthcare.schedule.index')">
                                <div class="flex flex-col items-start gap-1">
                                    <div class="flex items-center gap-2">
                                        <Calendar class="h-4 w-4" />
                                        <span class="font-medium">Schedule</span>
                                    </div>
                                    <span class="text-xs text-muted-foreground">Create Availability Slots</span>
                                </div>
                            </Link>
                        </Button>
                        <Button variant="outline" class="h-auto justify-start py-4">
                            <Link :href="route('healthcare.blog.create')">
                                <div class="flex flex-col items-start gap-1">
                                    <div class="flex items-center gap-2">
                                        <FileText class="h-4 w-4" />
                                        <span class="font-medium">Educational Blogs</span>
                                    </div>
                                    <span class="text-xs text-muted-foreground">Write a Blog</span>
                                </div>
                            </Link>
                        </Button>
                        <Button variant="outline" class="h-auto justify-start py-4" disabled>
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
