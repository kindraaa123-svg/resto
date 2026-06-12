<script setup>
import { Link } from '@inertiajs/vue3'
import { cn } from '@/lib/utils'

defineProps({
    links: Array,
})
</script>

<template>
    <div v-if="links.length > 3">
        <div class="flex flex-wrap justify-center gap-1">
            <template v-for="(link, key) in links" :key="key">
                <div 
                    v-if="link.url === null" 
                    class="px-4 py-2 text-xs font-bold text-muted-foreground bg-secondary/20 rounded-lg cursor-not-allowed uppercase tracking-widest border border-transparent"
                    v-html="link.label"
                />
                <Link
                    v-else
                    :href="link.url"
                    class="px-4 py-2 text-xs font-bold rounded-lg transition-all uppercase tracking-widest border"
                    :class="cn(
                        link.active 
                            ? 'bg-primary text-primary-foreground border-primary shadow-lg shadow-primary/20' 
                            : 'bg-card text-muted-foreground border-secondary/50 hover:bg-secondary hover:text-primary hover:border-primary/20'
                    )"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>
