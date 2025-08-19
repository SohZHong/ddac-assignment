<script setup lang="ts">
import axios from '@/axios';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { usePage } from '@inertiajs/vue3';
import { Bell } from 'lucide-vue-next';
import { ref } from 'vue';
import Button from './ui/button/Button.vue';

const page = usePage();
const notifications = ref(page.props.auth.user.notifications || []);
const isOpen = ref(false);

function togglePanel() {
    isOpen.value = !isOpen.value;
}
console.log(page.props.auth.user);

async function handleButtonClick() {
    await axios
        .post('api/notifications/read-all')
        .then(() => {
            notifications.value = [];
        })
        .catch((err) => {
            console.error(err);
        });
}
</script>

<template>
    <SidebarMenu>
        <SidebarMenuItem>
            <SidebarMenuButton size="lg" class="flex items-center justify-start gap-2" @click="togglePanel">
                <Avatar class="relative h-8 w-8 overflow-hidden rounded-lg">
                    <AvatarFallback class="flex items-center justify-center rounded-lg bg-muted">
                        <Bell class="h-4 w-4 text-foreground" />
                    </AvatarFallback>
                </Avatar>
                <!-- Red badge -->
                <span class="absolute -top-1 right-1 rounded-full bg-red-500 px-1 text-xs text-white">
                    {{ notifications.length }}
                </span>
                <!-- Text label -->
                <div class="grid flex-1 text-left text-sm leading-tight">
                    <span class="truncate font-medium">Notifications</span>
                </div>
            </SidebarMenuButton>
        </SidebarMenuItem>
    </SidebarMenu>

    <!-- Slide-over panel -->
    <transition name="slide">
        <div v-if="isOpen" class="fixed inset-y-0 right-0 z-50 flex w-80 flex-col bg-white shadow-xl">
            <div class="flex items-center justify-between border-b p-4">
                <h2 class="text-lg font-semibold">Notifications</h2>
                <button @click="togglePanel" class="text-gray-500 hover:text-gray-800">✕</button>
            </div>
            <!-- <Button @click="() => handleButtonClick">Hey</Button> -->
            <div class="flex-1 overflow-y-auto p-4">
                <div v-if="notifications.length">
                    <div v-for="n in notifications" :key="n.id" class="mb-2 rounded border p-3 hover:bg-gray-50">
                        <p class="text-sm">{{ n.data.message }}</p>
                        <small class="text-xs text-gray-500">{{ new Date(n.created_at).toLocaleString() }}</small>
                    </div>
                </div>
                <div v-else class="mt-10 text-center text-sm text-gray-500">No notifications</div>
            </div>
            <div class="border-t p-4 text-right">
                <Button variant="destructive" @click="handleButtonClick">Mark all as read</Button>
            </div>
        </div>
    </transition>
</template>

<style scoped>
.slide-enter-active,
.slide-leave-active {
    transition: transform 0.3s ease;
}
.slide-enter-from,
.slide-leave-to {
    transform: translateX(100%);
}
</style>
