<script setup>
import { ref, computed, watch } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import FaceApprovalModal from '@/Components/shared/FaceApprovalModal.vue'
import Button from '@/Components/ui/Button.vue'
import { 
    UtensilsCrossed, 
    CheckCircle2, 
    Clock, 
    LayoutGrid,
    ListTodo,
    CheckSquare,
    UserMinus,
    ChefHat,
    BellRing,
    Filter,
    X,
    XCircle,
    Printer
} from '@lucide/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Input from '@/Components/ui/Input.vue'

const page = usePage()
const props = defineProps({
    orders: Object, // Changed from Array to Object (LengthAwarePaginator)
    branches: Array,
    filters: Object,
    auth: Object,
    ziggy: Object,
    system: Object,
    locale: String,
    translations: Object,
})

const ui = computed(() => page.props.translations?.ui || {})

const filterForm = ref({
    branch_id: props.filters?.branch_id || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
    status: props.filters?.status || '',
})

const applyFilters = () => {
    router.get(route('orders.index'), filterForm.value, { 
        preserveState: true, 
        preserveScroll: true,
        replace: true
    })
}

const clearFilters = () => {
    filterForm.value = { branch_id: '', date_from: '', date_to: '', status: '' }
    // applyFilters will be triggered by watch
}

const printReceipt = (orderId) => {
    window.open(route('api.cashier.receipt', orderId), '_blank')
}

// Auto-filter on change with debounce
let timeout = null
watch(filterForm, () => {
    clearTimeout(timeout)
    timeout = setTimeout(() => {
        applyFilters()
    }, 300)
}, { deep: true })

// Face Approval & Cancel Logic
const showFaceModal = ref(false)
const selectedOrderToCancel = ref(null)

const triggerCancel = (orderId) => {
    selectedOrderToCancel.value = orderId
    showFaceModal.value = true
}

const onCancelApproved = (data) => {
    router.delete(route('orders.destroy', selectedOrderToCancel.value), {
        data: { manager_id: data.managerId },
        preserveScroll: true,
        onSuccess: () => {
            showFaceModal.value = false
            selectedOrderToCancel.value = null
        }
    })
}

