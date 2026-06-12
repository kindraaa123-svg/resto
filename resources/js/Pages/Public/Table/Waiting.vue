<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { 
    Clock, 
    CheckCircle2, 
    Store,
    Wallet,
    CreditCard,
    AlertCircle
} from '@lucide/vue'
import LanguageToggle from '@/Components/shared/LanguageToggle.vue'

const page = usePage()
const props = defineProps({
    table: Object,
    orderId: String,
    expiresAt: Number,
    subtotal: Number,
    tax: Number,
    totalPrice: [Number, String],
    paymentMethod: String,
    error: String,
    status: String,
})

const ui = computed(() => page.props.translations?.ui || {})

const timeRemaining = ref(0)
const timer = ref(null)
const isPaid = ref(['cooking', 'served', 'completed'].includes(props.status))
const isSimulating = ref(false)
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

const checkStatus = async () => {
    try {
        const response = await fetch(`/table/${props.table.barcode}/status/${props.orderId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        const data = await response.json()
        
        if (['cooking', 'served', 'completed'].includes(data.status)) {
            isPaid.value = true
            clearInterval(timer.value)
        } else if (data.status === 'cancelled') {
            clearInterval(timer.value)
            window.location.href = route('public.table.menu', { barcode: props.table.barcode })
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
            cancelOrder(true) // Pass a flag indicating it's an auto-cancel
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
    if (!props.error && !isPaid.value) {
        startTimer()
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
    <Head :title="ui.payment_status || 'Payment Status'" />

    <div class="min-h-screen bg-background flex flex-col justify-center items-center px-4 lg:px-6 py-8 lg:py-12 relative overflow-hidden max-w-md mx-auto shadow-2xl">
        <!-- Floating Language Toggle -->
        <div class="absolute top-4 right-4 lg:top-6 lg:right-6 z-50">
            <LanguageToggle />
        </div>

        <!-- Background patterns -->
        <div class="absolute inset-0 z-0 opacity-[0.03] pointer-events-none">
            <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-primary rounded-full blur-[100px] lg:blur-[120px]"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-primary rounded-full blur-[100px] lg:blur-[120px]"></div>
        </div>

        <div class="w-full text-center space-y-8 lg:space-y-10 z-10 pb-16">
            <!-- Error State -->
            <div v-if="error || (!isPaid && timeRemaining === 0)" class="space-y-6 lg:space-y-8">
                <div class="relative mx-auto w-24 h-24 lg:w-32 lg:h-32">
                    <div class="absolute inset-0 bg-destructive/10 rounded-[2rem] lg:rounded-[2.5rem] rotate-6 transform scale-110"></div>
                    <div class="absolute inset-0 bg-secondary rounded-[2rem] lg:rounded-[2.5rem] -rotate-3 transform"></div>
                    <div class="relative w-full h-full bg-destructive rounded-[2rem] lg:rounded-[2.5rem] flex items-center justify-center text-destructive-foreground shadow-2xl shadow-destructive/20">
                        <AlertCircle class="w-10 h-10 lg:w-14 lg:h-14" stroke-width="2.5" />
                    </div>
                </div>
                <div class="space-y-3 lg:space-y-4 px-2">
                    <h1 class="text-3xl lg:text-4xl font-black tracking-tighter text-destructive uppercase leading-tight">{{ ui.session_expired || 'Session Expired' }}</h1>
                    <p class="text-xs lg:text-sm font-bold text-muted-foreground uppercase tracking-widest leading-relaxed">
                        {{ error || ui.session_expired_msg || 'Your payment session has expired. Please create a new order.' }}
                    </p>
                </div>
                <button 
                    @click="goHome"
                    class="w-full inline-flex items-center justify-center gap-2 lg:gap-3 rounded-xl lg:rounded-2xl bg-card border border-secondary px-6 py-3.5 lg:px-8 lg:py-4 text-[10px] lg:text-xs font-black uppercase tracking-[0.2em] text-primary hover:bg-secondary transition-all"
                >
                    {{ ui.back_to_menu || 'Back to Menu' }}
                </button>
            </div>

            <!-- Success State -->
            <div v-else-if="isPaid" class="space-y-6 lg:space-y-8 animate-in fade-in zoom-in duration-500">
                <div class="relative mx-auto w-24 h-24 lg:w-32 lg:h-32">
                    <div class="absolute inset-0 bg-green-500/10 rounded-[2rem] lg:rounded-[2.5rem] rotate-6 transform scale-110"></div>
                    <div class="absolute inset-0 bg-secondary rounded-[2rem] lg:rounded-[2.5rem] -rotate-3 transform"></div>
                    <div class="relative w-full h-full bg-green-500 rounded-[2rem] lg:rounded-[2.5rem] flex items-center justify-center text-white shadow-2xl shadow-green-500/20">
                        <CheckCircle2 class="w-10 h-10 lg:w-14 lg:h-14" stroke-width="2.5" />
                    </div>
                </div>
                <div class="space-y-3 lg:space-y-4 px-2">
                    <p class="text-[9px] lg:text-[10px] font-black uppercase tracking-[0.4em] text-green-500/60">{{ ui.success || 'Success' }}</p>
                    <h1 class="text-3xl lg:text-4xl font-black tracking-tighter text-green-500 uppercase leading-tight">{{ ui.payment_received || 'Payment Received' }}</h1>
                    <div class="h-1 w-16 lg:w-20 bg-green-500/20 mx-auto rounded-full"></div>
                    <p class="text-xs lg:text-sm font-bold text-muted-foreground uppercase tracking-widest leading-relaxed">
                        {{ ui.meal_enjoy || 'Your order is being prepared by our kitchen. Enjoy your meal!' }}
                    </p>
                </div>
                <button 
                    @click="goHome"
                    class="w-full inline-flex items-center justify-center gap-2 lg:gap-3 rounded-xl lg:rounded-2xl bg-green-500 px-6 py-3.5 lg:px-8 lg:py-4 text-[10px] lg:text-xs font-black uppercase tracking-[0.2em] text-white shadow-lg shadow-green-500/20 hover:scale-105 active:scale-95 transition-all"
                >
                    {{ ui.new_order || 'New Order' }}
                </button>
            </div>

            <!-- Waiting State -->
            <div v-else class="space-y-6 lg:space-y-8">
                <div class="relative mx-auto w-24 h-24 lg:w-32 lg:h-32 animate-pulse">
                    <div class="absolute inset-0 bg-primary/10 rounded-[2rem] lg:rounded-[2.5rem] rotate-6 transform scale-110"></div>
                    <div class="absolute inset-0 bg-secondary rounded-[2rem] lg:rounded-[2.5rem] -rotate-3 transform"></div>
                    <div class="relative w-full h-full bg-primary rounded-[2rem] lg:rounded-[2.5rem] flex items-center justify-center text-primary-foreground shadow-2xl shadow-primary/20">
                        <Clock class="w-10 h-10 lg:w-14 lg:h-14" stroke-width="2.5" />
                    </div>
                </div>
                
                <div class="space-y-3 lg:space-y-4">
                    <p class="text-[9px] lg:text-[10px] font-black uppercase tracking-[0.4em] text-primary/60">{{ ui.action_required || 'Action Required' }}</p>
                    <h1 v-if="paymentMethod === 'Cashier Payment'" class="text-3xl lg:text-4xl font-black tracking-tighter text-primary uppercase leading-tight px-2">
                        {{ ui.pay_at_cashier_now || 'Please Pay at Cashier' }}
                    </h1>
                    <h1 v-else class="text-3xl lg:text-4xl font-black tracking-tighter text-primary uppercase leading-tight px-2">
                        {{ ui.scan_to_pay || 'Scan QRIS to Pay' }}
                    </h1>
                    
                    <div class="bg-card border border-secondary p-4 lg:p-6 rounded-[2rem] lg:rounded-3xl inline-block mt-2 lg:mt-4 w-full">
                        <div class="space-y-2.5 lg:space-y-3 mb-4">
                            <div class="flex justify-between items-center">
                                <span class="text-[9px] lg:text-[10px] font-black uppercase tracking-widest text-muted-foreground">{{ ui.subtotal || 'Subtotal' }}</span>
                                <span class="text-[11px] lg:text-xs font-black text-muted-foreground">{{ formatPrice(subtotal) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-[9px] lg:text-[10px] font-black uppercase tracking-widest text-muted-foreground">{{ ui.tax || 'Tax (11%)' }}</span>
                                <span class="text-[11px] lg:text-xs font-black text-muted-foreground">{{ formatPrice(tax) }}</span>
                            </div>
                            <div class="h-px w-full bg-secondary/50"></div>
                            <div class="flex justify-between items-center">
                                <span class="text-[11px] lg:text-xs font-black uppercase tracking-widest text-primary">{{ ui.total_payment || 'Total' }}</span>
                                <span class="text-xl lg:text-2xl font-black text-primary tracking-tighter">{{ formatPrice(totalPrice) }}</span>
                            </div>
                        </div>
                        <div class="h-px w-full bg-secondary mb-4"></div>
                        <div class="flex items-center justify-center gap-3 lg:gap-4 text-primary">
                            <span class="text-4xl lg:text-5xl font-black tracking-tighter tabular-nums">{{ String(minutes).padStart(2, '0') }}</span>
                            <span class="text-2xl lg:text-3xl font-black animate-pulse">:</span>
                            <span class="text-4xl lg:text-5xl font-black tracking-tighter tabular-nums">{{ String(seconds).padStart(2, '0') }}</span>
                        </div>
                    </div>
                    
                    <button 
                        @click="cancelOrder"
                        :disabled="isCancelling"
                        class="w-full inline-flex items-center justify-center gap-2 lg:gap-3 rounded-xl lg:rounded-2xl bg-destructive/10 border border-destructive/20 px-6 py-3.5 lg:px-8 lg:py-4 text-[10px] lg:text-xs font-black uppercase tracking-[0.2em] text-destructive hover:bg-destructive hover:text-white transition-all disabled:opacity-50"
                    >
                        {{ isCancelling ? (ui.cancelling || 'Cancelling...') : (ui.cancel_order || 'Cancel Order') }}
                    </button>
                    
                    <div v-if="paymentMethod === 'QRIS' && snapToken" id="snap-container" class="w-full mt-4 min-h-[350px] lg:min-h-[400px] border border-secondary rounded-2xl lg:rounded-3xl overflow-hidden bg-white"></div>
                </div>
            </div>
        </div>
        
        <!-- Global Footer -->
        <footer class="absolute bottom-0 w-full py-6 px-4 lg:py-8 lg:px-8 flex flex-col items-center justify-center gap-4 z-10">
            <p class="text-[8px] lg:text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground text-center">
                &copy; {{ new Date().getFullYear() }} {{ $page.props.system?.name || 'Joyi' }} {{ ui.all_rights_reserved || 'All Rights Reserved' }}
            </p>
        </footer>
    </div>
</template>
