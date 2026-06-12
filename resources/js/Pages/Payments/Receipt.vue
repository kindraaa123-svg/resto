<script setup>
import { Head } from '@inertiajs/vue3'
import { ref, onMounted, computed } from 'vue'
import { Printer, X, CheckCircle2 } from '@lucide/vue'

const props = defineProps({
    order: Object,
    payment: Object,
    system: Object,
})

const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(price || 0)
}

const formatDate = (dateString) => {
    if (!dateString) return '-'
    return new Date(dateString).toLocaleString('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short'
    })
}

const print = () => {
    window.print()
}

// Group details by package
const groupedDetails = computed(() => {
    const details = props.order?.details || []
    const groups = []
    const packageMap = new Map()

    details.forEach(detail => {
        const pkgId = parseInt(detail.packagename_id)
        if (pkgId && pkgId > 0) {
            if (!packageMap.has(pkgId)) {
                packageMap.set(pkgId, {
                    type: 'package',
                    id: pkgId,
                    name: detail.package?.packagename || 'PROMO PACKAGE',
                    price: detail.package_definition?.price || 0, 
                    quantity: 1, 
                    items: []
                })
                groups.push(packageMap.get(pkgId))
            }
            const currentPkg = packageMap.get(pkgId)
            currentPkg.items.push(detail)
            
            // If the package group doesn't have a note yet, but this item does, assign it.
            // Since all items in a package get the same parent note, finding one is enough.
            if (!currentPkg.note && detail.note) {
                currentPkg.note = detail.note
            }

            // Use the price from definition if not already set
            if (!currentPkg.price && detail.package_definition?.price) {
                currentPkg.price = detail.package_definition.price
            }
        } else {
            groups.push({
                type: detail.productid > 0 ? 'product' : 'menu',
                ...detail,
                // Add consistent price property for individual items
                price: detail.menu?.price || detail.product?.price || 0
            })
        }
    })

    return groups
})

const subtotal = computed(() => {
    const total = parseFloat(props.order?.totalprice || 0)
    const discount = parseFloat(props.order?.applied_promotion_discount || 0)
    return (total + discount) / 1.11
})

const tax = computed(() => subtotal.value * 0.11)

onMounted(() => {
    // Auto-print receipt upon loading
    setTimeout(() => window.print(), 500)
})
</script>

