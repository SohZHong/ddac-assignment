<script setup lang="ts">
import Bold from '@tiptap/extension-bold';
import Color from '@tiptap/extension-color';
import type { Level } from '@tiptap/extension-heading';
import Heading from '@tiptap/extension-heading';
import Image from '@tiptap/extension-image';
import Link from '@tiptap/extension-link';
import Paragraph from '@tiptap/extension-paragraph';
import Text from '@tiptap/extension-text';
import { TextStyleKit } from '@tiptap/extension-text-style';
import Underline from '@tiptap/extension-underline';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { computed, onBeforeUnmount } from 'vue';
import { Button } from './ui/button';

interface Props {
    onUpdate?: (content: string) => void;
    content?: string;
}

const props = defineProps<Props>();

// Editor Setup
const editor = useEditor({
    editorProps: {
        attributes: {
            class: 'prose prose-sm dark:prose-invert focus:outline-none min-h-[300px]',
        },
    },
    extensions: [
        StarterKit,
        Link.configure({
            openOnClick: false,
        }),
        Image,
        Underline,
        Text,
        Paragraph,
        TextStyleKit,
        Color,
        Bold,
        Heading.configure({ levels: [1, 2, 3] }),
    ],
    content: props.content || '',
    onUpdate({ editor }) {
        const html = editor.getHTML();
        if (props.onUpdate) props.onUpdate(html);
    },
});

// Bold
const toggleBold = () => editor.value?.chain().focus().toggleBold().run();
const isBold = computed(() => editor.value?.isActive('bold'));

// Italic
const toggleItalic = () => editor.value?.chain().focus().toggleItalic().run();
const isItalic = computed(() => editor.value?.isActive('italic'));

// Underline
const toggleUnderline = () => editor.value?.chain().focus().toggleUnderline().run();
const isUnderline = computed(() => editor.value?.isActive('underline'));

// Headings
const headingLevels: Array<1 | 2 | 3> = [1, 2, 3];
const setHeading = (level: Level) => editor.value?.chain().focus().toggleHeading({ level }).run();
const isHeading = (level: Level) => computed(() => editor.value?.isActive('heading', { level }));

// Color
const setTextColor = (color: string) => editor.value?.chain().focus().setColor(color).run();

// Links
const setLink = () => {
    const url = window.prompt('Enter URL');
    if (url) editor.value?.chain().focus().setLink({ href: url }).run();
};
const unsetLink = () => editor.value?.chain().focus().unsetLink().run();
const isLink = computed(() => editor.value?.isActive('link'));

// Image
const addImage = () => {
    const url = window.prompt('Enter image URL');
    if (url) editor.value?.chain().focus().setImage({ src: url }).run();
};

// Cleanup editor instance
onBeforeUnmount(() => {
    if (editor.value) editor.value.destroy();
});
</script>

<style scoped>
button {
    padding: 4px 6px;
    border-radius: 4px;
}

h1 {
    font-size: 2rem; /* 32px */
    font-weight: bold;
}

h2 {
    font-size: 1.5rem; /* 24px */
    font-weight: bold;
}

h3 {
    font-size: 1.25rem; /* 20px */
    font-weight: bold;
}
</style>

<template>
    <div class="mb-4 flex flex-wrap gap-2 border-b pb-2">
        <!-- Bold -->
        <Button @click="toggleBold" :class="['rounded border px-2 py-1', isBold ? 'bg-gray-300' : '']">B</Button>

        <!-- Italic -->
        <Button @click="toggleItalic" :class="['rounded border px-2 py-1 italic', isItalic ? 'bg-gray-300' : '']">I</Button>

        <!-- Underline -->
        <Button @click="toggleUnderline" :class="['rounded border px-2 py-1 underline', isUnderline ? 'bg-gray-300' : '']">U</Button>

        <!-- Headings -->
        <Button
            v-for="level in headingLevels"
            :key="level"
            @click="setHeading(level)"
            :class="['rounded border px-2 py-1', isHeading(level) ? 'bg-gray-300' : '']"
        >
            H{{ level }}
        </Button>

        <!-- Text color -->
        <input type="color" @input="setTextColor(($event.target as HTMLInputElement).value)" />
        <!-- Link -->
        <Button @click="setLink" class="rounded border px-2 py-1">Link</Button>
        <Button v-if="isLink" @click="unsetLink" class="rounded border px-2 py-1">Remove Link</Button>

        <!-- Image -->
        <Button @click="addImage" class="rounded border px-2 py-1">Img</Button>
    </div>

    <!-- Editor -->
    <EditorContent :editor="editor" class="min-h-[300px] rounded border p-4" />
</template>
