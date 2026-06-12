<script setup>
import { onMounted, onUnmounted } from 'vue'
import { X } from '@lucide/vue'

const props = defineProps({
    show: Boolean,
    title: String,
    persistent: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['close'])

const close = () => {
    if (!props.persistent) {
        emit('close')
    }
}

const onKeyDown = (e) => {
    if (e.key === 'Escape' && props.show && !props.persistent) {
        close()
    }
}

onMounted(() => window.addEventListener('keydown', onKeyDown))
onUnmounted(() => window.removeEventListener('keydown', onKeyDown))
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-6 lg:p-10">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-primary/20 backdrop-blur-sm transition-opacity" @click="close"></div>
        
        <!-- Modal Content -->
        <div class="relative bg-card w-full max-w-3xl max-h-full sm:max-h-[90vh] flex flex-col rounded-[1.5rem] sm:rounded-[2.5rem] shadow-2xl border border-secondary overflow-hidden animate-in fade-in zoom-in duration-300">
            <!-- Header -->
            <div class="flex items-center justify-between p-4 lg:p-6 border-b border-secondary/50 shrink-0 bg-white z-10">
                <h3 class="text-base lg:text-xl font-black text-primary uppercase tracking-tight">{{ title }}</h3>
                <button v-if="!persistent" @click="close" class="p-2 hover:bg-secondary rounded-xl transition-colors text-muted-foreground hover:text-primary">
                    <X class="w-5 h-5 lg:w-6 lg:h-6" />
                </button>
            </div>
            
            <!-- Body -->
            <div class="p-4 lg:p-8 overflow-y-auto no-scrollbar flex-1 min-h-0 bg-white/50">
                <slot />
            </div>

            <!-- Footer -->
            <div v-if="$slots.footer" class="p-4 lg:p-6 bg-secondary/10 border-t border-secondary/50 flex flex-col sm:flex-row justify-end gap-3 shrink-0">
                <slot name="footer" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
