<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';

const props = defineProps<{
    open: boolean;
    id: string;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'confirm', payload: { id: string }): void;
}>();

function handleConfirm() {
    emit('confirm', {
        id: props.id,
    });
    emit('update:open', false);
}
</script>

<template>
    <Dialog v-model:open="props.open">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Delete Quiz</DialogTitle>
                <DialogDescription class="mt-2"> Are you sure you want to delete this quiz? </DialogDescription>
            </DialogHeader>
            <DialogFooter class="mt-4">
                <Button variant="secondary" @click="emit('update:open', false)">Cancel</Button>
                <Button variant="destructive" @click="handleConfirm">Confirm</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
