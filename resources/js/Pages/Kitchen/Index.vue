<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, router, usePage, usePoll } from '@inertiajs/vue3'
import { 
    ChefHat, 
    CheckCircle2, 
    Clock, 
    ListTodo,
    BellRing,
    MessageSquareText,
    Volume2,
    UtensilsCrossed,
    Timer,
    AlertCircle,
    User,
    ArrowRight,
    History as HistoryIcon
} from '@lucide/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { cn } from '@/lib/utils'

const page = usePage()
const props = defineProps({
    pendingOrders: Array,
    completedOrders: Array,
    totalPending: Number,
    currentLimit: Number,
    errors: Object,
    auth: Object,
    ziggy: Object,
    system: Object,
    locale: String,
    translations: Object,
})

const ui = computed(() => page.props.translations?.ui || {})

// Polling every 5 seconds to keep the queue fresh
usePoll(5000, {
    only: ['pendingOrders', 'completedOrders', 'totalPending'],
    data: { limit: props.currentLimit },
})

const loadMore = () => {
    router.get(route('kitchen.index'), { limit: props.currentLimit + 10 }, {
        preserveScroll: true,
        preserveState: true,
        only: ['pendingOrders', 'totalPending', 'currentLimit']
    })
}

const markAsReady = (orderId) => {
    router.post(route('kitchen.complete', orderId), {}, {
        preserveScroll: true
    })
}

const startCooking = (orderId) => {
    router.post(route('kitchen.start', orderId), {}, {
        preserveScroll: true
    })
}

const speakOrder = (order) => {
    const synth = window.speechSynthesis
    if (!synth) {
        alert('Text-to-speech not supported in this browser.')
        return
    }

    synth.cancel()
    const currentLocale = page.props.locale === 'id' ? 'id-ID' : 'en-US'
    
    let text = ''
    if (page.props.locale === 'id') {
        text = `Pesanan baru untuk meja ${order.tableName}. `
        order.items.forEach(item => {
            text += `${item.quantity} porsi ${item.name}. `
            if (item.note) text += `Catatan item: ${item.note}. `
        })
        if (order.note) text += `Catatan pesanan: ${order.note}.`
    } else {
        text = `New order for table ${order.tableName}. `
        order.items.forEach(item => {
            text += `${item.quantity} quantity of ${item.name}. `
            if (item.note) text += `Item note: ${item.note}. `
        })
        if (order.note) text += `Order note: ${order.note}.`
    }

    const utterance = new SpeechSynthesisUtterance(text)
    utterance.lang = currentLocale
    utterance.rate = 0.9
    utterance.pitch = 1
    synth.speak(utterance)
}

const activeMobileTab = ref('active') // 'active' or 'history'

const getWaitTime = (orderTime) => {
    let start;
    const timeVal = Number(orderTime);
    if (!isNaN(timeVal) && timeVal > 0) {
        // If it's a unix timestamp (seconds), multiply by 1000 for JS
        start = new Date(timeVal < 10000000000 ? timeVal * 1000 : timeVal);
    } else {
        start = new Date(orderTime);
    }
    
    if (isNaN(start.getTime())) return 0;

    const now = new Date()
    const diff = Math.floor((now - start) / 1000 / 60)
    return Math.max(0, diff)
}
</script>

