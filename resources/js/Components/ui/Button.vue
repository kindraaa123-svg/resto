<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { cn } from '@/lib/utils'

const props = defineProps({
  variant: {
    type: String,
    default: 'default',
  },
  size: {
    type: String,
    default: 'default',
  },
  as: {
    type: String,
    default: 'button',
  },
  href: {
    type: String,
    default: null,
  },
  class: {
    type: String,
    default: '',
  },
})

const variants = {
  default: "bg-primary text-primary-foreground shadow-primary/20 hover:bg-primary/90 active:bg-primary/80",
  destructive: "bg-destructive text-destructive-foreground shadow-sm hover:bg-destructive/90",
  outline: "border border-border bg-background shadow-sm hover:bg-accent hover:text-accent-foreground",
  secondary: "bg-secondary text-primary border border-border font-semibold hover:bg-muted active:bg-muted/80",
  ghost: "bg-primary text-primary-foreground p-2 rounded-[4px] shadow-primary/10 hover:bg-primary/90 active:bg-primary/80",
  link: "text-primary underline-offset-4 hover:underline",
}

const sizes = {
  default: "px-6 py-3 text-sm font-semibold",
  sm: "h-8 rounded-md px-3 text-xs",
  lg: "h-12 px-8 text-base",
  icon: "h-9 w-9",
}

const componentClass = computed(() => {
  return cn(
    "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0",
    variants[props.variant],
    sizes[props.size],
    props.class
  )
})

const isLink = computed(() => !!props.href)
const component = computed(() => isLink.value ? Link : props.as)
</script>

<template>
  <component :is="component" :href="href" :class="componentClass">
    <slot />
  </component>
</template>
