<script setup lang="ts">
import { ToastAction, ToastDescription, ToastProvider, ToastRoot, ToastTitle, ToastViewport } from 'reka-ui';
import { computed, ref } from 'vue';

interface Props {
    title: string;
    description: string;
    actionText?: string;
    onAction?: () => void;
    variant?: 'default' | 'success' | 'destructive';
}

const props = defineProps<Props>();

const open = ref(false);

// Function to show the toast
function showToast() {
    open.value = false;
    setTimeout(() => {
        open.value = true;
    }, 50);
}

// Internal handler for action button
function handleAction() {
    open.value = false;
    if (props.onAction) props.onAction();
}

// Computed classes based on variant
const toastClass = computed(() => {
    switch (props.variant) {
        case 'success':
            return 'bg-green-50 dark:bg-green-900 text-green-900 dark:text-green-50 border-green-200 dark:border-green-800';
        case 'destructive':
            return 'bg-red-50 dark:bg-red-900 text-red-900 dark:text-red-50 border-red-200 dark:border-red-800';
        default:
            return 'bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-50 border border-gray-200 dark:border-gray-700';
    }
});

// Expose method
defineExpose({ showToast });
</script>

<template>
    <ToastProvider>
        <ToastRoot
            v-model:open="open"
            :class="[
                toastClass,
                `data-[state=open]:animate-slideIn data-[state=closed]:animate-hide data-[swipe=end]:animate-swipeOut grid grid-cols-[auto_max-content] items-center gap-x-[15px] rounded-lg border p-[15px] shadow-sm [grid-template-areas:_'title_action'_'description_action'] data-[swipe=cancel]:translate-x-0 data-[swipe=cancel]:transition-[transform_200ms_ease-out] data-[swipe=move]:translate-x-[var(--reka-toast-swipe-move-x)]`,
            ]"
        >
            <ToastTitle class="text-slate12 mb-[5px] text-sm font-medium [grid-area:_title]">
                {{ props.title }}
            </ToastTitle>

            <ToastDescription as-child>
                <div class="text-slate11 m-0 text-xs leading-[1.3] [grid-area:_description]">
                    {{ props.description }}
                </div>
            </ToastDescription>

            <ToastAction :alt-text="props.actionText" v-if="props.actionText" class="[grid-area:_action]" as-child>
                <button
                    @click="handleAction"
                    class="inline-flex items-center justify-center rounded-md bg-gray-200 px-3 py-1 text-xs font-medium hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600"
                >
                    {{ props.actionText }}
                </button>
            </ToastAction>
        </ToastRoot>
        <ToastViewport
            class="fixed right-0 bottom-0 z-[2147483647] m-0 flex w-[390px] max-w-[100vw] list-none flex-col gap-[10px] p-[var(--viewport-padding)] outline-none [--viewport-padding:_25px]"
        />
    </ToastProvider>
</template>
