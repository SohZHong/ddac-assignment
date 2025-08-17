<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type User } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BookHeartIcon, BookOpen, Folder, Heart, LayoutGrid, Megaphone, Shield, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

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
            title: 'Blog',
            href: '/blogs',
            icon: BookHeartIcon,
        },
    ];

    // Add healthcare routes for healthcare professionals and above (roles 2, 3, 4)
    if (user.value.role === '2' || user.value.role === '3') {
        items.push({
            title: 'Healthcare',
            href: '/healthcare',
            icon: Heart,
        });
    }

    // Add campaign routes for campaign managers and above (roles 3, 4)
    if (user.value.role === '3') {
        items.push({
            title: 'Campaigns',
            href: '/campaigns',
            icon: Megaphone,
        });
    }

    // Add admin routes for system admins only (role 4)
    if (user.value.role === '4') {
        items.push({
            title: 'Admin',
            href: '/admin',
            icon: Shield,
        });
        items.push({
            title: 'User Management',
            href: '/admin/users',
            icon: Users,
        });
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
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
