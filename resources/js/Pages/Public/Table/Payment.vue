<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { 
    Clock, 
    AlertCircle,
    ChevronLeft
} from '@lucide/vue'
import LanguageToggle from '@/Components/shared/LanguageToggle.vue'

const page = usePage()
const ui = computed(() => page.props.translations?.ui || {})

const props = defineProps({
    table: Object,
    orderId: String,
    items: Array,
    expiresAt: Number,
    subtotal: Number,
    tax: Number,
    totalPrice: [Number, String],
    snapToken: String,
    midtransClientKey: String,
    midtransIsProduction: [String, Boolean],
    error: String,
})

const timeRemaining = ref(0)
const timer = ref(null)
const isCancelling = ref(false)

const formatPrice = (price) => {
    return new Intl.NumberFormat(page.props.locale === 'id' ? 'id-ID' : 'en-US', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(price)
}

const minutes = computed(() => Math.floor(timeRemaining.value / 60))
const seconds = computed(() => timeRemaining.value % 60)

const initMidtrans = () => {
    if (props.snapToken) {
        const script = document.createElement('script')
        const isProd = props.midtransIsProduction === true || props.midtransIsProduction === 'true' || props.midtransIsProduction === '1'
        script.src = isProd 
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js'
        script.setAttribute('data-client-key', props.midtransClientKey || 'SB-Mid-client-YOUR_CLIENT_KEY')
        script.onload = () => {
            if (window.snap) {
                window.snap.embed(props.snapToken, {
                    embedId: 'snap-container',
                    onSuccess: function(result){ checkStatus() },
                    onPending: function(result){ checkStatus() },
                    onError: function(result){ alert("Payment failed!") },
                })
            }
        }
        document.head.appendChild(script)
    }
}

const checkStatus = async () => {
    try {
        const response = await fetch(`/table/${props.table.barcode}/status/${props.orderId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        const data = await response.json()
        
        if (data.status === 'completed' || data.status === 'paid') {
            clearInterval(timer.value)
            // Redirect to waiting page which will show success state
            router.visit(route('public.table.waiting', { barcode: props.table.barcode, order: props.orderId }))
        } else if (data.status === 'cancelled') {
            clearInterval(timer.value)
            router.visit(route('public.table.menu', { barcode: props.table.barcode }))
        }
    } catch (e) {
        console.error('Error checking status', e)
    }
}

const startTimer = () => {
    const updateTimer = () => {
        const now = Math.floor(Date.now() / 1000)
        timeRemaining.value = Math.max(0, props.expiresAt - now)
        
        if (timeRemaining.value === 0) {
            clearInterval(timer.value)
            cancelOrder(true)
        } else if (timeRemaining.value % 5 === 0) {
            // Check status every 5 seconds
            checkStatus()
        }
    }
    
    updateTimer()
    timer.value = setInterval(updateTimer, 1000)
}

const cancelOrder = (isAuto = false) => {
    const msg = ui.value.confirm_cancel_order || 'Are you sure you want to cancel this order?'
    if (isAuto || confirm(msg)) {
        isCancelling.value = true
        router.post(route('public.table.cancel', { barcode: props.table.barcode, order: props.orderId }), {}, {
            onFinish: () => isCancelling.value = false
        })
    }
}

onMounted(() => {
    if (!props.error) {
        startTimer()
        initMidtrans()
    }
})

onUnmounted(() => {
    if (timer.value) clearInterval(timer.value)
})

const goHome = () => {
    router.visit(route('public.table.welcome', { barcode: props.table.barcode }))
}
</script>

<template>
    <Head :title="ui.complete_payment || 'Complete Payment'" />

    <div class="min-h-screen bg-background flex flex-col items-center px-4 py-8 relative overflow-hidden">
        <!-- Background patterns -->
        <div class="absolute inset-0 z-0 opacity-[0.03] pointer-events-none">
            <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-primary rounded-full blur-[120px]"></div>
        </div>

        <div class="max-w-6xl w-full z-10 space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4 bg-white/80 backdrop-blur-md p-4 rounded-3xl border border-secondary/50 shadow-sm">
                <button 
                    @click="cancelOrder(false)"
                    class="p-2 rounded-2xl bg-secondary text-primary hover:bg-destructive hover:text-white transition-all"
                >
                    <ChevronLeft class="w-5 h-5" />
                </button>
                <div class="min-w-0 flex-1">
                    <h1 class="text-xs font-black uppercase tracking-[0.2em] text-primary truncate">{{ ui.complete_payment || 'Complete Payment' }}</h1>
                    <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">{{ ui.table || 'Table' }} {{ table.name }}</p>
                </div>
                <LanguageToggle />
                <div class="text-right pr-2">
                    <p class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">{{ ui.expires_in || 'Expires In' }}</p>
                    <p class="text-sm font-black text-primary tracking-tighter tabular-nums">
                        {{ String(minutes).padStart(2, '0') }}:{{ String(seconds).padStart(2, '0') }}
                    </p>
                </div>
            </div>

            <!-- Error State -->
            <div v-if="error || timeRemaining === 0" class="max-w-md mx-auto space-y-8 py-12 text-center">
                <div class="relative mx-auto w-32 h-32">
                    <div class="absolute inset-0 bg-destructive/10 rounded-[2.5rem] rotate-6 transform scale-110"></div>
                    <div class="absolute inset-0 bg-secondary rounded-[2.5rem] -rotate-3 transform"></div>
                    <div class="relative w-full h-full bg-destructive rounded-[2.5rem] flex items-center justify-center text-destructive-foreground shadow-2xl shadow-destructive/20">
                        <AlertCircle class="w-14 h-14" stroke-width="2.5" />
                    </div>
                </div>
                <div class="space-y-4">
                    <h1 class="text-4xl font-black tracking-tighter text-destructive uppercase leading-tight">{{ ui.session_expired || 'Session Expired' }}</h1>
                    <p class="text-sm font-bold text-muted-foreground uppercase tracking-widest leading-relaxed">
                        {{ error || ui.session_expired_msg || 'Your payment session has expired. Please create a new order.' }}
                    </p>
                </div>
                <button 
                    @click="goHome"
                    class="w-full inline-flex items-center justify-center gap-3 rounded-2xl bg-card border border-secondary px-8 py-4 text-xs font-black uppercase tracking-[0.2em] text-primary hover:bg-secondary transition-all"
                >
                    {{ ui.back_to_menu || 'Back to Menu' }}
                </button>
            </div>

            <!-- Payment Content -->
            <div v-else class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <!-- Left Column: Order Summary -->
                <div class="lg:col-span-5 space-y-6">
                    <div class="bg-white/80 backdrop-blur-md rounded-3xl border border-secondary/50 shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-secondary/50 bg-secondary/30">
                            <h2 class="text-sm font-black uppercase tracking-widest text-primary">{{ ui.order_summary || 'Order Summary' }}</h2>
                            <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-[0.2em] mt-1">#{{ orderId }}</p>
                        </div>
                        
                        <div class="p-6 space-y-4 max-h-[50vh] overflow-y-auto custom-scrollbar">
                            <div v-for="item in items" :key="item.id" class="flex gap-4">
                                <div class="w-16 h-16 rounded-2xl bg-secondary overflow-hidden shrink-0 border border-secondary">
                                    <img v-if="item.image" :src="item.image" :alt="item.name" class="w-full h-full object-cover" />
                                    <div v-else class="w-full h-full flex items-center justify-center text-muted-foreground/50">
                                        <AlertCircle class="w-6 h-6" />
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-start">
                                        <h3 class="text-sm font-black uppercase tracking-tight text-primary truncate">{{ item.name }}</h3>
                                        <p class="text-xs font-black text-primary">{{ formatPrice(item.itemTotalPrice) }}</p>
                                    </div>
                                    <p class="text-[10px] font-bold text-muted-foreground mt-0.5">{{ item.quantity }}x {{ formatPrice(item.price) }}</p>
                                    
                                    <!-- Addons -->
                                    <div v-if="item.addons && item.addons.length > 0" class="mt-2 space-y-1">
                                        <div v-for="addon in item.addons" :key="addon.name" class="flex justify-between items-center text-[9px] font-bold text-muted-foreground/80 uppercase tracking-wider">
                                            <span>+ {{ addon.name }}</span>
                                            <span>{{ formatPrice(addon.price) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 space-y-3 bg-primary/5 border-t border-secondary/50">
                            <div class="flex justify-between items-center text-[10px] font-black uppercase tracking-widest text-muted-foreground/80">
                                <span>{{ ui.subtotal || 'Subtotal' }}</span>
                                <span>{{ formatPrice(subtotal) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-[10px] font-black uppercase tracking-widest text-muted-foreground/80">
                                <span>{{ ui.tax || 'Tax (11%)' }}</span>
                                <span>{{ formatPrice(tax) }}</span>
                            </div>
                            <div class="h-px w-full bg-secondary/50"></div>
                            <div class="flex justify-between items-center text-primary">
                                <span class="text-xs font-black uppercase tracking-[0.2em]">{{ ui.total_payment || 'Total Amount' }}</span>
                                <span class="text-xl font-black tracking-tighter">{{ formatPrice(totalPrice) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Info Alert -->
                    <div class="p-4 bg-primary/5 rounded-2xl border border-primary/10 flex gap-3">
                        <AlertCircle class="w-5 h-5 text-primary shrink-0" />
                        <p class="text-[10px] font-bold text-primary/80 uppercase tracking-widest leading-relaxed">
                            {{ ui.complete_before_timer || 'Please complete your payment before the timer expires.' }}
                        </p>
                    </div>
                </div>

                <!-- Right Column: Payment Container -->
                <div class="lg:col-span-7 space-y-4">
                    <div v-if="!snapToken" class="p-6 bg-destructive/10 text-destructive rounded-3xl border border-destructive/20 text-center">
                        <p class="text-xs font-black uppercase tracking-widest">Snap Token Missing</p>
                        <p class="text-[10px] font-bold mt-1">Failed to generate payment token. Please contact staff or try again.</p>
                    </div>
                    <div id="snap-container" class="w-full min-h-[600px] border border-secondary rounded-3xl overflow-hidden bg-white shadow-xl shadow-primary/5">
                        <!-- Midtrans iframe will be injected here -->
                        <div class="flex items-center justify-center h-64 text-muted-foreground text-xs font-black uppercase tracking-widest animate-pulse">
                            {{ ui.loading_payment || 'Loading Secure Payment...' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Global Footer -->
        <footer class="p-8 mt-auto shrink-0 z-10 text-center">
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground">
                &copy; {{ new Date().getFullYear() }} {{ $page.props.system?.name || 'Joyi' }} {{ ui.all_rights_reserved || 'All Rights Reserved' }}
            </p>
        </footer>
    </div>
</template>
