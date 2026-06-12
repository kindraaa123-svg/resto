<script setup>
import { router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import Button from '@/Components/ui/Button.vue'
import { Languages } from '@lucide/vue'

const page = usePage()
const currentLocale = computed(() => page.props.locale || 'id')

const toggleLanguage = () => {
    const nextLocale = currentLocale.value === 'id' ? 'en' : 'id'
    router.post(route('language.switch', nextLocale), {}, {
        preserveScroll: true,
        preserveState: true
    })
}
</script>

<template>
    <Button 
        variant="secondary" 
        size="sm" 
        @click="toggleLanguage"
        class="h-9 px-3 flex items-center gap-2 uppercase font-bold tracking-wider"
    >
        <Languages class="w-4 h-4" />
        {{ currentLocale }}
    </Button>
</template>
