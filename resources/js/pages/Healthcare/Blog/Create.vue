<script setup lang="ts">
import TipTapTextEditor from '@/components/TipTapTextEditor.vue';
import Toast from '@/components/Toast.vue';
import { Button } from '@/components/ui/button';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { BlogStatus } from '@/types/blog';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Healthcare', href: '/healthcare' },
    { title: 'Blog', href: '/healthcare/blogs' },
    { title: 'Create', href: '#' },
];

const toastRef = ref<InstanceType<typeof Toast> | null>(null);
const toastMessage = ref({ title: '', description: '', variant: 'default' as 'default' | 'success' | 'destructive' });

const form = useForm<{
    title: string;
    cover_image: File | null;
    content: string;
    status: BlogStatus;
}>({
    title: '',
    cover_image: null,
    content: '',
    status: BlogStatus.STATUS_DRAFT,
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
    form.post(route('healthcare.blog.store'), {
        onSuccess: () => {
            toastMessage.value = {
                title: 'Blog Created',
                description: 'Your blog has been successfully created!',
                variant: 'success',
            };
            toastRef.value?.showToast();
        },
        onError: (errors) => {
            toastMessage.value = {
                title: 'Failed to create blog',
                description: Object.values(errors).join(', '), // show validation errors
                variant: 'destructive',
            };
            toastRef.value?.showToast();
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};
</script>

<template>
    <Head title="Manage Blogs" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <Toast ref="toastRef" :title="toastMessage.title" :description="toastMessage.description" :variant="toastMessage.variant" />
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Create New Blog</h1>
                    <p class="text-muted-foreground">Create a new blog to educate the public</p>
                </div>
            </div>
            <form @submit.prevent="submit">
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
                    <TipTapTextEditor :onUpdate="handleUpdate" />
                </div>

                <!-- Submit -->
                <Button type="submit" :disabled="processing">Create</Button>
            </form>
        </div>
    </AppLayout>
</template>
