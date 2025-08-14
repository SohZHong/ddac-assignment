<script setup>
import { defineProps } from 'vue';

const props = defineProps({
    blog: Object,
});
</script>

<template>
    <div class="mx-auto max-w-3xl py-8">
        <img :src="blog.cover_image" :alt="blog.title" class="h-80 w-full rounded-lg object-cover" />

        <h1 class="mt-6 text-3xl font-bold">{{ blog.title }}</h1>

        <p class="mt-1 text-sm text-gray-500">By {{ blog.author.name }} — {{ blog.published_at || 'Unpublished' }}</p>

        <div class="prose mt-6 max-w-none" v-html="blog.content"></div>
    </div>
</template>
