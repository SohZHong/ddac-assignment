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
import { BoldIcon, Italic, LucideStrikethrough, LucideUnderline } from 'lucide-vue-next';
import { ToolbarButton, ToolbarRoot, ToolbarSeparator, ToolbarToggleGroup, ToolbarToggleItem } from 'reka-ui';
import { computed, onBeforeUnmount } from 'vue';
import Icon from './Icon.vue';

interface Props {
    onUpdate?: (content: string) => void;
    content?: string;
}
const props = defineProps<Props>();

/* Editor */
const editor = useEditor({
    editorProps: {
        attributes: {
            class: 'prose prose-sm dark:prose-invert focus:outline-none min-h-[300px]',
        },
    },
    extensions: [
        StarterKit,
        Link.configure({ openOnClick: false }),
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
        props.onUpdate?.(html);
    },
});

/* Marks / Nodes state + actions */
const toggleBold = () => editor.value?.chain().focus().toggleBold().run();
const isBold = computed(() => editor.value?.isActive('bold') ?? false);

const toggleItalic = () => editor.value?.chain().focus().toggleItalic().run();
const isItalic = computed(() => editor.value?.isActive('italic') ?? false);

const toggleUnderline = () => editor.value?.chain().focus().toggleUnderline().run();
const isUnderline = computed(() => editor.value?.isActive('underline') ?? false);

const toggleStrike = () => editor.value?.chain().focus().toggleStrike().run();
const isStrike = computed(() => editor.value?.isActive('strike') ?? false);

/* Headings */
const setHeading = (level: Level) => editor.value?.chain().focus().toggleHeading({ level }).run();
const isHeadingActive = (level: Level) => editor.value?.isActive('heading', { level }) ?? false;

/* Text color */
const setTextColor = (color: string) => editor.value?.chain().focus().setColor(color).run();

/* Links */
const isLink = computed(() => editor.value?.isActive('link') ?? false);
const setLink = () => {
    const url = window.prompt('Enter URL');
    if (!url) return;
    editor.value?.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
};
const unsetLink = () => editor.value?.chain().focus().unsetLink().run();

/* Image */
const addImage = () => {
    const url = window.prompt('Enter image URL');
    if (!url) return;
    editor.value?.chain().focus().setImage({ src: url }).run();
};

/* Cleanup */
onBeforeUnmount(() => {
    editor.value?.destroy();
});
</script>

<style scoped>
/* Color input styled to match buttons */
.color-swatch {
    background: transparent;
}
.color-swatch::-webkit-color-swatch {
    border: none;
    border-radius: 6px;
}
.color-swatch::-webkit-color-swatch-wrapper {
    padding: 4px;
    border-radius: 6px;
}
</style>

