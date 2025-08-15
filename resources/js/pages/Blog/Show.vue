<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { User } from '@/types';
import { Blog } from '@/types/blog';
import { Link, usePage } from '@inertiajs/vue3';
import { Edit, Image } from 'lucide-vue-next';
import { defineProps } from 'vue';

defineProps<{
    blog: Blog;
}>();

const page = usePage();
const user = page.props.auth.user as User;
</script>

<template>
    <div class="relative mx-auto max-w-3xl py-8">
        <!-- Edit Button -->
        <Button v-if="user.id === Number(blog.author.id)" class="absolute top-3 right-3">
            <Link :href="route('blog.edit', blog.id)" class="inline-flex items-center">
                <Edit class="mr-2 h-4 w-4" />
                Edit
            </Link>
        </Button>

        <Image :src="blog.cover_image" :alt="blog.title" class="h-80 w-full rounded-lg object-cover" />

        <h1 class="mt-6 text-3xl font-bold">{{ blog.title }}</h1>

        <p class="mt-1 text-sm text-gray-500">By {{ blog.author.name }} — {{ blog.published_at || 'Unpublished' }}</p>

        <div class="prose prose-sm mt-6 max-w-none dark:prose-invert" v-html="blog.content"></div>
    </div>
</template>
