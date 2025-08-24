<script setup lang="ts">
import axios from '@/axios';
import Icon from '@/components/Icon.vue';
import Toast from '@/components/Toast.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Blog } from '@/types/blog';
import { LaravelPagination } from '@/types/pagination';
import { Head, Link, router } from '@inertiajs/vue3';
import { Filter, Plus } from 'lucide-vue-next';
import {
    PaginationEllipsis,
    PaginationFirst,
    PaginationLast,
    PaginationList,
    PaginationListItem,
    PaginationNext,
    PaginationPrev,
    PaginationRoot,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectItemIndicator,
    SelectItemText,
    SelectLabel,
    SelectPortal,
    SelectRoot,
    SelectScrollDownButton,
    SelectScrollUpButton,
    SelectTrigger,
    SelectValue,
    SelectViewport,
} from 'reka-ui';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Healthcare', href: '/healthcare' },
    { title: 'Blog', href: '#' },
];

const statusFilterOptions = [
    {
        title: 'Draft',
        value: false,
    },
    {
        title: 'Published',
        value: true,
    },
];

// Toast ref
const toastRef = ref<InstanceType<typeof Toast> | null>(null);
const toastMessage = ref({ title: '', description: '', variant: 'default' as 'default' | 'success' | 'destructive' });

const props = defineProps<{ blogs: LaravelPagination<Blog> }>();
const pagination = ref<LaravelPagination<Blog>>(props.blogs);
const blogs = ref<Blog[]>(props.blogs.data);

const currentPage = ref(pagination.value.current_page);
const searchQuery = ref('');
const statusFilter = ref<string | undefined>();

const filteredBlogs = computed(() => {
    return blogs.value
        .filter((b) => !searchQuery.value || b.title.toLowerCase().includes(searchQuery.value.toLowerCase()))
        .filter((b) => !statusFilter.value || b.status === Boolean(statusFilter.value));
});

async function publishBlog(id: string) {
    await axios
        .patch(route('api.blog.update.publish', id))
        .then(() => {
            // Update the local blog status
            const blog = blogs.value.find((b) => b.id === id);
            if (blog) {
                blog.status = true;
                blog.published_at = new Date().toISOString();
            }

            toastMessage.value = {
                title: `Blog Published`,
                description: `Blog: ${blog?.title} has been successfully published`,
                variant: 'success',
            };

            // Show toast
            toastRef.value?.showToast();
        })
        .catch((err) => {
            toastMessage.value = {
                title: `Failed to publish blog`,
                description: err.message,
                variant: 'destructive',
            };
            console.error('Failed to publish blog', err);
            toastRef.value?.showToast();
        });
}

async function draftBlog(id: string) {
    await axios
        .patch(route('api.blog.update.draft', id))
        .then(() => {
            // Update the local blog status
            const blog = blogs.value.find((b) => b.id === id);
            if (blog) {
                blog.status = false;
                blog.published_at = undefined;
            }

            toastMessage.value = {
                title: `Blog Drafted`,
                description: `Blog: ${blog?.title} has been drafted`,
                variant: 'success',
            };

            // Show toast
            toastRef.value?.showToast();
        })
        .catch((err) => {
            toastMessage.value = {
                title: `Failed to draft blog`,
                description: err.message,
                variant: 'destructive',
            };
            console.error('Failed to draft blog', err);
            toastRef.value?.showToast();
        });
}

function editBlog(id: string) {
    router.visit(route('healthcare.blog.edit', id));
}

async function deleteBlog(id: string) {
    await axios
        .delete(route('api.blog.delete.soft', id))
        .then((res) => {
            // Remove from local array
            blogs.value = blogs.value.filter((b) => b.id !== id);

            toastMessage.value = {
                title: `Blog Deleted`,
                description: res.data.message,
                variant: 'success',
            };

            // Show toast
            toastRef.value?.showToast();

            router.visit(route('healthcare.blog.index', { page: currentPage.value }), {
                preserveScroll: true,
                preserveState: true,
            });
        })
        .catch((err) => {
            toastMessage.value = {
                title: `Failed to delete blog`,
                description: err.message,
                variant: 'destructive',
            };
            console.error('Failed to delete blog', err);
            toastRef.value?.showToast();
        });
}

function goToPage(page: number) {
    if (page === currentPage.value) return; // prevent duplicate navigation
    router.visit(route('healthcare.blog.index', { page }));
}
</script>

