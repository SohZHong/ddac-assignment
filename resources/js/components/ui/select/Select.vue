<template>
  <SelectRoot v-bind="forwarded">
    <slot />
  </SelectRoot>
</template>

<script setup lang="ts">
import { SelectRoot } from 'reka-ui'
import { useForwardPropsEmits } from 'reka-ui'

const props = defineProps<{
  modelValue?: string
  defaultValue?: string
  open?: boolean
  defaultOpen?: boolean
  dir?: 'ltr' | 'rtl'
  name?: string
  autocomplete?: string
  size?: number
  required?: boolean
  disabled?: boolean
}>()

const emits = defineEmits<{
  'update:modelValue': [value: string]
  'update:open': [open: boolean]
}>()

const forwarded = useForwardPropsEmits(props, emits)
</script>
