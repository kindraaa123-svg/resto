<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import Card from '@/Components/ui/Card.vue'
import { 
    BarChart3, 
    Wallet,
    TrendingUp,
    TrendingDown,
    Calendar,
    ArrowUpRight,
    ArrowDownRight,
    Search,
    Download,
    Printer,
    FileSpreadsheet,
    FileText
} from 'lucide-vue-next'
import { computed, ref, nextTick } from 'vue'

const props = defineProps({
    reportData: Array,
    currentPeriod: String,
    stats: Object,
    startDate: String,
    endDate: String,
    currentMonth: String,
    currentYear: String,
    translations: Object,
    system: Object,
    locale: String // Added locale prop
})

const ui = computed(() => props.translations?.ui || {})

const filterMonth = ref(props.currentMonth || (new Date().getMonth() + 1).toString().padStart(2, '0'))
const filterYear = ref(props.currentYear || new Date().getFullYear().toString())
const isLoading = ref(false)

const months = computed(() => [
    { value: '01', label: ui.value.january || 'January' },
    { value: '02', label: ui.value.february || 'February' },
    { value: '03', label: ui.value.march || 'March' },
    { value: '04', label: ui.value.april || 'April' },
    { value: '05', label: ui.value.may || 'May' },
    { value: '06', label: ui.value.june || 'June' },
    { value: '07', label: ui.value.july || 'July' },
    { value: '08', label: ui.value.august || 'August' },
    { value: '09', label: ui.value.september || 'September' },
    { value: '10', label: ui.value.october || 'October' },
    { value: '11', label: ui.value.november || 'November' },
    { value: '12', label: ui.value.december || 'December' }
])

const years = computed(() => {
    const currentYear = new Date().getFullYear()
    const yearsArray = []
    for (let i = 0; i < 5; i++) {
        yearsArray.push((currentYear - i).toString())
    }
    return yearsArray
})

const formatCurrency = (value) => {
    return new Intl.NumberFormat(props.locale === 'id' ? 'id-ID' : 'en-US', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value)
}

const changePeriod = (period) => {
    isLoading.value = true
    const params = { period }
    
    // Maintain year filter if switching between relevant periods
    if (period === 'daily' || period === 'weekly' || period === 'monthly') {
        params.year = filterYear.value
    }
    
    router.get(route('finance.reports'), params, { 
        preserveState: true,
        preserveScroll: true,
        onFinish: () => isLoading.value = false
    })
}

const applyMonthFilter = () => {
    isLoading.value = true
    router.get(route('finance.reports'), { 
        period: props.currentPeriod, // Use current period instead of hardcoded 'daily'
        month: props.currentPeriod === 'daily' ? filterMonth.value : undefined,
        year: filterYear.value
    }, { 
        preserveState: true,
        preserveScroll: true,
        onFinish: () => isLoading.value = false
    })
}

const periods = computed(() => [
    { id: 'daily', label: ui.value.daily || 'Daily' },
    { id: 'weekly', label: ui.value.weekly || 'Weekly' },
    { id: 'monthly', label: ui.value.monthly || 'Monthly' },
    { id: 'yearly', label: ui.value.yearly || 'Yearly' }
])

// Print logic
const printRow = (row) => {
    const url = route('finance.reports.print', { 
        period: props.currentPeriod,
        start_date: row.start_date,
        end_date: row.end_date
    })
    window.open(url, '_blank')
}

const exportRowExcel = (row) => {
    window.location.href = route('finance.reports.financial.excel', { 
        period: props.currentPeriod,
        start_date: row.start_date,
        end_date: row.end_date
    })
}

const exportRowPdf = (row) => {
    const url = route('finance.reports.financial.pdf', { 
        period: props.currentPeriod,
        start_date: row.start_date,
        end_date: row.end_date
    })
    window.open(url, '_blank')
}
</script>

