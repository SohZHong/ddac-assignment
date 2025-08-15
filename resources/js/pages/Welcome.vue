<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Activity, BookOpen, Calendar, CheckCircle, ClipboardList, Heart, Star, Stethoscope, TrendingUp, Video } from 'lucide-vue-next';

interface Feature {
    icon: any;
    title: string;
    description: string;
}

interface HealthService {
    icon: any;
    title: string;
    description: string;
    features: string[];
}

const keyFeatures: Feature[] = [
    {
        icon: ClipboardList,
        title: 'Health Risk Assessment',
        description: 'Take comprehensive lifestyle quizzes to evaluate your health risks and get personalized insights.',
    },
    {
        icon: Heart,
        title: 'Personalized Recommendations',
        description: 'Receive tailored health advice and lifestyle tips based on your assessment results.',
    },
    {
        icon: Calendar,
        title: 'Health Consultations',
        description: 'Book appointments with certified healthcare professionals for screenings and consultations.',
    },
    {
        icon: BookOpen,
        title: 'Health Education Hub',
        description: 'Access expert articles, diet tips, and infographics to stay informed about your health.',
    },
    {
        icon: TrendingUp,
        title: 'Health Campaigns',
        description: 'Participate in community health awareness campaigns and wellness events.',
    },
    {
        icon: Activity,
        title: 'Health Monitoring',
        description: 'Track your health progress and receive ongoing support from healthcare professionals.',
    },
];

const healthServices: HealthService[] = [
    {
        icon: ClipboardList,
        title: 'Lifestyle Assessment',
        description: 'Comprehensive health risk evaluation',
        features: ['Risk factor analysis', 'Lifestyle questionnaires', 'Health scoring system', 'Progress tracking'],
    },
    {
        icon: Stethoscope,
        title: 'Professional Care',
        description: 'Connect with certified healthcare providers',
        features: ['Expert consultations', 'Health screenings', 'Medical reports', 'Professional guidance'],
    },
    {
        icon: Video,
        title: 'Health Education',
        description: 'Learn from expert content and campaigns',
        features: ['Educational articles', 'Diet and nutrition tips', 'Health webinars', 'Community events'],
    },
];

