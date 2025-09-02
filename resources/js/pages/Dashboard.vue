<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type User } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Calendar, CheckCircle, Clock, FileText, TrendingUp } from 'lucide-vue-next';

interface Stats {
    totalBookings: number;
    confirmedBookings: number;
    pendingBookings: number;
    completedBookings: number;
    totalReports: number;
    quizResponsesCount: number;
    healthScore: number;
    latestRiskLevel: number | null;
    lastAssessmentDate: string | null;
    assessmentCount: number;
}

interface Appointment {
    id: number;
    start_time: string;
    end_time: string;
    status: string;
    healthcare: {
        name: string;
        email: string;
    };
}

interface Report {
    id: number;
    title: string;
    created_at: string;
    doctor_name: string;
    file_path: string;
}

interface BlogPost {
    id: number;
    title: string;
    excerpt: string;
    published_at: string;
    author_name: string;
    reading_time: number;
}

const props = defineProps<{
    user: User;
    stats: Stats;
    upcomingAppointments: Appointment[];
    recentReports: Report[];
    recentBlogs: BlogPost[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getHealthScoreProgressColor = (score: number) => {
    if (score >= 80) return 'bg-green-500';
    if (score >= 60) return 'bg-yellow-500';
    return 'bg-red-500';
};

const getRiskLevelText = (riskLevel: number | null) => {
    if (riskLevel === null) return 'No assessment yet';
    switch (riskLevel) {
        case 0:
            return 'Low Risk';
        case 1:
            return 'Medium Risk';
        case 2:
            return 'High Risk';
        default:
            return 'Unknown';
    }
};

const getRiskLevelColor = (riskLevel: number | null) => {
    if (riskLevel === null) return 'text-gray-500';
    switch (riskLevel) {
        case 0:
            return 'text-green-600';
        case 1:
            return 'text-yellow-600';
        case 2:
            return 'text-red-600';
        default:
            return 'text-gray-500';
    }
};

const getHealthScoreDescription = (score: number, assessmentCount: number) => {
    if (assessmentCount === 0) {
        return 'No medical assessments yet';
    }

    const baseDescription = assessmentCount === 1 ? 'Based on 1 medical assessment' : `Based on ${assessmentCount} medical assessments`;

    if (score >= 80) return `${baseDescription} - Excellent health status`;
    if (score >= 60) return `${baseDescription} - Good health status`;
    if (score >= 40) return `${baseDescription} - Moderate health concerns`;
    return `${baseDescription} - Requires attention`;
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Welcome back, {{ props.user.name }}!</h1>
                    <p class="text-muted-foreground">Here's your health journey overview</p>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                <div class="card mb-6">
                    <div class="p-6">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-white">Health Score</h3>
                            <div class="text-right">
                                <div class="text-2xl font-bold text-white">{{ stats.healthScore }}/100</div>
                                <div class="text-sm" :class="getRiskLevelColor(stats.latestRiskLevel)">
                                    {{ getRiskLevelText(stats.latestRiskLevel) }}
                                </div>
                            </div>
                        </div>
                        <div class="mb-3 h-3 w-full rounded-full bg-gray-200">
                            <div
                                class="h-3 rounded-full transition-all duration-300"
                                :class="getHealthScoreProgressColor(stats.healthScore)"
                                :style="{ width: `${stats.healthScore}%` }"
                            ></div>
                        </div>
                        <p class="mb-2 text-sm text-gray-600">
                            {{ getHealthScoreDescription(stats.healthScore, stats.assessmentCount) }}
                        </p>
                        <div class="flex justify-between text-xs text-gray-500">
                            <span>{{ stats.assessmentCount }} assessments</span>
                            <span v-if="stats.lastAssessmentDate"> Last: {{ new Date(stats.lastAssessmentDate).toLocaleDateString() }} </span>
                            <span v-else>No assessments yet</span>
                        </div>
                    </div>
                </div>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Appointments</CardTitle>
                        <Calendar class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ props.stats.totalBookings }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ props.stats.confirmedBookings }} confirmed, {{ props.stats.pendingBookings }} pending
                        </p>
                        <Button variant="outline" class="mt-4 w-full" size="sm">
                            <Link :href="route('booking.index')">View Appointments</Link>
                        </Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Completed Sessions</CardTitle>
                        <CheckCircle class="h-4 w-4 text-green-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">{{ props.stats.completedBookings }}</div>
                        <p class="text-xs text-muted-foreground">Healthcare consultations completed</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Health Reports</CardTitle>
                        <FileText class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ props.stats.totalReports }}</div>
                        <p class="text-xs text-muted-foreground">{{ props.stats.quizResponsesCount }} quiz responses</p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Calendar class="h-5 w-5 text-blue-600" />
                            Upcoming Appointments
                        </CardTitle>
                        <CardDescription>Your next scheduled healthcare consultations</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="props.upcomingAppointments.length === 0" class="py-8 text-center text-muted-foreground">
                            <Calendar class="mx-auto mb-4 h-12 w-12 opacity-50" />
                            <p>No upcoming appointments</p>
                            <Button variant="outline" class="mt-4">
                                <Link :href="route('booking.create')">Book Appointment</Link>
                            </Button>
                        </div>
                        <div v-else class="space-y-4">
                            <div
                                v-for="appointment in props.upcomingAppointments"
                                :key="appointment.id"
                                class="flex items-center justify-between rounded-lg border p-4 transition-colors hover:bg-gray-800"
                            >
                                <div class="flex-1">
                                    <div class="font-medium">{{ appointment.healthcare.name }}</div>
                                    <div class="text-sm text-muted-foreground">
                                        {{ formatDate(appointment.start_time) }}
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">
                                        Confirmed
                                    </span>
                                </div>
                            </div>
                            <Button variant="outline" class="w-full">
                                <Link :href="route('booking.index')">View All Appointments</Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <FileText class="h-5 w-5 text-green-600" />
                            Recent Health Reports
                        </CardTitle>
                        <CardDescription>Your latest consultation reports and documents</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="props.recentReports.length === 0" class="py-8 text-center text-muted-foreground">
                            <FileText class="mx-auto mb-4 h-12 w-12 opacity-50" />
                            <p>No reports available yet</p>
                            <p class="text-sm">Reports will appear here after your consultations</p>
                        </div>
                        <div v-else class="space-y-4">
                            <div
                                v-for="report in props.recentReports"
                                :key="report.id"
                                class="flex items-center justify-between rounded-lg border p-4 transition-colors hover:bg-gray-50"
                            >
                                <div class="flex-1">
                                    <div class="font-medium">{{ report.title }}</div>
                                    <div class="text-sm text-muted-foreground">
                                        By Dr. {{ report.doctor_name }} • {{ formatDate(report.created_at) }}
                                    </div>
                                </div>
                                <Button variant="outline" size="sm">
                                    <a :href="report.file_path" target="_blank" class="flex items-center gap-1">
                                        <FileText class="h-4 w-4" />
                                        View
                                    </a>
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <TrendingUp class="h-5 w-5 text-purple-600" />
                        Latest Health Insights
                    </CardTitle>
                    <CardDescription>Stay informed with the latest health tips and articles</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="props.recentBlogs.length === 0" class="py-8 text-center text-muted-foreground">
                        <TrendingUp class="mx-auto mb-4 h-12 w-12 opacity-50" />
                        <p>No articles available</p>
                    </div>
                    <div v-else class="grid gap-4 md:grid-cols-3">
                        <div v-for="blog in props.recentBlogs" :key="blog.id" class="rounded-lg border p-4 transition-colors hover:bg-gray-50">
                            <h4 class="mb-2 line-clamp-2 font-medium">{{ blog.title }}</h4>
                            <p class="mb-3 line-clamp-3 text-sm text-muted-foreground">{{ blog.excerpt }}</p>
                            <div class="flex items-center justify-between text-xs text-muted-foreground">
                                <span>{{ blog.author_name }}</span>
                                <span class="flex items-center gap-1">
                                    <Clock class="h-3 w-3" />
                                    {{ blog.reading_time }}min read
                                </span>
                            </div>
                        </div>
                    </div>
                    <Button variant="outline" class="mt-4 w-full">
                        <Link :href="route('blog.index')">View All Articles</Link>
                    </Button>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
