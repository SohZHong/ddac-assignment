<template>
  <SelectValue v-bind="$attrs" :class="cn('pointer-events-none', props.class)">
    <slot />
  </SelectValue>
</template>

<script setup lang="ts">
import { SelectValue } from 'reka-ui'
import { cn } from '@/lib/utils'

const props = defineProps<{
  class?: string
  placeholder?: string
}>()
</script>