<template>
    <Head title="Receipt" />

    <div class="min-h-screen bg-secondary/20 p-4 sm:p-8 flex flex-col items-center">
        <!-- Print Header / Controls (Hidden during print) -->
        <div class="w-full max-w-md mb-6 flex justify-between items-center print:hidden">
            <button @click="window.close()" class="flex items-center gap-2 text-muted-foreground hover:text-primary transition-colors font-bold text-xs uppercase tracking-widest">
                <X class="w-4 h-4" /> Close
            </button>
            <button @click="print" class="flex items-center gap-2 bg-primary text-primary-foreground px-6 py-3 rounded-2xl font-black text-xs uppercase tracking-widest shadow-lg shadow-primary/20 hover:scale-105 active:scale-95 transition-all">
                <Printer class="w-4 h-4" /> Print Receipt
            </button>
        </div>

        <!-- Receipt Paper -->
        <div class="w-full max-w-[400px] bg-white shadow-2xl rounded-sm p-8 flex flex-col items-center text-slate-800 font-mono text-[13px] print:shadow-none print:p-0">
            
            <!-- Store Info -->
            <div class="text-center space-y-2 mb-8 w-full">
                <img v-if="system?.logo" :src="system.logo" class="w-16 h-16 object-contain mx-auto mb-4" />
                <h1 class="text-xl font-black uppercase tracking-tighter">{{ system?.name || 'Joyi POS' }}</h1>
                <p class="text-[10px] leading-tight text-slate-500 max-w-[200px] mx-auto">{{ system?.address || 'Jl. Sinar Lestari No.3&4' }}</p>
                <p class="text-[10px] font-bold">{{ system?.contact || '08117010988' }}</p>
            </div>

            <div class="w-full border-t border-dashed border-slate-300 my-4"></div>

            <!-- Transaction Info -->
            <div class="w-full space-y-1 mb-6">
                <div class="flex justify-between">
                    <span>Order ID:</span>
                    <span class="font-bold">#{{ order.orderid }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Date:</span>
                    <span>{{ formatDate(payment?.paymentdate || order.ordertime) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Type:</span>
                    <span class="uppercase">{{ order.ordertype }}</span>
                </div>
                <div v-if="order.table_seat" class="flex justify-between">
                    <span>Table:</span>
                    <span class="font-bold">{{ order.table_seat.name }}</span>
                </div>
                <div v-if="order.name" class="flex justify-between">
                    <span>Customer:</span>
                    <span class="uppercase truncate pl-4">{{ order.name }}</span>
                </div>
            </div>

            <div class="w-full border-t border-slate-200 my-4"></div>

            <!-- Items -->
            <div class="w-full space-y-4 mb-8">
                <div v-for="(group, idx) in groupedDetails" :key="idx" class="space-y-1">
                    <!-- Package View -->
                    <template v-if="group.type === 'package'">
                        <div class="flex justify-between items-start gap-4">
                            <span class="flex-1 uppercase font-black text-primary">{{ group.name }}</span>
                            <span class="shrink-0">{{ group.quantity }}x</span>
                            <span class="shrink-0 text-right font-black">{{ formatPrice(group.price) }}</span>
                        </div>
                        <!-- Item Note for package -->
                        <div v-if="group.note" class="text-[10px] text-slate-400 pl-4 italic leading-tight mb-2">
                            * Note: {{ group.note }}
                        </div>
                        <!-- Package Contents -->
                        <div v-for="item in group.items" :key="item.detailorderid" class="space-y-0.5">
                            <div class="flex justify-between items-center text-[11px] text-slate-500 pl-4 uppercase">
                                <span>· {{ item.quantity }}x {{ item.menu?.name || 'Unknown' }}</span>
                            </div>
                            <!-- Addons inside package -->
                            <div v-for="oa in item.addons" :key="oa.orderaddonid" class="text-[10px] text-slate-400 pl-8 italic">
                                + {{ oa.addon?.name }}
                            </div>
                        </div>
                    </template>

                    <!-- Regular Menu Item View -->
                    <template v-else>
                        <div class="flex justify-between items-start gap-4">
                            <span class="flex-1 uppercase font-bold">{{ group.menu?.name || group.product?.productname || 'Item' }}</span>
                            <span class="shrink-0">{{ group.quantity }}x</span>
                            <span class="shrink-0 text-right">{{ group.is_free ? 'Rp 0' : formatPrice((group.menu?.price || group.product?.price || 0) * group.quantity) }}</span>
                        </div>
                        <!-- Addons -->
                        <div v-for="oa in group.addons" :key="oa.orderaddonid" class="flex justify-between items-center text-[11px] text-slate-500 pl-4 italic">
                            <span>+ {{ oa.addon.name }}</span>
                            <span>{{ formatPrice(oa.addon.price * group.quantity) }}</span>
                        </div>
                        <!-- Item Note -->
                        <div v-if="group.note" class="text-[10px] text-slate-400 pl-4 italic leading-tight">
                            * Note: {{ group.note }}
                        </div>
                    </template>
                </div>
            </div>

            <!-- General Order Note -->
            <div v-if="order.note" class="w-full bg-slate-50 p-3 rounded mb-6 border border-slate-100">
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">Order Note:</p>
                <p class="italic text-[11px] leading-relaxed text-slate-600">"{{ order.note }}"</p>
            </div>

            <div class="w-full border-t border-dashed border-slate-300 my-4"></div>

            <!-- Totals -->
            <div class="w-full space-y-2 mb-8">
                <div class="flex justify-between items-center text-[11px] text-slate-500">
                    <span class="uppercase tracking-widest">Subtotal</span>
                    <span>{{ formatPrice(subtotal) }}</span>
                </div>
                <div class="flex justify-between items-center text-[11px] text-slate-500">
                    <span class="uppercase tracking-widest">Tax (11%)</span>
                    <span>{{ formatPrice(tax) }}</span>
                </div>

                <!-- Applied Promotion -->
                <div v-if="order.applied_promotion_name" class="flex justify-between items-center text-[11px] text-primary font-bold animate-in fade-in slide-in-from-right-1">
                    <span class="uppercase tracking-widest">Promo: {{ order.applied_promotion_name }}</span>
                    <span>-{{ formatPrice(order.applied_promotion_discount) }}</span>
                </div>

                <div class="w-full border-t border-slate-100 my-1"></div>
                <div class="flex justify-between items-center text-base font-black">
                    <span class="uppercase tracking-widest">Total</span>
                    <span>{{ formatPrice(order.totalprice) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span>Method:</span>
                    <span class="uppercase">{{ payment?.method || 'Cash' }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span>Paid:</span>
                    <span>{{ formatPrice(payment?.paid) }}</span>
                </div>
                <div class="flex justify-between items-center font-bold">
                    <span>Change:</span>
                    <span>{{ formatPrice(payment?.changes) }}</span>
                </div>
            </div>

            <div class="w-full border-t border-dashed border-slate-300 my-6"></div>

            <!-- Footer Message -->
            <div class="text-center space-y-3">
                <div class="flex justify-center text-green-600 mb-2 print:hidden">
                    <CheckCircle2 class="w-6 h-6" />
                </div>
                <p class="font-bold uppercase tracking-[0.2em] text-[10px]">Thank You!</p>
                <p class="text-[9px] text-slate-500 leading-relaxed uppercase">Please come again to {{ system?.systemname || 'Joyi' }}</p>
            </div>

        </div>

        <p class="mt-8 text-[10px] font-bold text-muted-foreground uppercase tracking-[0.3em] print:hidden">
            CTRL + P to print manually
        </p>
    </div>
</template>

<style>
@media print {
    body {
        background: white !important;
        margin: 0;
        padding: 0;
    }
    .min-h-screen {
        min-height: auto !important;
        background: white !important;
        padding: 0 !important;
    }
    @page {
        margin: 0;
        size: auto;
    }
}
</style>
