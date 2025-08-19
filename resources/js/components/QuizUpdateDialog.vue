<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { ref, watch } from 'vue';

const props = defineProps<{
    open: boolean;
    id: string;
    title: string;
    description?: string;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'confirm', payload: { id: string; title: string; description: string }): void;
}>();

const localTitle = ref(props.title);
const localDescription = ref(props.description || '');

// Keep local values in sync when parent changes
watch(
    () => props.title,
    (val) => (localTitle.value = val || ''),
);
watch(
    () => props.description,
    (val) => (localDescription.value = val || ''),
);

function handleConfirm() {
    emit('confirm', {
        id: props.id,
        title: localTitle.value,
        description: localDescription.value,
    });
    emit('update:open', false);
}
</script>

<template>
    <Dialog v-model:open="props.open">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Edit Quiz</DialogTitle>
                <DialogDescription class="mt-2"> Update the title and description of this quiz. </DialogDescription>
            </DialogHeader>

            <div class="mt-4 space-y-4">
                <Input v-model="localTitle" placeholder="Quiz Title" />
                <Input v-model="localDescription" placeholder="Description" />
            </div>

            <DialogFooter class="mt-4">
                <Button variant="secondary" @click="emit('update:open', false)">Cancel</Button>
                <Button @click="handleConfirm">Save</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
