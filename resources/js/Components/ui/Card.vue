<script setup>
import { cn } from '@/lib/utils'

defineProps({
  class: String,
})
</script>

<template>
  <div :class="cn('rounded-lg border-2 border-border transition-all hover:border-primary/50 hover:shadow-primary/10', $props.class)">
    <div v-if="$slots.header" :class="cn('flex flex-col space-y-1.5 p-4', $props.classHeader)">
      <slot name="header" />
    </div>
    <div :class="cn('p-4 pt-0', $props.classContent)">
      <slot />
    </div>
    <div v-if="$slots.footer" :class="cn('flex items-center p-4 pt-0', $props.classFooter)">
      <slot name="footer" />
    </div>
  </div>
</template>
