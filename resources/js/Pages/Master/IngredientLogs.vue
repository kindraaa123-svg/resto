<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Input from '@/Components/ui/Input.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import { Search, Building, ArrowUpCircle, ArrowDownCircle, Filter, X } from '@lucide/vue'
import { ref, watch, computed } from 'vue'

defineOptions({ layout: AppLayout })

const page = usePage()
const props = defineProps({
    ingredientLogs: Object,
    ingredients: Array,
    branches: Array,
    filters: Object,
    errors: Object,
    auth: Object,
    ziggy: Object,
    system: Object,
    locale: String,
    translations: Object,
})

const ui = computed(() => page.props.translations?.ui || {})
const userBranchId = computed(() => page.props.auth.user.branchid)
const isBranchUser = computed(() => !!userBranchId.value)

const activeFilters = ref({
    search: props.filters?.filter?.search || '',
    branchid: isBranchUser.value ? userBranchId.value : (props.filters?.filter?.branchid || ''),
    type: props.filters?.filter?.type || '',
})

function formatCurrency(value) {
    if (value === null || value === undefined || value === '') return '—'
    try {
        return new Intl.NumberFormat(page.props.locale === 'id' ? 'id-ID' : 'en-US', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(value)
    } catch (e) {
        return value
    }
}

function formatDate(dateString) {
    if (!dateString) return '—'
    try {
        return new Date(dateString).toLocaleDateString(page.props.locale === 'id' ? 'id-ID' : 'en-US', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        })
    } catch (e) {
        return dateString
    }
}

const applyFilters = () => {
    router.get(route('ingredient.ingredient-logs.index'), {
        filter: {
            search: activeFilters.value.search,
            branchid: activeFilters.value.branchid,
            type: activeFilters.value.type,
        }
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    })
}

const resetFilters = () => {
    activeFilters.value = {
        search: '',
        branchid: '',
        type: '',
    }
    applyFilters()
}

// Debounce search
let timeout = null
watch(activeFilters, () => {
    clearTimeout(timeout)
    timeout = setTimeout(() => {
        applyFilters()
    }, 300)
}, { deep: true })
</script>

<template>
    <Head :title="ui.ingredient_logs || 'Ingredient Logs'" />

    <div class="max-w-7xl mx-auto space-y-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-primary uppercase">{{ ui.ingredient_logs || 'Ingredient Logs' }}</h1>
                <p class="text-muted-foreground text-sm font-medium uppercase tracking-widest mt-1">{{ ui.track_ingredient_movements || 'Track Ingredient Movements (In/Out)' }}</p>
            </div>
        </div>

        <!-- Filters Section -->
        <Card class="border-none shadow-sm bg-card/50">
            <div class="p-6 space-y-6">
                <div class="flex items-center gap-2 text-primary">
                    <Filter class="w-4 h-4" />
                    <h2 class="text-xs font-black uppercase tracking-widest">{{ ui.filter_logs || 'Filter Logs' }}</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Search Filter -->
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black uppercase text-muted-foreground ml-1">{{ ui.search_ingredient || 'Search Ingredient' }}</label>
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                            <Input 
                                v-model="activeFilters.search" 
                                :placeholder="ui.search_placeholder || 'Search...'" 
                                class="pl-10 h-10 border-transparent bg-background text-xs font-bold focus:ring-1 focus:ring-ring"
                            />
                        </div>
                    </div>

                    <!-- Branch Filter (Only for global users) -->
                    <div v-if="!isBranchUser" class="space-y-1.5">
                        <label class="text-[10px] font-black uppercase text-muted-foreground ml-1">{{ ui.by_branch || 'By Branch' }}</label>
                        <select 
                            v-model="activeFilters.branchid"
                            class="w-full h-10 rounded-xl border-transparent bg-background text-xs font-bold focus:ring-1 focus:ring-ring px-3"
                        >
                            <option value="">{{ ui.all_branches || 'All Branches' }}</option>
                            <option v-for="b in branches" :key="b.branchid" :value="b.branchid">{{ b.branchname }}</option>
                        </select>
                    </div>

                    <!-- Type Filter -->
                    <div class="space-y-1.5" :class="{'md:col-span-2': isBranchUser}">
                        <label class="text-[10px] font-black uppercase text-muted-foreground ml-1">{{ ui.by_movement_type || 'By Movement Type' }}</label>
                        <div class="flex gap-2">
                            <select 
                                v-model="activeFilters.type"
                                class="flex-1 h-10 rounded-xl border-transparent bg-background text-xs font-bold focus:ring-1 focus:ring-ring px-3"
                            >
                                <option value="">{{ ui.all_types || 'All Types (IN/OUT)' }}</option>
                                <option value="IN">{{ ui.stock_in || 'Stock IN' }}</option>
                                <option value="OUT">{{ ui.stock_out || 'Stock OUT' }}</option>
                            </select>
                            <Button 
                                variant="ghost" 
                                size="icon"
                                @click="resetFilters"
                                class="h-10 w-10 rounded-xl hover:bg-destructive/10 hover:text-destructive shrink-0"
                                :title="ui.reset_filters || 'Reset Filters'"
                            >
                                <X class="w-4 h-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </Card>

        <Card class="border-none shadow-sm bg-card/50 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-secondary/20 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">
                            <th class="px-6 py-4">{{ ui.date_time || 'Date & Time' }}</th>
                            <th class="px-6 py-4">{{ ui.employee || 'Ingredient' }}</th>
                            <th class="px-6 py-4">{{ ui.branch || 'Branch' }}</th>
                            <th class="px-6 py-4">{{ ui.type || 'Type' }}</th>
                            <th class="px-6 py-4">{{ ui.quantity || 'Quantity' }}</th>
                            <th class="px-6 py-4">{{ ui.total_price || 'Total Price' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-secondary/30">
                        <tr v-for="item in ingredientLogs?.data || []" :key="item.ingredientlogid" class="group hover:bg-white/50 transition-colors">
                            <td class="px-6 py-4 text-[11px] font-bold text-muted-foreground">
                                {{ formatDate(item.created_at) }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-black text-primary text-sm uppercase tracking-wide">{{ item.ingredient_name }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-primary/5 border border-primary/10 text-xs font-bold text-primary">
                                    <Building class="w-3.5 h-3.5" />
                                    {{ item.branch_name }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div v-if="item.type === 'IN'" class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-green-100 text-green-700 text-[10px] font-black uppercase tracking-widest border border-green-200">
                                    <ArrowUpCircle class="w-3 h-3" />
                                    {{ ui.stock_in || 'Stock IN' }}
                                </div>
                                <div v-else class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-red-100 text-red-700 text-[10px] font-black uppercase tracking-widest border border-red-200">
                                    <ArrowDownCircle class="w-3 h-3" />
                                    {{ ui.stock_out || 'Stock OUT' }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-black text-primary">{{ item.display_qty }}</span>
                            </td>
                            <td class="px-6 py-4 text-xs font-black text-primary">
                                <span v-if="item.type === 'IN'">{{ formatCurrency(item.total_price) }}</span>
                                <span v-else class="text-[10px] uppercase tracking-widest text-muted-foreground/50">N/A</span>
                            </td>
                        </tr>
                        <tr v-if="!ingredientLogs?.data || ingredientLogs.data.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-muted-foreground italic text-sm">
                                {{ ui.no_inventory_logs || 'No ingredient logs found.' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-6 border-t border-secondary/50">
                <Pagination :links="ingredientLogs?.links || []" />
            </div>
        </Card>
    </div>
</template>
