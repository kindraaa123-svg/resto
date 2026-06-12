<script setup>
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const ui = computed(() => page.props.translations?.ui || {})
const systemName = computed(() => page.props.system?.name || 'POS System')
const currentYear = new Date().getFullYear()
</script>

<template>
    <footer class="w-full py-6 px-8 border-t border-secondary/50 flex flex-col items-center justify-center bg-white/20 backdrop-blur-sm mt-auto">
        <p class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground text-center">
            &copy; {{ currentYear }} {{ systemName }} {{ ui.all_rights_reserved || 'All Rights Reserved' }}
        </p>
    </footer>
</template>
