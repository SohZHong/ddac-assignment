<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { ref, watch } from 'vue';
import { Input } from './ui/input';

const props = defineProps<{
    open: boolean;
}>();
const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'confirm', payload: { file: File | null; notes: string | null }): void;
}>();

const localOpen = ref(props.open);
const notes = ref<string>('');
const selectedFile = ref<File | null>(null);

watch(
    () => props.open,
    (val) => (localOpen.value = val),
);

function handleFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        selectedFile.value = target.files[0];
    }
}

function handleConfirm() {
    emit('confirm', { file: selectedFile.value, notes: notes.value });
    localOpen.value = false;
    emit('update:open', false);
}
</script>

<template>
    <Dialog v-model:open="localOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Upload Consultation Report</DialogTitle>
                <DialogDescription class="mt-2"> Upload a consultation / screening report for this patient </DialogDescription>
            </DialogHeader>

            <!-- File Upload Input -->
            <div class="mt-4 space-y-4">
                <Input type="file" @change="handleFileChange" :multiple="false" />
                <Input type="text" placeholder="Optional notes for the report" v-model="notes" />
            </div>

            <DialogFooter>
                <Button variant="secondary" @click="emit('update:open', false)">Cancel</Button>
                <Button @click="handleConfirm">Confirm</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
