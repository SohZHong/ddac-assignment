<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { Blog } from '@/types/blog';
import { LaravelPagination } from '@/types/pagination';
// import { UserRole } from '@/types/role';
import { Head, Link } from '@inertiajs/vue3';
// import { Plus } from 'lucide-vue-next';

const props = defineProps<{
    blogs: LaravelPagination<Blog>;
}>();

console.log(props.blogs);
</script>

<template>
    <Head title="Educational Resources" />

    <AppLayout>
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Educational Resources</h1>
                    <p class="text-muted-foreground">Enrich yourself with knowledge</p>
                </div>
                <Link :href="'/healthcare/blogs'" class="inline-flex items-center cursor-pointer">
                    <Button class="cursor-pointer">
                        My Blogs
                    </Button>
                </Link>
                <!-- <Link
                    v-if="user.role === UserRole.HEALTHCARE_PROFESSIONAL || user.role === UserRole.HEALTH_CAMPAIGN_MANAGER"
                    :href="route('blog.create')"
                    class="inline-flex items-center"
                >
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        Publish Your Blog
                    </Button>
                </Link> -->
            </div>
            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                <div v-for="blog in blogs.data" :key="blog.id" class="group cursor-pointer">
                    <Link :href="route('blog.show', blog.id)" class="block overflow-hidden rounded-lg">
                        <img
                            v-if="blog.cover_image"
                            :src="blog.cover_image"
                            :alt="blog.title"
                            class="h-56 w-full object-cover transition-transform duration-300 group-hover:scale-105"
                        />
                    </Link>

                    <div class="mt-4">
                        <Link
                            :href="route('blog.show', blog.id)"
                            class="text-xl leading-tight font-semibold transition-colors group-hover:text-blue-600"
                        >
                            {{ blog.title }}
                        </Link>

                        <p class="mt-1 text-sm text-gray-500">
                            By {{ blog.author.name }}
                            {{ blog.published_at ? ' - ' + new Date(blog.published_at).toLocaleString() : null }}
                        </p>
                    </div>
                </div>
            </div>
            <div>
                <template v-for="link in blogs.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" :class="{ 'font-bold': link.active }">
                        {{ link.label }}
                    </Link>
                    <span v-else v-html="link.label" class="text-gray-400" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
