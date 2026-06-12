<script setup>
import { cn } from '@/lib/utils'

defineProps({
  modelValue: [String, Number],
  type: {
    type: String,
    default: 'text',
  },
  placeholder: String,
  class: String,
})

defineEmits(['update:modelValue'])
</script>

<template>
  <input
    :type="type"
    :value="modelValue"
    @input="$emit('update:modelValue', $event.target.value)"
    :placeholder="placeholder"
    :class="cn(
      'flex h-[44px] w-full rounded-md border border-border bg-secondary px-[14px] py-[10px] text-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:ring-primary/10 disabled:cursor-not-allowed disabled:opacity-50',
      $props.class
    )"
  />
</template>
