<script setup lang="ts">
import TipTapTextEditor from '@/components/TipTapTextEditor.vue';
import Toast from '@/components/Toast.vue';
import { Button } from '@/components/ui/button';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { urlToFile } from '@/lib/utils';
import { BreadcrumbItem } from '@/types';
import { Blog, BlogStatus } from '@/types/blog';
import { Head, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const props = defineProps<{
    blog: Blog;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Healthcare', href: '/healthcare' },
    { title: 'Blog', href: '/healthcare/blogs' },
    { title: 'Edit', href: '#' },
];

const toastRef = ref<InstanceType<typeof Toast> | null>(null);
const toastMessage = ref({ title: '', description: '', variant: 'default' as 'default' | 'success' | 'destructive' });

let coverFile: File | null = null;

const form = useForm<{
    title: string;
    cover_image: File | null;
    content: string;
    status: BlogStatus;
}>({
    title: props.blog.title,
    cover_image: coverFile,
    content: props.blog.content!,
    status: props.blog.status,
});

const processing = ref(false);

const handleFile = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.cover_image = target.files[0];
    }
};

const handleUpdate = (html: string) => {
    form.content = html;
};

const submit = () => {
    processing.value = true;
    form.put(route('blog.update', props.blog.id), {
        onSuccess: () => {
            toastMessage.value = {
                title: 'Blog Updated',
                description: 'Your blog has been successfully updated!',
                variant: 'success',
            };
            toastRef.value?.showToast();
        },
        onError: (errors) => {
            toastMessage.value = {
                title: 'Failed to update blog',
                description: Object.values(errors).join(', '),
                variant: 'destructive',
            };
            toastRef.value?.showToast();
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};

onMounted(async () => {
    if (props.blog.cover_image) {
        coverFile = await urlToFile(props.blog.cover_image, 'cover.jpg', 'image/jpeg');
    }
});
</script>

<template>
    <Head title="Edit Blog" />
    <Toast ref="toastRef" :title="toastMessage.title" :description="toastMessage.description" :variant="toastMessage.variant" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Edit Blog</h1>
                    <p class="text-muted-foreground">Update your blog details and republish</p>
                </div>
            </div>

            <form @submit.prevent="submit">
                <!-- Status Toggle -->
                <div class="mb-4 flex items-center gap-3">
                    <Label class="mb-1">Status</Label>
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input type="checkbox" class="sr-only" v-model="form.status" :true-value="true" :false-value="false" />
                        <div
                            class="h-6 w-11 rounded-full bg-gray-300 transition-colors duration-300"
                            :class="form.status ? 'bg-green-500' : ''"
                        ></div>
                        <div
                            class="absolute top-0.5 left-0.5 h-5 w-5 transform rounded-full bg-white shadow transition-transform duration-300"
                            :class="form.status ? 'translate-x-5' : 'translate-x-0'"
                        ></div>
                    </label>
                    <span class="text-sm">{{ form.status ? 'Published' : 'Draft' }}</span>
                </div>

                <!-- Title -->
                <div class="mb-4">
                    <Label class="mb-1 block">Title</Label>
                    <Input v-model="form.title" type="text" class="w-full rounded border p-2" required />
                </div>

                <!-- Cover Image -->
                <div class="mb-4">
                    <Label class="mb-1 block">Cover Image</Label>
                    <Input type="file" @change="handleFile" class="w-full" />
                </div>

                <!-- Rich Text Editor -->
                <div class="mb-4">
                    <Label class="mb-1 block">Content</Label>
                    <TipTapTextEditor :onUpdate="handleUpdate" :content="form.content" />
                </div>

                <!-- Submit -->
                <Button type="submit" :disabled="processing">Update Blog</Button>
            </form>
        </div>
    </AppLayout>
</template>