<template>
    <AppLayout>
        <Head :title="ui.kitchen_dashboard || 'Kitchen Dashboard'" />

        <div class="flex flex-col h-screen -mx-4 -mt-4 lg:mx-0 lg:mt-0 bg-background overflow-hidden font-sans">
            <!-- MODERN KITCHEN HEADER -->
            <header class="bg-[#076A68] text-white px-6 py-6 lg:px-10 flex items-center justify-between shrink-0 shadow-xl shadow-primary/10 relative z-20">
                <div class="flex items-center gap-6">
                    <div class="w-14 h-14 rounded-[20px] bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center">
                        <ChefHat class="w-8 h-8 text-[#FCCC4B]" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-black tracking-tighter uppercase leading-none">{{ ui.cooking_queue || 'Kitchen Display System' }}</h1>
                        <div class="flex items-center gap-3 mt-1.5">
                            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-white/60">Live Production Board</span>
                            <span class="w-1.5 h-1.5 rounded-full bg-[#FCCC4B] animate-pulse"></span>
                            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-[#FCCC4B]">{{ pendingOrders.length }} Tickets</span>
                        </div>
                    </div>
                </div>

                <!-- MOBILE TABS -->
                <div class="lg:hidden flex bg-white/10 p-1 rounded-2xl w-48 border border-white/10 backdrop-blur-sm">
                    <button 
                        @click="activeMobileTab = 'active'"
                        :class="cn('flex-1 py-2 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all', activeMobileTab === 'active' ? 'bg-[#FCCC4B] text-[#076A68] shadow-lg' : 'text-white/60')"
                    >
                        Active
                    </button>
                    <button 
                        @click="activeMobileTab = 'history'"
                        :class="cn('flex-1 py-2 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all', activeMobileTab === 'history' ? 'bg-[#FCCC4B] text-[#076A68] shadow-lg' : 'text-white/60')"
                    >
                        History
                    </button>
                </div>

                <!-- DESKTOP TOOLS -->
                <div class="hidden lg:flex items-center gap-6">
                    <div class="flex flex-col items-end">
                        <p class="text-[10px] font-black text-white/50 uppercase tracking-widest">{{ new Date().toLocaleDateString($page.props.locale === 'id' ? 'id-ID' : 'en-US', { weekday: 'short', day: 'numeric', month: 'short' }) }}</p>
                        <p class="text-lg font-black tracking-tighter">SHIFT 01</p>
                    </div>
                </div>
            </header>

            <!-- KITCHEN BOARD -->
            <div class="flex-1 flex flex-col lg:flex-row overflow-hidden p-6 lg:p-10 gap-10">
                
                <!-- PRODUCTION TICKETS (CENTER) -->
                <div 
                    :class="cn(
                        'flex-1 flex flex-col gap-8 overflow-hidden transition-all',
                        activeMobileTab === 'active' ? 'flex' : 'hidden lg:flex'
                    )"
                >
                    <div class="flex-1 overflow-y-auto no-scrollbar pb-24">
                        <div v-if="pendingOrders.length === 0" class="w-full h-full flex flex-col items-center justify-center text-muted-foreground/20 space-y-6 py-24 bg-white rounded-[40px] border-2 border-dashed border-border">
                            <UtensilsCrossed class="w-24 h-24" />
                            <div class="text-center space-y-1">
                                <h3 class="text-xl font-black uppercase tracking-tighter">Kitchen is Clear</h3>
                                <p class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Waiting for new incoming orders...</p>
                            </div>
                        </div>

                        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-2 2xl:grid-cols-3 gap-10 items-start">
                            <div 
                                v-for="order in pendingOrders" 
                                :key="order.id" 
                                class="bg-white rounded-[40px] shadow-2xl shadow-primary/5 border border-border/50 flex flex-col hover:border-[#076A68]/30 transition-all group overflow-hidden"
                            >
                                <!-- High Impact Ticket Header -->
                                <div class="bg-[#F8F9F7] p-8 border-b border-border/30">
                                    <div class="flex justify-between items-start mb-6">
                                        <div class="flex items-center gap-4">
                                            <div class="w-16 h-16 bg-[#076A68] rounded-[24px] flex items-center justify-center text-white shadow-lg shadow-primary/20">
                                                <span class="text-3xl font-black leading-none">{{ order.tableName }}</span>
                                            </div>
                                            <div class="space-y-1">
                                                <h3 class="text-lg font-black text-[#076A68] leading-none uppercase tracking-tighter">{{ order.name || 'GUEST' }}</h3>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[10px] font-black text-muted-foreground/60 uppercase tracking-[0.1em]">#{{ order.id }}</span>
                                                    <span class="w-1 h-1 rounded-full bg-muted-foreground/30"></span>
                                                    <span class="text-[10px] font-black text-muted-foreground/60 uppercase tracking-[0.1em]">{{ order.orderType }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <button @click="speakOrder(order)" class="w-10 h-10 rounded-xl bg-white border border-border flex items-center justify-center text-[#076A68] hover:bg-[#076A68] hover:text-white transition-all shadow-sm">
                                            <Volume2 class="w-5 h-5" />
                                        </button>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div :class="cn(
                                                'px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-[0.15em] flex items-center gap-2',
                                                getWaitTime(order.orderTime) > 15 ? 'bg-destructive/10 text-destructive animate-pulse' : 'bg-[#076A68]/10 text-[#076A68]'
                                            )">
                                                <Timer class="w-4 h-4" />
                                                {{ getWaitTime(order.orderTime) }} MINS AGO
                                            </div>
                                        </div>
                                        <div class="text-[10px] font-black text-muted-foreground uppercase tracking-widest">
                                            {{ new Date(order.orderTime).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Order Content -->
                                <div class="p-8 space-y-6">
                                    <div v-for="item in order.items" :key="item.id" class="flex gap-6 pb-6 border-b border-border/30 last:border-0 last:pb-0">
                                        <div class="w-14 h-14 rounded-2xl bg-[#076A68] flex items-center justify-center text-2xl font-black text-white shrink-0 shadow-lg shadow-primary/10">
                                            {{ item.quantity }}
                                        </div>
                                        
                                        <div class="flex-1 min-w-0 pt-1">
                                            <h4 class="text-xl font-black text-foreground uppercase leading-tight tracking-tighter">{{ item.name }}</h4>
                                            
                                            <div v-if="item.addons?.length" class="flex flex-wrap gap-2 mt-3">
                                                <span v-for="addon in item.addons" :key="addon" class="px-2 py-1 bg-[#FCCC4B]/20 text-[#076A68] text-[9px] font-black uppercase tracking-wider rounded-md border border-[#FCCC4B]/30">
                                                    + {{ addon }}
                                                </span>
                                            </div>

                                            <div v-if="item.note" class="mt-3 bg-[#FCCC4B]/10 p-3 rounded-2xl border border-[#FCCC4B]/20">
                                                <div class="flex items-center gap-2 text-[#076A68] mb-1">
                                                    <MessageSquareText class="w-3.5 h-3.5" />
                                                    <span class="text-[9px] font-black uppercase tracking-widest">Note</span>
                                                </div>
                                                <p class="text-xs font-bold text-[#076A68] italic">"{{ item.note }}"</p>
                                            </div>

                                            <!-- Package Sub-items -->
                                            <div v-if="item.is_package" class="mt-4 grid grid-cols-1 gap-3 pl-4 border-l-4 border-[#076A68]/20">
                                                <div v-for="sub in item.sub_items" :key="sub.name" class="space-y-1.5">
                                                    <div class="flex items-center gap-2">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-[#076A68]"></span>
                                                        <span class="text-[11px] font-black text-foreground uppercase tracking-tight">{{ sub.quantity }}x {{ sub.name }}</span>
                                                    </div>
                                                    <div v-if="sub.addons?.length" class="flex flex-wrap gap-1.5 ml-4">
                                                        <span v-for="addon in sub.addons" :key="addon" class="text-[9px] font-bold text-muted-foreground uppercase tracking-widest">
                                                            + {{ addon }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Global Order Note -->
                                    <div v-if="order.note" class="mt-6 p-6 rounded-[32px] bg-destructive/10 border-2 border-destructive/20 relative overflow-hidden group/note">
                                        <div class="absolute top-0 left-0 w-2 h-full bg-destructive transition-all group-hover/note:w-3"></div>
                                        <div class="flex items-center gap-3 text-destructive mb-3">
                                            <AlertCircle class="w-5 h-5" />
                                            <span class="text-xs font-black uppercase tracking-[0.2em]">Priority Instruction</span>
                                        </div>
                                        <p class="text-sm font-black text-destructive leading-tight italic uppercase tracking-tighter">"{{ order.note }}"</p>
                                    </div>
                                </div>

                                <!-- Interaction Area -->
                                <div class="p-8 pt-0 mt-auto shrink-0">
                                    <button 
                                        v-if="order.status === 'Cooking'"
                                        @click="markAsReady(order.id)"
                                        class="w-full h-16 bg-[#30C55D] text-white rounded-[24px] flex items-center justify-center gap-4 transition-all active:scale-[0.98] hover:shadow-2xl hover:shadow-[#30C55D]/30 group/btn font-black uppercase tracking-[0.2em] text-xs"
                                    >
                                        {{ ui.mark_as_ready || 'Confirm Served' }}
                                        <CheckCircle2 class="w-6 h-6 group-hover/btn:scale-110 transition-transform" />
                                    </button>
                                    <button 
                                        v-else
                                        @click="startCooking(order.id)"
                                        class="w-full h-16 bg-[#FCCC4B] text-[#076A68] rounded-[24px] flex items-center justify-center gap-4 transition-all active:scale-[0.98] hover:shadow-2xl hover:shadow-[#FCCC4B]/30 group/btn font-black uppercase tracking-[0.2em] text-xs shadow-xl shadow-[#FCCC4B]/10 border-2 border-[#FCCC4B]"
                                    >
                                        {{ ui.start_cooking || 'Begin Production' }}
                                        <ChefHat class="w-6 h-6 group-hover/btn:rotate-12 transition-transform" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Load More Button -->
                        <div v-if="totalPending > pendingOrders.length" class="mt-16 flex justify-center">
                            <button @click="loadMore" class="px-12 py-5 bg-white border border-border text-[#076A68] font-black uppercase tracking-[0.2em] text-[11px] rounded-[32px] hover:bg-[#076A68] hover:text-white transition-all shadow-xl shadow-primary/5 active:scale-95">
                                Show {{ totalPending - pendingOrders.length }} More Tickets
                            </button>
                        </div>
                    </div>
                </div>

                <!-- SIDEBAR HISTORY (RIGHT) -->
                <aside 
                    :class="cn(
                        'w-[400px] flex flex-col gap-8 overflow-hidden transition-all shrink-0',
                        activeMobileTab === 'history' ? 'flex w-full' : 'hidden lg:flex'
                    )"
                >
                    <div class="flex items-center justify-between shrink-0 px-2">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-[#FCCC4B] rounded-xl text-[#076A68]">
                                <HistoryIcon class="w-5 h-5" />
                            </div>
                            <h2 class="text-sm font-black uppercase tracking-[0.2em] text-foreground">{{ ui.served_today || 'Recent History' }}</h2>
                        </div>
                        <span class="text-[10px] font-black text-muted-foreground bg-muted px-3 py-1 rounded-full uppercase tracking-widest">{{ completedOrders.length }} Items</span>
                    </div>

                    <div class="flex-1 overflow-y-auto no-scrollbar space-y-6 pb-24 px-1">
                        <div v-if="completedOrders.length === 0" class="h-full flex flex-col items-center justify-center text-muted-foreground/10 py-32 bg-white rounded-[40px] border-2 border-dashed border-border">
                            <Clock class="w-16 h-16" />
                            <p class="text-[10px] font-black uppercase tracking-widest mt-4">History is empty</p>
                        </div>

                        <div 
                            v-for="order in completedOrders" 
                            :key="order.id"
                            class="bg-white p-6 rounded-[32px] border border-border/50 shadow-sm hover:border-[#076A68]/30 transition-all group relative overflow-hidden"
                        >
                            <div class="absolute top-0 right-0 w-16 h-16 bg-[#30C55D]/5 rounded-bl-[40px] flex items-center justify-end p-4">
                                <CheckCircle2 class="w-5 h-5 text-[#30C55D] opacity-40" />
                            </div>

                            <div class="flex gap-5 items-start mb-6">
                                <div class="w-14 h-14 bg-[#076A68]/5 text-[#076A68] rounded-2xl flex items-center justify-center text-xl font-black shrink-0">
                                    {{ order.tableName }}
                                </div>
                                <div class="pt-1">
                                    <h4 class="text-sm font-black text-foreground uppercase tracking-tight leading-none mb-1.5">{{ order.name || 'GUEST' }}</h4>
                                    <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">{{ order.items_count || order.items?.length }} Items Ordered</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between pt-4 border-t border-border/50">
                                <div class="flex items-center gap-2 text-[10px] font-black text-muted-foreground/60 uppercase tracking-widest">
                                    <Timer class="w-3.5 h-3.5" /> 
                                    <span>Served at {{ new Date(order.completionTime).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</span>
                                </div>
                                <div class="px-3 py-1 bg-[#30C55D]/10 text-[#30C55D] text-[9px] font-black uppercase tracking-widest rounded-lg">
                                    Completed
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>

            </div>
        </div>
    </AppLayout>
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
