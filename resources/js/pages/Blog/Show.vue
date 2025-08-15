<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { User } from '@/types';
import { Blog } from '@/types/blog';
import { UserRole } from '@/types/role';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Edit, Image, Settings, Trash } from 'lucide-vue-next';
import { defineProps, ref } from 'vue';

const props = defineProps<{
    blog: Blog;
}>();

const page = usePage();
const user = page.props.auth.user as User;

const menuOpen = ref(false);

// Close dropdown when clicking outside
const handleClickOutside = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    if (!target.closest('.gear-menu')) {
        menuOpen.value = false;
    }
};

const deleteBlog = () => {
    if (confirm('Are you sure you want to delete this blog?')) {
        router.delete(route('blog.softdelete', props.blog.id), {
            preserveScroll: true,
        });
    }
};

document.addEventListener('click', handleClickOutside);
</script>

<template>
    <div class="relative mx-auto max-w-3xl py-8">
        <!-- Gear Icon -->
        <div v-if="user.id === Number(blog.author.id) || user.role === UserRole.SYSTEM_ADMIN" class="gear-menu absolute top-3 right-3">
            <Button @click.stop="menuOpen = !menuOpen" class="rounded-full p-2 hover:bg-gray-200 dark:hover:bg-gray-700">
                <Settings class="h-5 w-5" />
            </Button>

            <!-- Dropdown Menu -->
            <div v-if="menuOpen" class="absolute right-0 mt-2 w-36 rounded-md bg-white shadow-lg ring-1 ring-black/5 dark:bg-gray-800">
                <ul class="py-1 text-sm">
                    <!-- Edit button -->
                    <li v-if="user.id === Number(blog.author.id)">
                        <Link :href="route('blog.edit', blog.id)" class="flex items-center px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <Edit class="mr-2 h-4 w-4" />
                            Edit
                        </Link>
                    </li>

                    <!-- Delete button -->
                    <li v-if="user.id === Number(blog.author.id) || user.role === UserRole.SYSTEM_ADMIN">
                        <Button @click="deleteBlog" class="flex items-center px-4 py-2 text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700">
                            <Trash class="mr-2 h-4 w-4" /> Delete
                        </Button>
                    </li>
                </ul>
            </div>
        </div>

        <Image :src="blog.cover_image" :alt="blog.title" class="h-80 w-full rounded-lg object-cover" />

        <h1 class="mt-6 text-3xl font-bold">{{ blog.title }}</h1>

        <p class="mt-1 text-sm text-gray-500">By {{ blog.author.name }} — {{ blog.published_at || 'Unpublished' }}</p>

        <div class="prose prose-sm mt-6 max-w-none dark:prose-invert" v-html="blog.content"></div>
    </div>
</template>
