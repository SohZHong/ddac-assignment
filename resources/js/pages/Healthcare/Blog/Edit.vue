<script setup lang="ts">
import TipTapTextEditor from '@/components/TipTapTextEditor.vue';
import { urlToFile } from '@/lib/utils';
import { Blog, BlogStatus } from '@/types/blog';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const props = defineProps<{
    blog: Blog;
}>();
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
    status: props.blog.status ? BlogStatus.STATUS_PUBLISHED : BlogStatus.STATUS_DRAFT,
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
        onFinish: () => {
            processing.value = false;
        },
    });
};

onMounted(async () => {
    if (props.blog.cover_image) {
        // convert URL string to File
        coverFile = await urlToFile(props.blog.cover_image, 'cover.jpg', 'image/jpeg');
    }
});
</script>

<template>
    <div class="mx-auto max-w-3xl py-8">
        <h1 class="mb-6 text-2xl font-bold">Update Blog</h1>
        <form @submit.prevent="submit">
            <div class="mb-4 flex items-center space-x-4">
                <label class="block font-medium">Status</label>

                <label class="relative inline-flex cursor-pointer items-center">
                    <input
                        type="checkbox"
                        class="sr-only"
                        v-model="form.status"
                        :true-value="BlogStatus.STATUS_PUBLISHED"
                        :false-value="BlogStatus.STATUS_DRAFT"
                    />
                    <div
                        class="h-8 w-16 rounded-full bg-gray-700 transition-colors duration-300"
                        :class="form.status === BlogStatus.STATUS_PUBLISHED ? 'bg-green-500' : ''"
                    ></div>
                    <div
                        class="absolute top-1 left-1 h-6 w-6 transform rounded-full bg-white shadow transition-transform duration-300"
                        :class="form.status === BlogStatus.STATUS_PUBLISHED ? 'translate-x-8' : 'translate-x-0'"
                    ></div>
                </label>

                <span class="text-sm">{{ form.status === BlogStatus.STATUS_PUBLISHED ? 'Published' : 'Draft' }}</span>
            </div>
            <!-- Title -->
            <div class="mb-4">
                <label class="mb-1 block">Title</label>
                <input v-model="form.title" type="text" class="w-full rounded border p-2" required />
            </div>

            <!-- Cover Image -->
            <div class="mb-4">
                <label class="mb-1 block">Cover Image</label>
                <input type="file" @change="handleFile" class="w-full" />
            </div>

            <!-- Rich Text Editor -->
            <div class="mb-4">
                <label class="mb-1 block">Content</label>
                <TipTapTextEditor :onUpdate="handleUpdate" :content="form.content" />
            </div>

            <!-- Submit -->
            <button type="submit" class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700" :disabled="processing">Publish</button>
        </form>
    </div>
</template>