<template>
    <div>
        <ToolbarRoot class="flex w-full flex-wrap items-center gap-1 rounded-lg border bg-white p-2 shadow-sm" aria-label="Formatting options">
            <!-- Basic formatting -->
            <ToolbarToggleGroup type="multiple" aria-label="Text formatting">
                <ToolbarToggleItem
                    class="ml-0.5 inline-flex h-[28px] w-[28px] items-center justify-center rounded bg-white text-black transition-colors hover:bg-gray-100 data-[state=on]:bg-green-200 data-[state=on]:text-green-800"
                    :pressed="isBold"
                    value="bold"
                    aria-label="Bold"
                    @click="toggleBold"
                >
                    <BoldIcon class="h-4 w-4" />
                </ToolbarToggleItem>

                <ToolbarToggleItem
                    class="ml-0.5 inline-flex h-[28px] w-[28px] items-center justify-center rounded bg-white text-black transition-colors hover:bg-gray-100 data-[state=on]:bg-green-200 data-[state=on]:text-green-800"
                    :pressed="isItalic"
                    value="italic"
                    aria-label="Italic"
                    @click="toggleItalic"
                >
                    <Italic class="h-4 w-4" />
                </ToolbarToggleItem>

                <ToolbarToggleItem
                    class="ml-0.5 inline-flex h-[28px] w-[28px] items-center justify-center rounded bg-white text-black transition-colors hover:bg-gray-100 data-[state=on]:bg-green-200 data-[state=on]:text-green-800"
                    :pressed="isUnderline"
                    value="underline"
                    aria-label="Underline"
                    @click="toggleUnderline"
                >
                    <LucideUnderline class="h-4 w-4" />
                </ToolbarToggleItem>

                <ToolbarToggleItem
                    class="ml-0.5 inline-flex h-[28px] w-[28px] items-center justify-center rounded bg-white text-black transition-colors hover:bg-gray-100 data-[state=on]:bg-green-200 data-[state=on]:text-green-800"
                    :pressed="isStrike"
                    value="strike"
                    aria-label="Strikethrough"
                    @click="toggleStrike"
                >
                    <LucideStrikethrough class="h-4 w-4" />
                </ToolbarToggleItem>
            </ToolbarToggleGroup>

            <ToolbarSeparator class="mx-2 h-5 w-px bg-gray-200" />

            <!-- Headings -->
            <ToolbarToggleGroup type="multiple" aria-label="Headings">
                <ToolbarToggleItem
                    class="ml-0.5 inline-flex h-[28px] w-[34px] items-center justify-center rounded bg-white font-semibold text-black transition-colors hover:bg-gray-100 data-[state=on]:bg-green-200 data-[state=on]:text-green-800"
                    :pressed="isHeadingActive(1)"
                    value="h1"
                    aria-label="Heading 1"
                    @click="setHeading(1)"
                >
                    H1
                </ToolbarToggleItem>
                <ToolbarToggleItem
                    class="ml-0.5 inline-flex h-[28px] w-[34px] items-center justify-center rounded bg-white font-semibold text-black transition-colors hover:bg-gray-100 data-[state=on]:bg-green-200 data-[state=on]:text-green-800"
                    :pressed="isHeadingActive(2)"
                    value="h2"
                    aria-label="Heading 2"
                    @click="setHeading(2)"
                >
                    H2
                </ToolbarToggleItem>
                <ToolbarToggleItem
                    class="ml-0.5 inline-flex h-[28px] w-[34px] items-center justify-center rounded bg-white font-semibold text-black transition-colors hover:bg-gray-100 data-[state=on]:bg-green-200 data-[state=on]:text-green-800"
                    :pressed="isHeadingActive(3)"
                    value="h3"
                    aria-label="Heading 3"
                    @click="setHeading(3)"
                >
                    H3
                </ToolbarToggleItem>
            </ToolbarToggleGroup>

            <ToolbarSeparator class="mx-2 h-5 w-px bg-gray-200" />

            <!-- Text color -->
            <div class="flex items-center">
                <Icon class="mr-1 h-4 w-4 text-black" name="palette" icon="lucide:palette" />
                <input
                    type="color"
                    class="color-swatch ml-2 h-[28px] w-[28px] cursor-pointer appearance-none rounded border border-gray-200 p-0"
                    @input="setTextColor(($event.target as HTMLInputElement).value)"
                    :value="undefined"
                    aria-label="Text color"
                />
            </div>

            <ToolbarSeparator class="mx-2 h-5 w-px bg-gray-200" />

            <!-- Link / Unlink -->
            <ToolbarToggleGroup type="multiple" aria-label="Linking">
                <ToolbarToggleItem
                    class="ml-0.5 inline-flex h-[28px] w-[28px] items-center justify-center rounded bg-white text-black transition-colors hover:bg-gray-100 data-[state=on]:bg-green-200 data-[state=on]:text-green-800"
                    :pressed="isLink"
                    value="link"
                    aria-label="Link"
                    @click="isLink ? unsetLink() : setLink()"
                >
                    <Icon name="link" class="h-4 w-4" icon="lucide:link" />
                </ToolbarToggleItem>
            </ToolbarToggleGroup>

            <!-- Image -->
            <ToolbarButton
                class="ml-0.5 inline-flex !h-[28px] !w-[28px] items-center justify-center rounded bg-white text-black transition-colors hover:bg-gray-100 data-[state=on]:bg-green-200 data-[state=on]:text-green-800"
                aria-label="Insert image"
                @click="addImage"
            >
                <Icon name="image" class="h-4 w-4" icon="lucide:image" />
            </ToolbarButton>

            <!-- Save (example action) -->
            <ToolbarButton class="bg-green9 hover:bg-green10 ml-auto rounded px-3 text-sm font-medium text-white"> Save </ToolbarButton>
        </ToolbarRoot>

        <!-- Editor -->
        <EditorContent :editor="editor" class="mt-4 min-h-[300px] rounded border p-4" />
    </div>
</template>