const stats = [
    { number: '10K+', label: 'Active Users' },
    { number: '500+', label: 'Healthcare Professionals' },
    { number: '100+', label: 'Health Campaigns' },
    { number: '95%', label: 'User Satisfaction' },
];
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div class="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]">
        <header class="mb-6 w-full max-w-[335px] text-sm not-has-[nav]:hidden lg:max-w-4xl">
            <nav class="flex items-center justify-end gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="route('login')"
                        class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                    >
                        Log in
                    </Link>
                    <Link
                        :href="route('register')"
                        class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                    >
                        Register
                    </Link>
                </template>
            </nav>
        </header>

        <div class="mx-auto w-full max-w-7xl">
            <!-- Hero Section -->
            <div class="mb-20 text-center">
                <div class="mb-6 flex justify-center">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-green-500 shadow-lg">
                        <Heart class="h-8 w-8 text-white" />
                    </div>
                </div>
                <h1 class="mb-6 text-5xl font-bold tracking-tight text-[#1b1b18] sm:text-7xl dark:text-[#EDEDEC]">
                    Your Health,<br />
                    <span class="bg-gradient-to-r from-blue-600 to-green-600 bg-clip-text text-transparent"> Our Priority </span>
                </h1>
                <p class="mx-auto mt-6 max-w-3xl text-xl leading-8 text-[#706f6c] dark:text-[#A1A09A]">
                    Take control of your health journey with personalized assessments, expert consultations, and comprehensive health education - all
                    in one integrated platform.
                </p>
                <div class="mt-12 flex items-center justify-center gap-x-6">
                    <Link
                        :href="route('register')"
                        class="transform rounded-lg bg-gradient-to-r from-blue-600 to-green-600 px-8 py-4 text-lg font-semibold text-white shadow-lg transition-all duration-200 hover:scale-105 hover:shadow-xl"
                    >
                        Start Your Health Journey
                    </Link>
                    <Link
                        :href="route('login')"
                        class="text-lg leading-6 font-semibold text-[#1b1b18] transition-colors hover:text-blue-600 dark:text-[#EDEDEC] dark:hover:text-blue-400"
                    >
                        Sign In <span aria-hidden="true">→</span>
                    </Link>
                </div>
            </div>

            <!-- Stats Section -->
            <div class="mb-20 grid grid-cols-2 gap-8 lg:grid-cols-4">
                <div v-for="stat in stats" :key="stat.label" class="text-center">
                    <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ stat.number }}</div>
                    <div class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">{{ stat.label }}</div>
                </div>
            </div>

            <!-- Key Features Section -->
            <div class="mb-20">
                <div class="mb-16 text-center">
                    <h2 class="mb-4 text-3xl font-bold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]">Everything You Need for Better Health</h2>
                    <p class="mx-auto max-w-2xl text-lg text-[#706f6c] dark:text-[#A1A09A]">
                        Our comprehensive platform provides all the tools and support you need to take charge of your health and wellness.
                    </p>
                </div>

                <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="feature in keyFeatures"
                        :key="feature.title"
                        class="group relative rounded-2xl border border-[#e3e3e0] bg-white p-8 shadow-sm transition-all duration-300 hover:border-blue-200 hover:shadow-lg dark:border-[#3E3E3A] dark:bg-[#161615] dark:hover:border-blue-800"
                    >
                        <div class="mb-4 flex items-center">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-50 transition-colors group-hover:bg-blue-100 dark:bg-blue-950 dark:group-hover:bg-blue-900"
                            >
                                <component :is="feature.icon" class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                            </div>
                        </div>
                        <h3 class="mb-3 text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                            {{ feature.title }}
                        </h3>
                        <p class="leading-relaxed text-[#706f6c] dark:text-[#A1A09A]">
                            {{ feature.description }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Health Services Section -->
            <div class="mb-20">
                <div class="mb-16 text-center">
                    <h2 class="mb-4 text-3xl font-bold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]">Comprehensive Health Services</h2>
                    <p class="mx-auto max-w-2xl text-lg text-[#706f6c] dark:text-[#A1A09A]">
                        From assessment to education, we provide end-to-end health management solutions.
                    </p>
                </div>

                <div class="grid gap-8 lg:grid-cols-3">
                    <div
                        v-for="service in healthServices"
                        :key="service.title"
                        class="relative rounded-2xl border border-[#e3e3e0] bg-white p-8 shadow-sm transition-shadow duration-300 hover:shadow-lg dark:border-[#3E3E3A] dark:bg-[#161615]"
                    >
                        <div class="mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-green-500">
                            <component :is="service.icon" class="h-8 w-8 text-white" />
                        </div>
                        <h3 class="mb-3 text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">
                            {{ service.title }}
                        </h3>
                        <p class="mb-6 text-[#706f6c] dark:text-[#A1A09A]">
                            {{ service.description }}
                        </p>
                        <ul class="space-y-2">
                            <li
                                v-for="feature in service.features"
                                :key="feature"
                                class="flex items-center text-sm text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                <CheckCircle class="mr-3 h-4 w-4 flex-shrink-0 text-green-500" />
                                {{ feature }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- How It Works Section -->
            <div class="mb-20">
                <div class="mb-16 text-center">
                    <h2 class="mb-4 text-3xl font-bold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]">How It Works</h2>
                    <p class="mx-auto max-w-2xl text-lg text-[#706f6c] dark:text-[#A1A09A]">
                        Get started with your health journey in just a few simple steps.
                    </p>
                </div>

                <div class="grid gap-8 md:grid-cols-3">
                    <div class="text-center">
                        <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900">
                            <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">1</span>
                        </div>
                        <h3 class="mb-3 text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">Take Assessment</h3>
                        <p class="text-[#706f6c] dark:text-[#A1A09A]">
                            Complete our comprehensive lifestyle quiz to evaluate your health risks and current status.
                        </p>
                    </div>
                    <div class="text-center">
                        <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                            <span class="text-2xl font-bold text-green-600 dark:text-green-400">2</span>
                        </div>
                        <h3 class="mb-3 text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">Get Recommendations</h3>
                        <p class="text-[#706f6c] dark:text-[#A1A09A]">
                            Receive personalized health advice and connect with healthcare professionals if needed.
                        </p>
                    </div>
                    <div class="text-center">
                        <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900">
                            <span class="text-2xl font-bold text-purple-600 dark:text-purple-400">3</span>
                        </div>
                        <h3 class="mb-3 text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">Track Progress</h3>
                        <p class="text-[#706f6c] dark:text-[#A1A09A]">
                            Monitor your health journey and participate in wellness campaigns and educational events.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Final CTA Section -->
            <div class="rounded-3xl bg-gradient-to-r from-blue-50 to-green-50 p-12 text-center dark:from-blue-950 dark:to-green-950">
                <div class="mb-6 flex justify-center">
                    <div class="flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-green-500 shadow-lg">
                        <Star class="h-10 w-10 text-white" />
                    </div>
                </div>
                <h2 class="mb-4 text-3xl font-bold tracking-tight text-[#1b1b18] dark:text-[#EDEDEC]">Ready to Transform Your Health?</h2>
                <p class="mx-auto mb-8 max-w-2xl text-lg text-[#706f6c] dark:text-[#A1A09A]">
                    Join thousands of users who have already started their journey to better health with our comprehensive platform.
                </p>
                <div class="flex flex-col justify-center gap-4 sm:flex-row">
                    <Link
                        :href="route('register')"
                        class="transform rounded-lg bg-gradient-to-r from-blue-600 to-green-600 px-8 py-4 text-lg font-semibold text-white shadow-lg transition-all duration-200 hover:scale-105 hover:shadow-xl"
                    >
                        Get Started for Free
                    </Link>
                    <Link
                        :href="route('login')"
                        class="rounded-lg border-2 border-blue-600 px-8 py-4 text-lg font-semibold text-blue-600 transition-all duration-200 hover:bg-blue-600 hover:text-white dark:border-blue-400 dark:text-blue-400 dark:hover:bg-blue-400 dark:hover:text-white"
                    >
                        Sign In
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
