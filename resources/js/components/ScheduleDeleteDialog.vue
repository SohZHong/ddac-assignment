<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { ref, watch } from 'vue';

const props = defineProps<{
    open: boolean;
    start?: string;
    end?: string;
}>();
const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'confirm'): void;
}>();

const localOpen = ref(props.open);

watch(
    () => props.open,
    (val) => (localOpen.value = val),
);

function handleConfirm() {
    emit('confirm');
    localOpen.value = false;
    emit('update:open', false);
}
</script>

<template>
    <Dialog v-model:open="localOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Delete Availability Slot</DialogTitle>
                <DialogDescription class="mt-2"> Are you sure you want to create this slot? </DialogDescription>
            </DialogHeader>

            <div v-if="start && end">
                <p class="text-sm text-gray-600">{{ new Date(start).toLocaleString() }} — {{ new Date(end).toLocaleString() }}</p>
            </div>

            <DialogFooter>
                <Button variant="secondary" @click="emit('update:open', false)">Cancel</Button>
                <Button variant="destructive" @click="handleConfirm">Confirm</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