<template>
    <Head title="Manage Blogs" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toast ref="toastRef" :title="toastMessage.title" :description="toastMessage.description" :variant="toastMessage.variant" />
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Blogs</h1>
                    <p class="text-muted-foreground">Manage your blogs</p>
                </div>

                <!-- Create + Search + Filter -->
                <div class="flex flex-col items-center gap-4 md:flex-row">
                    <Button>
                        <Link class="inline-flex items-center" :href="route('healthcare.blog.create')">
                            <Plus class="mr-2 h-4 w-4" />
                            Create
                        </Link>
                    </Button>
                    <Input v-model="searchQuery" placeholder="Search by blog title" icon="search" class="min-w-[200px]" />
                    <SelectRoot v-model="statusFilter">
                        <SelectTrigger
                            class="inline-flex h-[35px] min-w-[160px] items-center justify-between gap-[5px] rounded-lg border bg-white px-[15px] text-xs leading-none shadow-sm outline-none hover:bg-stone-50 focus:shadow-[0_0_0_2px] focus:shadow-black dark:bg-black dark:hover:bg-accent"
                            aria-label="Filter options"
                        >
                            <SelectValue placeholder="Status" />
                            <Filter class="h-3.5 w-3.5" />
                        </SelectTrigger>
                        <SelectPortal>
                            <SelectContent
                                class="data-[side=top]:animate-slideDownAndFade data-[side=right]:animate-slideLeftAndFade data-[side=bottom]:animate-slideUpAndFade data-[side=left]:animate-slideRightAndFade z-[100] min-w-[160px] rounded-lg border bg-white shadow-sm will-change-[opacity,transform] dark:bg-black"
                                :side-offset="5"
                            >
                                <SelectScrollUpButton
                                    class="text-violet11 flex h-[25px] cursor-default items-center justify-center bg-white dark:bg-black"
                                >
                                    <Icon name="chevron up" icon="radix-icons:chevron-up" />
                                </SelectScrollUpButton>
                                <SelectViewport class="p-[5px]">
                                    <SelectLabel class="text-mauve11 px-[25px] text-xs leading-[25px]"> All Status </SelectLabel>
                                    <SelectGroup>
                                        <SelectItem
                                            v-for="(option, index) in statusFilterOptions"
                                            :key="index"
                                            class="text-grass11 data-[disabled]:text-mauve8 data-[highlighted]:bg-green9 data-[highlighted]:text-green1 relative flex h-[25px] items-center rounded-[3px] pr-[35px] pl-[25px] text-xs leading-none select-none data-[disabled]:pointer-events-none data-[highlighted]:outline-none"
                                            :value="String(option.value)"
                                        >
                                            <SelectItemIndicator class="absolute left-0 inline-flex w-[25px] items-center justify-center">
                                                <Icon name="check" icon="radix-icons:check" />
                                            </SelectItemIndicator>
                                            <SelectItemText>
                                                {{ option.title }}
                                            </SelectItemText>
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectViewport>
                                <SelectScrollDownButton
                                    class="text-violet11 flex h-[25px] cursor-default items-center justify-center bg-white dark:bg-black"
                                >
                                    <Icon name="chevron down" icon="radix-icons:chevron-down" />
                                </SelectScrollDownButton>
                            </SelectContent>
                        </SelectPortal>
                    </SelectRoot>
                </div>
            </div>
            <!-- Table Wrapper -->
            <div class="overflow-x-auto rounded-lg border">
                <div class="min-w-[900px]">
                    <!-- Table Header -->
                    <div class="grid grid-cols-[2fr_2fr_2fr_2fr_1fr_3fr] items-center bg-muted text-sm font-semibold text-muted-foreground">
                        <div class="px-4 py-2">Title</div>
                        <div class="px-4 py-2">Slug</div>
                        <div class="px-4 py-2">Cover Image</div>
                        <div class="px-4 py-2">Published At</div>
                        <div class="px-4 py-2">Status</div>
                        <div class="px-4 py-2">Actions</div>
                    </div>

                    <!-- Table Rows -->
                    <div
                        v-for="blog in filteredBlogs"
                        :key="blog.id"
                        class="grid grid-cols-[2fr_2fr_2fr_2fr_1fr_3fr] items-center border-t bg-white text-sm hover:bg-stone-50 dark:bg-black dark:hover:bg-accent"
                    >
                        <!-- Title (clickable to public view if published) -->
                        <div class="truncate px-4 py-3 font-medium">
                            <Link
                                v-if="blog.status"
                                :href="route('blog.show', blog.id)"
                                target="_blank"
                                rel="noopener"
                                class="hover:underline"
                            >
                                {{ blog.title }}
                            </Link>
                            <span v-else>{{ blog.title }}</span>
                        </div>

                        <!-- Slug -->
                        <div class="truncate px-4 py-3 text-muted-foreground">{{ blog.slug }}</div>

                        <!-- Cover Image -->
                        <div class="px-4 py-3">
                            <a v-if="blog.cover_image" :href="blog.cover_image" target="_blank" class="text-blue-600 hover:underline"> View Image </a>
                            <span v-else class="text-muted-foreground">No image</span>
                        </div>

                        <!-- Published At -->
                        <div class="px-4 py-3 text-muted-foreground">
                            {{ blog.published_at ? new Date(blog.published_at).toLocaleString() : '—' }}
                        </div>

                        <!-- Status -->
                        <div class="px-4 py-3">
                            <Badge :variant="blog.status ? 'default' : 'secondary'">
                                {{ blog.status ? 'Published' : 'Draft' }}
                            </Badge>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2 px-4 py-3 whitespace-nowrap">
                            <Button size="sm" variant="outline">
                                <Link :href="route('blog.show', blog.id)" target="_blank" rel="noopener" class="inline-flex items-center"> View </Link>
                            </Button>
                            <Button size="sm" variant="default" @click="editBlog(blog.id)"> Edit </Button>
                            <Button size="sm" variant="default" v-if="!blog.status" @click="publishBlog(blog.id)"> Publish </Button>
                            <Button size="sm" variant="secondary" v-if="blog.status" @click="draftBlog(blog.id)"> Make Draft </Button>
                            <Button size="sm" variant="destructive" @click="deleteBlog(blog.id)"> Delete </Button>
                        </div>
                    </div>
                    <!-- Empty State -->
                    <div v-if="filteredBlogs.length === 0" class="px-4 py-6 text-center text-muted-foreground">No blogs found.</div>
                </div>
            </div>
            <PaginationRoot :total="pagination.total" :items-per-page="pagination.per_page" :default-page="pagination.current_page" show-edges>
                <PaginationList v-slot="{ items }">
                    <PaginationFirst
                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-transparent transition hover:bg-white disabled:opacity-50 dark:hover:bg-stone-700/70"
                    >
                        <Icon name="double-arrow-left" icon="radix-icons:double-arrow-left" />
                    </PaginationFirst>
                    <PaginationPrev
                        class="mr-4 flex h-9 w-9 items-center justify-center rounded-lg bg-transparent transition hover:bg-white disabled:opacity-50 dark:hover:bg-stone-700/70"
                    >
                        <Icon name="chevron-left" icon="radix-icons:chevron-left" />
                    </PaginationPrev>
                    <template v-for="(page, index) in items">
                        <PaginationListItem
                            class="h-9 w-9 rounded-lg border transition hover:bg-white data-[selected]:!bg-white data-[selected]:text-black data-[selected]:shadow-sm dark:border-stone-800 dark:hover:bg-stone-700/70"
                            v-if="page.type === 'page'"
                            :key="index"
                            :value="page.value"
                            @click="goToPage(page.value)"
                        >
                            {{ page.value }}
                        </PaginationListItem>

                        <PaginationEllipsis class="flex h-9 w-9 items-center justify-center" v-else :key="page.type" :index="index">
                            &#8230;
                        </PaginationEllipsis>
                    </template>
                    <PaginationNext
                        class="ml-4 flex h-9 w-9 items-center justify-center rounded-lg bg-transparent transition hover:bg-white disabled:opacity-50 dark:hover:bg-stone-700/70"
                    >
                        <Icon name="chevron-right" icon="radix-icons:chevron-right" />
                    </PaginationNext>
                    <PaginationLast
                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-transparent transition hover:bg-white disabled:opacity-50 dark:hover:bg-stone-700/70"
                    >
                        <Icon name="double-arrow-right" icon="radix-icons:double-arrow-right" />
                    </PaginationLast>
                </PaginationList>
            </PaginationRoot>
        </div>
    </AppLayout>
</template>
