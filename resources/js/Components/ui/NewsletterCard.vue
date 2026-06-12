<script setup>
import { computed } from 'vue'
import Button from '@/Components/ui/Button.vue'
import { Send } from '@lucide/vue'

defineProps({
    title: {
        type: String,
        default: 'Join our Newsletter'
    },
    description: {
        type: String,
        default: 'Get latest updates about our new menu and special promotions.'
    },
    buttonText: {
        type: String,
        default: 'Subscribe Now'
    }
})

const emit = defineEmits(['subscribe'])
</script>

<template>
    <div class="bg-primary rounded-lg p-6 shadow-[0_4px_12px_rgba(22,101,143,0.2)] flex flex-col gap-4">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-white/10 rounded-lg">
                <Send class="w-6 h-6 text-white" />
            </div>
            <h3 class="text-xl font-bold text-white">{{ title }}</h3>
        </div>
        
        <p class="text-xs font-normal text-[#A8D5E2] leading-relaxed">
            {{ description }}
        </p>

        <Button 
            variant="secondary" 
            class="mt-2 bg-white text-primary hover:bg-[#F5F5F5] font-semibold border-none"
            @click="$emit('subscribe')"
        >
            {{ buttonText }}
        </Button>
    </div>
</template>
