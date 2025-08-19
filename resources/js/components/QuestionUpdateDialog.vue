<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { QuestionType } from '@/types/quiz';
import { ref, watch } from 'vue';

const props = defineProps<{
    open: boolean;
    id: string;
    text: string;
    type: QuestionType;
    optionText?: string;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'confirm', payload: { id: string; text: string; optionText?: string }): void;
}>();

const localText = ref(props.text);
const localType = ref(props.type);
const localOption = ref<string | undefined>(props.optionText);

// Keep local values in sync when parent changes
watch(
    () => props.text,
    (val) => (localText.value = val || ''),
);
watch(
    () => props.type,
    (val) => {
        if (val !== QuestionType.MCQ) {
            localOption.value = undefined;
        }
        localType.value = val;
    },
);
watch(
    () => props.optionText,
    (val) => (localOption.value = val || undefined),
);

function handleConfirm() {
    emit('confirm', {
        id: props.id,
        text: localText.value,
        optionText: localOption.value,
    });
    emit('update:open', false);
}
</script>

<template>
    <Dialog v-model:open="props.open">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Edit Question</DialogTitle>
                <DialogDescription class="mt-2">Update the question text and options. Type cannot be changed.</DialogDescription>
            </DialogHeader>

            <div class="mt-4 space-y-4">
                <Input v-model="localText" placeholder="Question Text" />
                <Input v-if="localType === QuestionType.MCQ" v-model="localOption" placeholder="Comma-separated options" />
            </div>

            <DialogFooter class="mt-4">
                <Button variant="secondary" @click="emit('update:open', false)">Cancel</Button>
                <Button @click="handleConfirm">Save</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
