<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';

import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type User } from '@/types';
import { UserRole } from '@/types/role';
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookHeartIcon,
    BookOpen,
    Calendar,
    ClipboardEdit,
    Folder,
    Heart,
    LayoutGrid,
    List,
    Megaphone,
    MessageCircleQuestion,
    Shield,
    TrendingUp,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';
import NavNotifications from './NavNotifications.vue';

const page = usePage();
const user = computed(() => page.props.auth.user as User);

const mainNavItems = computed((): NavItem[] => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: '/dashboard',
            icon: LayoutGrid,
        },
        {
            title: 'Public Events',
            href: '/public/events',
            icon: Calendar,
        },
    ];

    if (user.value.role === UserRole.PUBLIC_USER) {
        items.push(
            {
                title: 'Health Progress',
                href: '/health/progress',
                icon: TrendingUp,
            },
            {
                title: 'Blog',
                href: '/blogs',
                icon: BookHeartIcon,
            },
            {
                title: 'Appointment Booking',
                href: '/schedules',
                icon: Calendar,
            },
            {
                title: 'Appointments',
                href: '/bookings',
                icon: List,
            },
            {
                title: 'Health Assessment',
                href: '/assessments',
                icon: MessageCircleQuestion,
            },
        );
    }

    // Add healthcare routes for healthcare professionals and above
    if (
        user.value.role === UserRole.HEALTHCARE_PROFESSIONAL ||
        user.value.role === UserRole.HEALTH_CAMPAIGN_MANAGER ||
        user.value.role === UserRole.SYSTEM_ADMIN
    ) {
        items.push(
            {
                title: 'Healthcare',
                href: '/healthcare',
                icon: Heart,
            },
            {
                title: 'Health Progress',
                href: '/health/progress',
                icon: TrendingUp,
            },
            {
                title: 'Patients',
                href: '/healthcare/patients',
                icon: Users,
            },
            {
                title: 'Blog',
                href: '/blogs',
                icon: BookHeartIcon,
            },
            {
                title: 'My Blogs',
                href: '/healthcare/blogs',
                icon: BookOpen,
            },
            {
                title: 'Schedule',
                href: '/healthcare/schedules',
                icon: Calendar,
            },
            {
                title: 'Appointments',
                href: '/healthcare/appointments',
                icon: ClipboardEdit,
            },
            {
                title: 'Assessment Quizzes',
                href: '/healthcare/quizzes',
                icon: MessageCircleQuestion,
            },
        );
    }

    // Add campaign routes for campaign managers and above (roles 3, 4)
    if (user.value.role === UserRole.HEALTH_CAMPAIGN_MANAGER || user.value.role === UserRole.SYSTEM_ADMIN) {
        items.push({
            title: 'Campaigns',
            href: '/campaigns',
            icon: Megaphone,
        });
        items.push({
            title: 'Events',
            href: '/events',
            icon: Calendar,
        });
        items.push({
            title: 'Calendar',
            href: '/events/calendar',
            icon: Calendar,
        });
    }

    // Add admin routes for system admins only
    if (user.value.role === UserRole.SYSTEM_ADMIN) {
        items.push(
            {
                title: 'Admin Dashboard',
                href: '/admin',
                icon: Shield,
            },
            {
                title: 'User Management',
                href: '/admin/users',
                icon: Users,
            },
        );
    }

    return items;
});

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavNotifications />

            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
