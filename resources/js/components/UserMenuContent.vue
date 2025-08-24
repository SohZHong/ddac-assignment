<script setup lang="ts">
import UserInfo from '@/components/UserInfo.vue';
import { DropdownMenuGroup, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import type { User } from '@/types';
import { Link, router } from '@inertiajs/vue3';
import { LogOut, Settings, UserPlus } from 'lucide-vue-next';

interface Props {
    user: User;
}

defineProps<Props>();

const handleLogout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem>
            <div class="w-full">
                <Link :href="route('profile.edit')" class="flex w-full items-center">
                    <Settings class="mr-2 h-4 w-4" />
                    Settings
                </Link>
            </div>
        </DropdownMenuItem>
        <!-- Show Professional Application link only for public users (role '1') -->
        <DropdownMenuItem v-if="user.role === '1'">
            <div class="w-full">
                <Link :href="route('professional-application.create')" class="flex w-full items-center">
                    <UserPlus class="mr-2 h-4 w-4" />
                    Apply as Professional
                </Link>
            </div>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem>
        <div class="w-full">
            <button @click="handleLogout" class="flex w-full items-center">
                <LogOut class="mr-2 h-4 w-4" />
                Log out
            </button>
        </div>
    </DropdownMenuItem>
</template>