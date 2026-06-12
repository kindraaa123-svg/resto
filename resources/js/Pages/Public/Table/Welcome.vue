<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3'
import { Utensils, ArrowRight } from '@lucide/vue'
import LanguageToggle from '@/Components/shared/LanguageToggle.vue'
import { computed } from 'vue'

const page = usePage()
const props = defineProps({
    table: Object,
})

const ui = computed(() => page.props.translations?.ui || {})
</script>

<template>
    <Head :title="ui.welcome || 'Welcome'" />

    <div class="min-h-screen bg-background flex flex-col justify-center items-center px-6 py-12 relative overflow-hidden">
        <!-- Floating Language Toggle -->
        <div class="absolute top-6 right-6 z-50">
            <LanguageToggle />
        </div>

        <!-- Background patterns -->
        <div class="absolute inset-0 z-0 opacity-[0.03] pointer-events-none">
            <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-primary rounded-full blur-[120px]"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-primary rounded-full blur-[120px]"></div>
        </div>

        <div class="max-w-md w-full text-center space-y-12 z-10">
            <!-- App Logo/Icon -->
            <div class="relative mx-auto w-32 h-32">
                <div class="absolute inset-0 bg-primary/10 rounded-[2.5rem] rotate-6 transform scale-110"></div>
                <div class="absolute inset-0 bg-secondary rounded-[2.5rem] -rotate-3 transform"></div>
                <div class="relative w-full h-full bg-primary rounded-[2.5rem] flex items-center justify-center text-primary-foreground shadow-2xl shadow-primary/20 transition-transform hover:scale-105 duration-500">
                    <Utensils class="w-14 h-14" stroke-width="2.5" />
                </div>
            </div>

            <div class="space-y-4">
                <p class="text-[10px] font-black uppercase tracking-[0.4em] text-primary/60">{{ ui.welcome_to || 'Welcome to' }}</p>
                <h1 class="text-4xl font-black tracking-tighter text-primary uppercase leading-tight sm:text-5xl">
                    {{ $page.props.system?.name || 'POS System' }}
                </h1>
                <div class="h-1 w-20 bg-primary/20 mx-auto rounded-full"></div>
                <div class="space-y-1">
                    <p class="text-sm font-bold text-muted-foreground uppercase tracking-widest leading-relaxed">
                        {{ ui.you_are_at_table || 'You are at Table' }}
                    </p>
                    <p class="text-2xl font-black text-foreground uppercase tracking-tighter">
                        {{ table.name }}
                    </p>
                </div>
            </div>

            <div class="pt-6">
                <Link
                    :href="route('public.table.menu', { barcode: table.barcode })"
                    class="group relative inline-flex items-center justify-center gap-4 rounded-2xl bg-primary px-10 py-5 text-sm font-black uppercase tracking-[0.2em] text-primary-foreground shadow-xl shadow-primary/30 transition-all duration-300 hover:scale-105 active:scale-95"
                >
                    <span>{{ ui.enter_menu || 'Enter Menu' }}</span>
                    <ArrowRight class="w-5 h-5 transition-transform group-hover:translate-x-1" />
                </Link>
            </div>
        </div>

        <!-- Standard Footer -->
        <footer class="absolute bottom-12 text-center w-full">
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground">
                &copy; {{ new Date().getFullYear() }} {{ $page.props.system?.name || 'Joyi' }} {{ ui.all_rights_reserved || 'All Rights Reserved' }}
            </p>
        </footer>
    </div>
</template>