<template>
    <AppLayout>
        <Head :title="ui.reports || 'Reports'" />

        <div class="p-6 space-y-8">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div class="space-y-1">
                    <h2 class="text-4xl font-black tracking-tight text-primary uppercase">{{ ui.reports || 'Financial Reports' }}</h2>
                    <p class="text-muted-foreground font-medium uppercase tracking-widest text-xs">
                        {{ ui.financial_overview || 'Comprehensive Business Performance Overview' }} (Since {{ startDate }})
                    </p>
                </div>
                
                <div class="flex items-center gap-2 bg-secondary/50 p-1 rounded-2xl border border-secondary">
                    <button 
                        v-for="p in periods" 
                        :key="p.id"
                        @click="changePeriod(p.id)"
                        class="px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all"
                        :class="currentPeriod === p.id 
                            ? 'bg-primary text-white shadow-lg shadow-primary/20 scale-105' 
                            : 'text-muted-foreground hover:text-primary hover:bg-white/50'"
                    >
                        {{ p.label }}
                    </button>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <Card class="p-6 border-none bg-white shadow-xl shadow-secondary/50 relative overflow-hidden group border-b-4 border-emerald-500">
                    <div class="relative z-10 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="p-2.5 bg-emerald-100 text-emerald-600 rounded-xl">
                                <TrendingUp class="w-6 h-6" />
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">{{ ui.total_income || 'Total Income' }}</span>
                        </div>
                        <div class="space-y-1">
                            <h3 class="text-3xl font-black tracking-tighter text-emerald-600">{{ formatCurrency(stats.income) }}</h3>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground/60">{{ ui.gross_revenue_desc || 'Gross Revenue Collected' }}</p>
                        </div>
                    </div>
                    <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-110 transition-transform duration-500 text-emerald-600">
                        <TrendingUp class="w-32 h-32" />
                    </div>
                </Card>

                <Card class="p-6 border-none bg-white shadow-xl shadow-secondary/50 relative overflow-hidden group border-b-4 border-destructive">
                    <div class="relative z-10 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="p-2.5 bg-destructive/10 text-destructive rounded-xl">
                                <TrendingDown class="w-6 h-6" />
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">{{ ui.total_outcome || 'Total Outcome' }}</span>
                        </div>
                        <div class="space-y-1">
                            <h3 class="text-3xl font-black tracking-tighter text-destructive">{{ formatCurrency(stats.total_outcome) }}</h3>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground/60">{{ ui.outcome_desc || 'Operational, Payroll & Stock' }}</p>
                        </div>
                    </div>
                    <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-110 transition-transform duration-500 text-destructive">
                        <TrendingDown class="w-32 h-32" />
                    </div>
                </Card>

                <Card class="p-6 border-none bg-white shadow-xl shadow-secondary/50 relative overflow-hidden group border-b-4" :class="stats.profit >= 0 ? 'border-primary' : 'border-destructive'">
                    <div class="relative z-10 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="p-2.5 rounded-xl" :class="stats.profit >= 0 ? 'bg-primary/10 text-primary' : 'bg-destructive/10 text-destructive'">
                                <Wallet class="w-6 h-6" />
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">{{ ui.net_profit || 'Net Profit' }}</span>
                        </div>
                        <div class="space-y-1">
                            <h3 class="text-3xl font-black tracking-tighter" :class="stats.profit >= 0 ? 'text-primary' : 'text-destructive'">
                                {{ formatCurrency(stats.profit) }}
                            </h3>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground/60">{{ ui.profit_desc || 'Estimated Take Home' }}</p>
                        </div>
                    </div>
                    <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:scale-110 transition-transform duration-500" :class="stats.profit >= 0 ? 'text-primary' : 'text-destructive'">
                        <Wallet class="w-32 h-32" />
                    </div>
                </Card>
            </div>

            <!-- Data Table Section -->
            <div v-if="currentPeriod === 'daily' || currentPeriod === 'monthly' || currentPeriod === 'weekly'" class="flex flex-col md:flex-row items-center gap-4 bg-white p-6 rounded-2xl shadow-xl shadow-secondary/30">
                <div class="flex items-center gap-3 w-full md:w-auto">
                    <div class="p-2 bg-primary/10 text-primary rounded-lg">
                        <Search class="w-5 h-5" />
                    </div>
                    <span class="font-black uppercase tracking-widest text-xs">{{ ui.filter_period || 'Filter Period' }}</span>
                </div>
                
                <div class="flex flex-col md:flex-row items-center gap-3 w-full md:w-auto">
                    <!-- Month Filter (Only for Daily) -->
                    <select 
                        v-if="currentPeriod === 'daily'"
                        v-model="filterMonth"
                        @change="applyMonthFilter"
                        class="bg-secondary/20 border-none rounded-xl text-xs font-bold p-2.5 focus:ring-2 focus:ring-primary/20 w-full md:w-40"
                    >
                        <option value="">{{ ui.select_month || 'Select Month' }}</option>
                        <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                    </select>

                    <!-- Year Filter -->
                    <select 
                        v-model="filterYear"
                        @change="applyMonthFilter"
                        class="bg-secondary/20 border-none rounded-xl text-xs font-bold p-2.5 focus:ring-2 focus:ring-primary/20 w-full md:w-32"
                    >
                        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                    </select>
                </div>
            </div>

            <Card class="border-none shadow-xl shadow-secondary/30 overflow-hidden">
                <div class="p-6 border-b border-secondary/50 flex items-center justify-between bg-white">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-primary/10 text-primary rounded-lg">
                            <Calendar class="w-5 h-5" />
                        </div>
                        <h3 class="font-black uppercase tracking-widest text-sm">{{ ui[currentPeriod] || currentPeriod }} {{ ui.breakdown || 'Breakdown' }}</h3>
                    </div>
                </div>

                <div class="overflow-x-auto bg-white">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-secondary/20">
                                <th class="p-4 text-[10px] font-black uppercase tracking-widest text-muted-foreground border-b border-secondary/50">{{ ui.period || 'Period' }}</th>
                                <th class="p-4 text-[10px] font-black uppercase tracking-widest text-muted-foreground border-b border-secondary/50">{{ ui.income || 'Income' }}</th>
                                <th class="p-4 text-[10px] font-black uppercase tracking-widest text-muted-foreground border-b border-secondary/50">{{ ui.outcome || 'Outcome' }}</th>
                                <th class="p-4 text-[10px] font-black uppercase tracking-widest text-muted-foreground border-b border-secondary/50">{{ ui.profit || 'Net Profit' }}</th>
                                <th class="p-4 text-[10px] font-black uppercase tracking-widest text-muted-foreground border-b border-secondary/50 text-right">{{ ui.actions || 'Actions' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-secondary/30">
                            <tr v-for="(row, idx) in reportData" :key="idx" class="hover:bg-secondary/10 transition-colors group">
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-2 h-2 rounded-full bg-primary/40 group-hover:scale-150 transition-transform"></div>
                                        <span class="font-bold text-sm text-primary">{{ row.date_label }}</span>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <span class="font-black text-sm text-emerald-600">{{ formatCurrency(row.income) }}</span>
                                </td>
                                <td class="p-4">
                                    <span class="font-bold text-sm text-destructive">{{ formatCurrency(row.total_outcome) }}</span>
                                </td>
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <span class="font-black text-sm" :class="row.profit >= 0 ? 'text-primary' : 'text-destructive'">
                                            {{ formatCurrency(row.profit) }}
                                        </span>
                                        <ArrowUpRight v-if="row.profit >= 0" class="w-4 h-4 text-primary opacity-40" />
                                        <ArrowDownRight v-else class="w-4 h-4 text-destructive opacity-40" />
                                    </div>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button 
                                            @click="printRow(row)"
                                            class="p-2 hover:bg-primary/10 text-muted-foreground hover:text-primary rounded-lg transition-colors"
                                            :title="ui.print || 'Print'"
                                        >
                                            <Printer class="w-4 h-4" />
                                        </button>
                                        <button 
                                            @click="exportRowExcel(row)"
                                            class="p-2 hover:bg-emerald-100/50 text-muted-foreground hover:text-emerald-700 rounded-lg transition-colors"
                                            :title="ui.excel || 'Excel'"
                                        >
                                            <FileSpreadsheet class="w-4 h-4" />
                                        </button>
                                        <button 
                                            @click="exportRowPdf(row)"
                                            class="p-2 hover:bg-red-100/50 text-muted-foreground hover:text-red-700 rounded-lg transition-colors"
                                            :title="ui.pdf || 'PDF'"
                                        >
                                            <FileText class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="reportData.length === 0">
                                <td colspan="5" class="p-12 text-center">
                                    <div class="flex flex-col items-center gap-2 opacity-20">
                                        <BarChart3 class="w-12 h-12" />
                                        <p class="font-black uppercase tracking-widest text-xs">{{ ui.no_data || 'No data available' }}</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>

<style scoped>
@media print {
    /* Hide non-report elements */
    aside, 
    header, 
    footer,
    button,
    .bg-secondary\/50 {
        display: none !important;
    }

    /* Reset main layout for print */
    main {
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
        height: auto !important;
        overflow: visible !important;
    }

    /* Ensure content is full width */
    .p-6 {
        padding: 0 !important;
    }

    .max-w-7xl {
        max-width: 100% !important;
    }

    /* Style the report for paper */
    .p-6.space-y-8 {
        padding: 2cm !important;
    }

    table {
        border-collapse: collapse !important;
        width: 100% !important;
    }

    th, td {
        border: 1px solid #eee !important;
    }

    .shadow-xl, .shadow-2xl {
        box-shadow: none !important;
    }

    .bg-primary {
        background-color: #f3f4f6 !important;
        color: black !important;
    }

    .text-primary, .text-destructive, .text-emerald-600 {
        color: black !important;
    }
}
</style>
