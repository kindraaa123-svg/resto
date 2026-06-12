<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import Card from '@/Components/ui/Card.vue'
import Input from '@/Components/ui/Input.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import { Search, Building, ClipboardList, Plus, Minus, Trash2, ShoppingCart, Scale } from '@lucide/vue'
import { ref, watch, computed, onMounted, onUnmounted } from 'vue'

defineOptions({ layout: AppLayout })

const page = usePage()
const props = defineProps({
    logs: Object,
    auth: Object,
    translations: Object,
})

const ui = computed(() => page.props.translations?.ui || {})

const searchQuery = ref('')

const filteredLogs = computed(() => {
    return props.logs.data.filter(l => 
        (l.product?.productname || '').toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        (l.branch?.branchname || '').toLowerCase().includes(searchQuery.value.toLowerCase())
    )
})

const formatCurrency = (amount) => {
    return new Intl.NumberFormat(page.props.locale === 'id' ? 'id-ID' : 'en-US', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount || 0)
}

const isMobileView = ref(false)
const checkViewSize = () => {
    isMobileView.value = window.innerWidth < 768
}

onMounted(() => {
    checkViewSize()
    window.addEventListener('resize', checkViewSize)
})

onUnmounted(() => {
    window.removeEventListener('resize', checkViewSize)
})
</script>

<template>
    <Head :title="ui.product_logs || 'Product Logs'" />

    <div class="max-w-7xl mx-auto space-y-6 lg:space-y-8 pb-20">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-2 lg:px-0">
            <div>
                <h1 class="text-2xl lg:text-3xl font-black tracking-tight text-primary uppercase">{{ ui.product_logs || 'Product Logs' }}</h1>
                <p class="text-muted-foreground text-[10px] lg:text-sm font-medium uppercase tracking-widest mt-1">{{ ui.track_product_movements || 'Track Product Stock Movements' }}</p>
            </div>
        </div>

        <Card class="border-none shadow-sm bg-card/50 overflow-hidden mx-2 lg:mx-0">
            <!-- Table Controls -->
            <div class="p-4 lg:p-6 border-b border-secondary/50 flex items-center">
                <div class="relative w-full sm:max-w-xs">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                    <Input 
                        v-model="searchQuery" 
                        :placeholder="ui.search_logs || 'Search logs...'" 
                        class="pl-10 bg-background h-11 border-transparent focus:border-primary/20 text-sm"
                    />
                </div>
            </div>

            <!-- Desktop Table -->
            <div v-if="!isMobileView" class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-secondary/20 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">
                            <th class="px-6 py-4">{{ ui.date || 'Date' }}</th>
                            <th class="px-6 py-4">{{ ui.product_name || 'Product' }}</th>
                            <th class="px-6 py-4">{{ ui.branch || 'Branch' }}</th>
                            <th class="px-6 py-4">{{ ui.type || 'Type' }}</th>
                            <th class="px-6 py-4 text-center">{{ ui.qty || 'Qty' }}</th>
                            <th class="px-6 py-4 text-right">{{ ui.price || 'Price' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-secondary/30">
                        <tr v-for="log in filteredLogs" :key="log.productlog_id" class="group hover:bg-white/50 transition-colors">
                            <td class="px-6 py-4 text-xs font-bold text-muted-foreground">
                                {{ new Date(log.created_at).toLocaleString() }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-black text-primary text-sm uppercase tracking-wide">{{ log.product?.productname }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-primary/5 border border-primary/10 text-xs font-bold text-primary">
                                    <Building class="w-3.5 h-3.5" />
                                    {{ log.branch?.branchname }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div :class="[
                                    'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest',
                                    log.type === 'IN' ? 'bg-green-100 text-green-700' : 
                                    log.type === 'SALE' ? 'bg-blue-100 text-blue-700' :
                                    'bg-red-100 text-red-700'
                                ]">
                                    <Plus v-if="log.type === 'IN'" class="w-3 h-3" />
                                    <ShoppingCart v-else-if="log.type === 'SALE'" class="w-3 h-3" />
                                    <Minus v-else class="w-3 h-3" />
                                    {{ log.type }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-sm font-black text-primary">{{ Math.round(log.qty) }}</span>
                            </td>
                            <td class="px-6 py-4 text-right font-black text-primary text-sm">
                                {{ log.price ? formatCurrency(log.price) : '-' }}
                            </td>
                        </tr>
                        <tr v-if="filteredLogs.length === 0">
                            <td colspan="7" class="px-6 py-12 text-center text-muted-foreground italic text-sm">
                                {{ ui.no_logs_found || 'No movement logs found.' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div v-else class="bg-secondary/5">
                <div class="divide-y divide-secondary/30 p-2 space-y-2">
                    <div v-for="log in filteredLogs" :key="log.productlog_id" class="bg-white border border-secondary/50 rounded-3xl p-4 shadow-sm relative overflow-hidden flex flex-col gap-3">
                        <div class="flex justify-between items-start">
                            <div class="space-y-1">
                                <h3 class="text-sm font-black text-primary uppercase leading-tight">{{ log.product?.productname }}</h3>
                                <p class="text-[9px] font-bold text-muted-foreground uppercase">{{ new Date(log.created_at).toLocaleString() }}</p>
                            </div>
                            <div :class="[
                                'px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-widest',
                                log.type === 'IN' ? 'bg-green-100 text-green-700' : 
                                log.type === 'SALE' ? 'bg-blue-100 text-blue-700' :
                                'bg-red-100 text-red-700'
                            ]">
                                {{ log.type }}
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between text-[10px] font-bold">
                            <div class="flex items-center gap-1.5 text-muted-foreground">
                                <Building class="w-3 h-3" />
                                {{ log.branch?.branchname }}
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-muted-foreground">Qty: <span class="text-primary font-black">{{ Math.round(log.qty) }}</span></span>
                                <span v-if="log.price" class="text-primary font-black">{{ formatCurrency(log.price) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-4 lg:p-6 border-t border-secondary/50">
                <Pagination :links="logs.links" />
            </div>
        </Card>
    </div>
</template>