const formatPrice = (price) => {
    return new Intl.NumberFormat(page.props.locale === 'id' ? 'id-ID' : 'en-US', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(price || 0)
}

const serveOrder = (orderId) => {
    router.post(route('orders.serve', orderId), {}, {
        preserveScroll: true
    })
}

const makeVacant = (tableId) => {
    if (confirm(ui.value.confirm_vacant || 'Set this table to vacant? This will also close any active sessions.')) {
        router.post(route('tables.vacant', tableId), {}, {
            preserveScroll: true
        })
    }
}

const getStatusColor = (status) => {
    switch (status?.toLowerCase()) {
        case 'pending': return 'bg-amber-50 text-amber-600 border-amber-200'
        case 'cooking': return 'bg-blue-50 text-blue-600 border-blue-200'
        case 'served': return 'bg-green-50 text-green-600 border-green-200'
        case 'cancelled': return 'bg-destructive/10 text-destructive border-destructive/20'
        default: return 'bg-primary/5 text-primary border-primary/20'
    }
}

const isMobileView = ref(false)
const checkViewSize = () => {
    isMobileView.value = window.innerWidth < 1024
}

import { onMounted, onUnmounted } from 'vue'
onMounted(() => {
    checkViewSize()
    window.addEventListener('resize', checkViewSize)
})
onUnmounted(() => {
    window.removeEventListener('resize', checkViewSize)
})
</script>

<template>
    <AppLayout>
        <Head :title="ui.orders_tables || 'Orders Management'" />

        <div class="flex flex-col space-y-6 -mt-4 lg:mt-0 min-h-[calc(100vh-8rem)]">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-2 lg:px-0 shrink-0">
                <div>
                    <h1 class="text-3xl font-black tracking-tight text-primary uppercase">{{ ui.orders || 'All Orders' }}</h1>
                    <p class="text-muted-foreground text-sm font-medium uppercase tracking-widest mt-1">{{ ui.manage_delivery_seating || 'Monitor all historical and active orders' }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-card p-4 rounded-3xl border border-secondary/50 shadow-sm mx-2 lg:mx-0">
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground mb-1 block">Branch</label>
                        <select v-model="filterForm.branch_id" class="w-full h-11 rounded-xl border border-secondary bg-background px-3 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="">All Branches</option>
                            <option v-for="branch in branches" :key="branch.branchid" :value="branch.branchid">
                                {{ branch.branchname }}
                            </option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground mb-1 block">Date From</label>
                        <Input type="date" v-model="filterForm.date_from" class="h-11 rounded-xl" />
                    </div>
                    <div class="flex-1">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground mb-1 block">Date To</label>
                        <Input type="date" v-model="filterForm.date_to" class="h-11 rounded-xl" />
                    </div>
                    <div class="flex-1">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground mb-1 block">Status</label>
                        <select v-model="filterForm.status" class="w-full h-11 px-4 rounded-xl border border-input bg-background text-sm font-bold outline-none focus:ring-1 focus:ring-primary">
                            <option value="">All Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Cooking">Cooking</option>
                            <option value="Served">Served</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="flex items-end shrink-0">
                        <button 
                            v-if="filterForm.branch_id || filterForm.date_from || filterForm.date_to || filterForm.status" 
                            @click="clearFilters" 
                            class="h-11 px-6 rounded-xl bg-secondary text-muted-foreground hover:bg-destructive/10 hover:text-destructive font-black uppercase tracking-widest text-[10px] flex items-center gap-2 transition-all"
                        >
                            <X class="w-4 h-4" /> {{ ui.reset_filters || 'Reset' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-hidden bg-card rounded-3xl lg:rounded-[2rem] border border-secondary/50 shadow-sm flex flex-col mx-2 lg:mx-0">
                
                <div class="flex-1 flex flex-col overflow-hidden">
                    <div class="p-4 lg:p-6 border-b border-secondary/50 bg-secondary/10 flex items-center justify-between shrink-0">
                        <h2 class="text-xs lg:text-sm font-black uppercase tracking-[0.2em] text-primary flex items-center gap-2">
                            <ListTodo class="w-4 h-4 lg:w-5 lg:h-5" />
                            <span>{{ ui.order_history || 'Order History' }}</span>
                        </h2>
                        <span class="text-[9px] lg:text-[10px] font-black text-primary uppercase tracking-widest bg-primary/10 px-2.5 py-1 rounded-lg">
                            {{ orders.total }} {{ ui.total_orders || 'Total' }}
                        </span>
                    </div>

                    <div class="flex-1 overflow-y-auto p-4 lg:p-6 bg-background no-scrollbar">
                        <div v-if="orders.data.length === 0" class="h-full flex flex-col items-center justify-center text-muted-foreground/30 space-y-4 py-20">
                            <UtensilsCrossed class="w-16 h-16 opacity-10" />
                            <p class="text-xs font-black uppercase tracking-widest">{{ ui.no_orders_yet || 'No orders yet' }}</p>
                        </div>

                        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 lg:gap-6">
                            <div 
                                v-for="order in orders.data" 
                                :key="order.id"
                                :class="[
                                    'flex flex-col border border-secondary rounded-3xl p-5 lg:p-8 space-y-8 transition-all bg-white shadow-sm min-h-[500px]'
                                ]"
                            >
                                <div class="flex justify-between items-start border-b border-secondary/50 pb-3">
                                    <div class="min-w-0">
                                        <h3 class="text-lg lg:text-xl font-black uppercase tracking-tight text-primary leading-none mb-1 truncate">{{ order.tableName }}</h3>
                                        <div class="flex flex-col gap-0.5">
                                            <p class="text-[9px] lg:text-[10px] font-bold text-muted-foreground uppercase tracking-widest truncate">#{{ order.id }} &bull; {{ order.customerName || 'Guest' }}</p>
                                            <div class="flex items-center gap-2 text-[8px] lg:text-[9px] font-black text-primary/40 uppercase tracking-tighter">
                                                <span class="flex items-center gap-1"><Clock class="w-3 h-3" /> {{ new Date(order.orderTime).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</span>
                                                <span>&bull;</span>
                                                <span class="flex items-center gap-1">{{ new Date(order.orderTime).toLocaleDateString([], {day: '2-digit', month: 'short', year: 'numeric'}) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <span 
                                        :class="[
                                            'px-2 py-0.5 rounded-lg text-[8px] lg:text-[9px] font-black uppercase tracking-widest border shrink-0',
                                            getStatusColor(order.status)
                                        ]"
                                    >
                                        {{ order.status }}
                                    </span>
                                </div>

                                <div class="flex-1 space-y-2 py-1">
                                    <div v-for="(item, idx) in order.items" :key="idx" class="flex gap-3">
                                        <span class="text-[10px] font-black text-primary bg-secondary w-5 h-5 lg:w-6 lg:h-6 rounded-lg flex items-center justify-center shrink-0 border border-secondary-foreground/5 shadow-inner">{{ item.quantity }}</span>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex justify-between items-start gap-2">
                                                <p class="text-xs lg:text-sm font-black uppercase tracking-tight truncate leading-tight">{{ item.name }}</p>
                                                <span class="text-[10px] font-bold text-primary shrink-0">{{ formatPrice(item.price) }}</span>
                                            </div>
                                            
                                            <!-- Package Info -->
                                            <p v-if="item.packageName" class="text-[8px] font-black text-amber-600 uppercase tracking-widest mt-0.5">📦 {{ item.packageName }}</p>
                                            
                                            <!-- Free Label -->
                                            <span v-if="item.isFree" class="inline-block mt-0.5 px-1.5 py-0.5 rounded bg-green-100 text-green-700 text-[8px] font-black uppercase tracking-widest">Free</span>

                                            <div v-if="item.addons?.length" class="flex flex-wrap gap-1 mt-1">
                                                <span v-for="a in item.addons" :key="a" class="text-[8px] font-bold text-muted-foreground uppercase tracking-tighter bg-secondary/50 px-1.5 py-0.5 rounded border border-secondary/30">+ {{ a }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Order Footer: Totals & Promos -->
                                <div class="pt-3 border-t border-secondary/50 space-y-3">
                                    <div v-if="order.promoName" class="flex items-center gap-2 bg-amber-50 p-2 rounded-xl border border-amber-100">
                                        <div class="p-1 rounded-lg bg-amber-200 text-amber-700"><CheckSquare class="w-3 h-3" /></div>
                                        <div class="min-w-0">
                                            <p class="text-[8px] font-black text-amber-800 uppercase tracking-widest leading-none">{{ order.promoName }}</p>
                                            <p v-if="order.promoDiscount > 0" class="text-[10px] font-bold text-amber-600">- {{ formatPrice(order.promoDiscount) }}</p>
                                        </div>
                                    </div>

                                    <div class="flex justify-between items-end px-1">
                                        <span class="text-[10px] font-black text-muted-foreground uppercase tracking-[0.2em]">Total Amount</span>
                                        <span class="text-lg lg:text-xl font-black text-primary tracking-tighter">{{ formatPrice(order.totalPrice) }}</span>
                                    </div>

                                    <!-- Order Actions -->
                                    <div class="pt-1 flex flex-col gap-2">
                                        <!-- Print Receipt for Paid Orders -->
                                        <Button 
                                            v-if="['cooking', 'served'].includes(order.status.toLowerCase())"
                                            @click="printReceipt(order.id)" 
                                            variant="outline" 
                                            class="w-full gap-2 border-primary/20 hover:bg-primary/5 font-black uppercase text-[10px] tracking-[0.15em] h-10 rounded-xl"
                                        >
                                            <Printer class="w-4 h-4 text-primary" /> {{ ui.print_receipt || 'Print Receipt' }}
                                        </Button>

                                        <!-- Cancel Button for Cooking -->
                                        <Button 
                                            v-if="order.status.toLowerCase() === 'cooking'"
                                            @click="triggerCancel(order.id)" 
                                            variant="ghost" 
                                            class="w-full gap-2 text-destructive hover:bg-destructive/5 font-black uppercase text-[10px] tracking-[0.15em] h-10 rounded-xl border border-destructive/20"
                                        >
                                            <XCircle class="w-4 h-4" /> {{ ui.cancel_order || 'Cancel Order' }}
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination Footer -->
                    <div v-if="orders.links.length > 3" class="p-4 border-t border-secondary/50 bg-secondary/5 flex items-center justify-between shrink-0">
                        <div class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">
                            Showing {{ orders.from }} to {{ orders.to }} of {{ orders.total }}
                        </div>
                        <div class="flex gap-1">
                            <template v-for="(link, k) in orders.links" :key="k">
                                <div v-if="link.url === null" 
                                     class="px-3 py-1.5 rounded-lg border border-secondary/50 text-[10px] font-black text-muted-foreground/40 uppercase bg-card/50"
                                     v-html="link.label"
                                />
                                <button v-else
                                        @click="router.get(link.url, {}, { preserveScroll: true, preserveState: true })"
                                        :class="[
                                            'px-3 py-1.5 rounded-lg border text-[10px] font-black uppercase transition-all',
                                            link.active 
                                                ? 'bg-primary text-primary-foreground border-primary shadow-lg shadow-primary/20' 
                                                : 'bg-card text-primary border-secondary/50 hover:bg-secondary/20'
                                        ]"
                                        v-html="link.label"
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Manager Face Approval Modal -->
        <FaceApprovalModal 
            :show="showFaceModal" 
            @close="showFaceModal = false" 
            @approved="onCancelApproved"
            :title="ui.authorization_required || 'Authorization Required'"
            :message="ui.manager_scan_required_cancel || 'Manager face scan is required to cancel a cooking order.'"
        />
    </AppLayout>
</template>
