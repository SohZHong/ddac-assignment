<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { FreeSchedule } from '@/types/schedule';
import DialogDescription from './ui/dialog/DialogDescription.vue';

const props = defineProps<{
    open: boolean;
    schedule: FreeSchedule | null;
}>();
const emit = defineEmits(['close', 'confirm']);

function confirmBooking() {
    emit('confirm', {
        scheduleId: props.schedule?.schedule_id,
        eventId: props.schedule?.id,
        startTime: props.schedule?.start,
        endTime: props.schedule?.end,
    });
}

const daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

function getDayOfWeekLabel(day: number) {
    return daysOfWeek[day] ?? '';
}
</script>

<template>
    <Dialog :open="open" @close="() => emit('close')">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Booking Confirmation</DialogTitle>
                <DialogDescription>Do you wish to proceed with this booking?</DialogDescription>
            </DialogHeader>

            <div v-if="schedule">
                <div class="space-y-1 font-medium">
                    <div class="grid grid-cols-[3fr_7fr] items-center">
                        <span>Day:</span>
                        <span class="font-bold"> {{ getDayOfWeekLabel(schedule.day_of_week) }} </span>
                    </div>
                    <div class="grid grid-cols-[3fr_7fr] items-center">
                        <span>From:</span>
                        <span class="font-bold">{{ new Date(schedule.start).toLocaleString() }}</span>
                    </div>
                    <div class="grid grid-cols-[3fr_7fr] items-center">
                        <span>To:</span>
                        <span class="font-bold">{{ new Date(schedule.end).toLocaleString() }}</span>
                    </div>
                </div>
            </div>

            <DialogFooter>
                <Button variant="secondary" @click="() => emit('close')">Cancel</Button>
                <Button @click="confirmBooking">Confirm</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
