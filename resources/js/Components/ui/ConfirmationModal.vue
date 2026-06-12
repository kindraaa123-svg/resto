<script setup>
import Modal from './Modal.vue'
import Button from './Button.vue'
import { AlertTriangle, Info, HelpCircle } from '@lucide/vue'

const props = defineProps({
    show: Boolean,
    title: {
        type: String,
        default: 'Confirmation'
    },
    message: String,
    type: {
        type: String,
        default: 'warning' // warning, danger, info
    },
    confirmText: {
        type: String,
        default: 'Continue'
    },
    cancelText: {
        type: String,
        default: 'Cancel'
    },
    impact: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['close', 'confirm'])
</script>

<template>
    <Modal :show="show" :title="title" @close="$emit('close')">
        <div class="flex flex-col gap-6">
            <div class="flex items-start gap-4">
                <div 
                    class="p-3 rounded-2xl shrink-0"
                    :class="{
                        'bg-amber-100 text-amber-600': type === 'warning',
                        'bg-destructive/10 text-destructive': type === 'danger',
                        'bg-primary/10 text-primary': type === 'info',
                    }"
                >
                    <AlertTriangle v-if="type === 'danger' || type === 'warning'" class="w-6 h-6" />
                    <Info v-else-if="type === 'info'" class="w-6 h-6" />
                    <HelpCircle v-else class="w-6 h-6" />
                </div>
                
                <div class="space-y-2">
                    <p class="text-sm text-muted-foreground font-medium leading-relaxed">
                        {{ message }}
                    </p>
                </div>
            </div>

            <!-- Impact Summary -->
            <div v-if="impact.length > 0" class="bg-secondary/30 rounded-2xl p-5 border border-secondary/50 space-y-4">
                <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground flex items-center gap-2">
                    <AlertTriangle class="w-3 h-3 text-destructive" />
                    Data Affected by this Action
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div v-for="item in impact" :key="item.label" class="bg-background/50 p-3 rounded-xl border border-secondary/30 flex items-center justify-between">
                        <span class="text-[10px] font-bold text-muted-foreground uppercase tracking-wider">{{ item.label }}</span>
                        <span class="text-xs font-black text-primary bg-primary/5 px-2 py-0.5 rounded-md">{{ item.count }}</span>
                    </div>
                </div>
                <p class="text-[9px] font-medium text-destructive/70 uppercase tracking-widest leading-relaxed">
                    Note: These related records will be hidden (Soft Delete) or potentially lose their reference.
                </p>
            </div>
        </div>

        <template #footer>
            <Button variant="ghost" @click="$emit('close')" class="rounded-xl font-bold">
                {{ cancelText }}
            </Button>
            <Button 
                @click="$emit('confirm')" 
                :variant="type === 'danger' ? 'destructive' : 'default'"
                class="px-8 rounded-xl font-black uppercase tracking-widest text-xs"
            >
                {{ confirmText }}
            </Button>
        </template>
    </Modal>
</template>
