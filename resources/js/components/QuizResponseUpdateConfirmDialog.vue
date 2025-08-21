<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { ref, watch } from 'vue';

const props = defineProps<{
    open: boolean;
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
                <DialogTitle>Duplicate Submissions</DialogTitle>
                <DialogDescription class="mt-2"> Do you wish to submit again? Your old response will be overwritten! </DialogDescription>
            </DialogHeader>
            <DialogFooter class="mt-4">
                <Button variant="secondary" @click="emit('update:open', false)">Cancel</Button>
                <Button @click="handleConfirm">Confirm</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
