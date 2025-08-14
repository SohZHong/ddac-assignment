<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type User } from '@/types';
import type { BlogList } from '@/types/blog';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';

defineProps<{
    blogs: BlogList;
}>();

const page = usePage();
const user = page.props.auth.user as User;
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
                <Button disabled>
                    <Plus class="mr-2 h-4 w-4" />
                    Publish Your Blog
                </Button>
            </div>

            <!-- Blog List -->
            <!-- <Card>
                <CardContent>
                    <div class="space-y-4">
                        <div
                            v-for="blog in blogs.data"
                            :key="blog.id"
                            class="flex items-center justify-between rounded-lg border p-4 hover:bg-muted/50"
                        >
                            <
                            <Link :key="blog.title" :href="`/blog/${blog.id}`">
                                {{ blog.title }}
                            </Link>
                            <small> By {{ blog.author.name }} — {{ blog.published_at }} </small>
                        </div>
                    </div>
                </CardContent>
            </Card> -->
            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                <div v-for="blog in blogs.data" :key="blog.id" class="group cursor-pointer">
                    <Link :href="route('blog.show', blog.id)" class="block overflow-hidden rounded-lg">
                        <img
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
                            By {{ blog.author.name }} —
                            {{ blog.published_at }}
                        </p>
                    </div>
                </div>
            </div>
            <div>
                <template v-for="link in blogs.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" v-html="link.label" :class="{ 'font-bold': link.active }" />
                    <span v-else v-html="link.label" class="text-gray-400" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
