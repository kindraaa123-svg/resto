<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Input.vue' // Reusing Input style for consistency if needed, but we'll use custom buttons
import { 
    ChevronLeft,
    Printer,
    Calendar,
    ArrowUpRight,
    ArrowDownRight,
    Filter,
    Download,
    FileSpreadsheet,
    FileDown
} from '@lucide/vue'
import { computed } from 'vue'

defineOptions({ layout: AppLayout })

const page = usePage()
const props = defineProps({
    reportData: Array,
    currentPeriod: String,
})

const ui = computed(() => page.props.translations?.ui || {})
const system = computed(() => page.props.system || {})

function formatCurrency(value) {
    if (value === null || value === undefined) return 'Rp 0'
    return new Intl.NumberFormat(page.props.locale === 'id' ? 'id-ID' : 'en-US', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value)
}

function formatDate(dateStr, period) {
    if (period === 'daily') {
        return new Date(dateStr).toLocaleDateString(page.props.locale === 'id' ? 'id-ID' : 'en-US', { day: 'numeric', month: 'long', year: 'numeric' })
    } else if (period === 'monthly') {
        const [year, month] = dateStr.split('-')
        return new Date(year, month - 1).toLocaleDateString(page.props.locale === 'id' ? 'id-ID' : 'en-US', { month: 'long', year: 'numeric' })
    }
    return dateStr // Yearly
}

const changePeriod = (period) => {
    router.get(route('finance.reports.financial'), { period }, { preserveState: true })
}

const printReport = () => {
    const printContent = document.getElementById('report-table').innerHTML;
    const printWindow = window.open('', '_blank');
    
    const systemName = system.value.name || 'POS System';
    const systemLogo = system.value.logo;
    
    printWindow.document.write(`
        <html>
            <head>
                <title>FINANCIAL REPORT - ${systemName}</title>
                <style>
                    body { font-family: 'Inter', system-ui, sans-serif; padding: 40px; color: #000; line-height: 1.5; background: #fff; }
                    @page { size: A4 landscape; margin: 10mm; }
                    
                    .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 20px; }
                    .header img { max-height: 60px; margin-bottom: 10px; }
                    .header h1 { margin: 0; font-size: 24px; font-weight: 800; color: #000; letter-spacing: -0.025em; text-transform: uppercase; }
                    .header h2 { margin: 5px 0 0; font-size: 16px; color: #000; font-weight: 600; }
                    .header p { margin: 5px 0 0; font-size: 12px; color: #000; font-weight: 500; }
                    
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #000; padding: 12px; text-align: left; font-size: 11px; color: #000; }
                    th { background-color: #fff; font-weight: 800; text-transform: uppercase; color: #000; letter-spacing: 0.05em; }
                    .text-right { text-align: right; }
                    .font-black { font-weight: 900; }
                    .text-green { color: #000 !important; }
                    .text-red { color: #000 !important; }
                    .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #000; border-top: 1px solid #000; padding-top: 10px; text-transform: uppercase; font-weight: 600; }
                    
                    /* Hide action column and elements in print */
                    .no-print-col, th:last-child, td:last-child { display: none !important; }
                    button, .actions-cell { display: none !important; }
                    
                    @media print {
                        body { padding: 0; }
                        table { page-break-inside: auto; }
                        tr { page-break-inside: avoid; page-break-after: auto; }
                        thead { display: table-header-group; }
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    ${systemLogo ? `<img src="${systemLogo}" alt="Logo" />` : ''}
                    <h1>${systemName}</h1>
                    <h2>FINANCIAL REPORT</h2>
                    <p>${props.currentPeriod.toUpperCase()} BREAKDOWN</p>
                    <p>Generated on: ${new Date().toLocaleString('en-US')}</p>
                </div>
                ${printContent}
                <div class="footer">
                    &copy; ${new Date().getFullYear()} ${systemName} &bull; Joyi POS Intelligence
                </div>
                <script>
                    window.onload = function() {
                        setTimeout(() => {
                            window.print();
                        }, 500);
                    };
                <\/script>
            </body>
        </html>
    `);
    
    printWindow.document.close();
}

const printRow = (row) => {
    const printWindow = window.open('', '_blank');
    
    const systemName = system.value.name || 'POS System';
    const systemLogo = system.value.logo;
    const grossProfit = row.income - row.stock;
    const totalExpenses = row.payroll + row.operational;
    
    // Force English date for print
    const englishDate = new Date(row.raw_date).toLocaleDateString('en-US', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
    
    printWindow.document.write(`
        <html>
            <head>
                <title>FINANCIAL REPORT - ${englishDate}</title>
                <style>
                    body { font-family: 'Inter', system-ui, sans-serif; padding: 40px; color: #000; line-height: 1.5; background: #fff; }
                    @page { size: A4; margin: 15mm; }
                    
                    .report-header { text-align: center; margin-bottom: 50px; border-bottom: 2px solid #000; padding-bottom: 20px; }
                    .report-header img { max-height: 60px; margin-bottom: 10px; }
                    .report-header h1 { margin: 0; font-size: 28px; font-weight: 800; color: #000; letter-spacing: -0.025em; text-transform: uppercase; }
                    .report-header .company { font-size: 18px; font-weight: 600; margin-top: 5px; color: #000; }
                    .report-header .period { font-size: 14px; color: #000; margin-top: 5px; font-weight: 500; }
                    
                    .data-section { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
                    .data-row { border-bottom: 1px solid #000; }
                    .data-row.section-title { border-bottom: 2px solid #000; }
                    .data-row.section-title td { padding: 20px 0 8px; font-weight: 800; text-transform: uppercase; font-size: 12px; color: #000; letter-spacing: 0.05em; }
                    
                    .data-row td { padding: 12px 0; font-size: 14px; color: #000; }
                    .data-row .label { padding-left: 20px; }
                    .data-row .amount { text-align: right; font-weight: 600; font-family: monospace; }
                    
                    .data-row.total-row { font-weight: 800; color: #000; }
                    .data-row.total-row .label { padding-left: 0; }
                    
                    .highlight-box { background-color: #fff; border-top: 1px solid #000; border-bottom: 1px solid #000; font-weight: 800; color: #000; }
                    .highlight-box td { padding: 15px 10px; font-size: 15px; }
                    
                    .footer { position: fixed; bottom: 40px; left: 40px; right: 40px; border-top: 1px solid #000; padding-top: 15px; display: flex; justify-content: space-between; font-size: 10px; color: #000; font-weight: 600; text-transform: uppercase; }
                    
                    @media print {
                        body { padding: 0; }
                    }
                </style>
            </head>
            <body>
                <div class="report-header">
                    ${systemLogo ? `<img src="${systemLogo}" alt="Logo" />` : ''}
                    <h1>FINANCIAL REPORT</h1>
                    <div class="company">${systemName}</div>
                    <div class="period">Period: ${englishDate}</div>
                </div>
                
                <table class="data-section">
                    <tr class="data-row section-title"><td colspan="2">Revenue</td></tr>
                    <tr class="data-row"><td class="label">Sales Revenue</td><td class="amount">${formatCurrency(row.income)}</td></tr>
                    <tr class="data-row total-row"><td class="label">Total Revenue</td><td class="amount">${formatCurrency(row.income)}</td></tr>
                    
                    <tr class="data-row section-title"><td colspan="2">Cost of Goods Sold (COGS)</td></tr>
                    <tr class="data-row"><td class="label">Purchases (Stock Inventory)</td><td class="amount">${formatCurrency(row.stock)}</td></tr>
                    <tr class="data-row total-row"><td class="label">Total Cost of Goods Sold</td><td class="amount">(${formatCurrency(row.stock)})</td></tr>
                    
                    <tr class="highlight-box"><td>GROSS PROFIT</td><td class="amount">${formatCurrency(grossProfit)}</td></tr>
                    
                    <tr class="data-row section-title"><td colspan="2">Operating Expenses</td></tr>
                    <tr class="data-row"><td class="label">Payroll Expenses (Salaries)</td><td class="amount">${formatCurrency(row.payroll)}</td></tr>
                    <tr class="data-row"><td class="label">Other Operating Expenses</td><td class="amount">${formatCurrency(row.operational)}</td></tr>
                    <tr class="data-row total-row"><td class="label">Total Operating Expenses</td><td class="amount">(${formatCurrency(totalExpenses)})</td></tr>
                    
                    <tr class="highlight-box"><td>OPERATING INCOME (EBIT)</td><td class="amount">${formatCurrency(row.profit)}</td></tr>
                    
                    <tr class="data-row section-title"><td colspan="2">Other Income & Expenses</td></tr>
                    <tr class="data-row total-row"><td class="label">Total Other Income & Expenses</td><td class="amount">Rp 0</td></tr>
                    
                    <tr class="highlight-box" style="background-color: #f1f5f9; border-bottom: 3px double #cbd5e1;"><td>NET PROFIT / (LOSS)</td><td class="amount">${formatCurrency(row.profit)}</td></tr>
                </table>
                
                <div class="footer">
                    <span>Generated by Joyi POS System</span>
                    <span>Printed on: ${new Date().toLocaleString('en-US')}</span>
                </div>
                
                <script>
                    window.onload = function() {
                        setTimeout(() => {
                            window.print();
                            window.close();
                        }, 500);
                    };
                <\/script>
            </body>
        </html>
    `);
    printWindow.document.close();
}

const exportExcel = () => {
    window.location.href = route('finance.reports.financial.excel', { period: props.currentPeriod })
}

const exportPdf = () => {
    window.location.href = route('finance.reports.financial.pdf', { period: props.currentPeriod })
}
</script>

<template>
    <Head :title="ui.financial_report || 'Financial Reports'" />

    <div class="max-w-6xl mx-auto space-y-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <Link :href="route('finance.reports')" class="p-2 rounded-2xl bg-secondary text-primary hover:bg-primary hover:text-primary-foreground transition-all">
                    <ChevronLeft class="w-5 h-5" />
                </Link>
                <div>
                    <h1 class="text-3xl font-black tracking-tight text-primary uppercase">{{ ui.financial_report || 'Financial Report' }}</h1>
                    <p class="text-muted-foreground text-sm font-medium uppercase tracking-widest mt-1">{{ ui.daily_monthly_yearly || 'Daily, Monthly & Yearly Overview' }}</p>
                </div>
            </div>
            
            <div class="flex items-center gap-2 bg-secondary/30 p-1.5 rounded-2xl">
                <button 
                    v-for="p in ['daily', 'monthly', 'yearly']" 
                    :key="p"
                    @click="changePeriod(p)"
                    :class="[
                        'px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all',
                        currentPeriod === p ? 'bg-primary text-primary-foreground shadow-md' : 'text-muted-foreground hover:bg-secondary'
                    ]"
                >
                    {{ p }}
                </button>
            </div>
        </div>

        <Card class="border-none shadow-sm bg-card/50 overflow-hidden">
            <div class="p-6 border-b border-secondary/50 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Filter class="w-4 h-4 text-primary" />
                    <h2 class="text-xs font-black uppercase tracking-widest text-primary">{{ currentPeriod }} {{ ui.breakdown || 'Breakdown' }}</h2>
                </div>
                <div class="flex items-center gap-2">
                    <button 
                        @click="exportExcel"
                        class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:scale-105 transition-all shadow-lg shadow-green-600/20"
                    >
                        <FileSpreadsheet class="w-3 h-3" />
                        {{ ui.excel || 'Excel' }}
                    </button>
                    <button 
                        @click="exportPdf"
                        class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:scale-105 transition-all shadow-lg shadow-red-600/20"
                    >
                        <FileDown class="w-3 h-3" />
                        {{ ui.pdf || 'PDF' }}
                    </button>
                    <button 
                        @click="printReport"
                        class="flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-xl text-[10px] font-black uppercase tracking-widest hover:scale-105 transition-all shadow-lg shadow-primary/20"
                    >
                        <Printer class="w-3 h-3" />
                        {{ ui.print || 'Print' }}
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto" id="report-table">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-secondary/20 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">
                            <th class="px-6 py-4">{{ ui.period || 'Period' }}</th>
                            <th class="px-6 py-4 text-right">{{ ui.income || 'Income' }}</th>
                            <th class="px-6 py-4 text-right">{{ ui.operational || 'Operational' }}</th>
                            <th class="px-6 py-4 text-right">{{ ui.payroll || 'Payroll' }}</th>
                            <th class="px-6 py-4 text-right">{{ ui.stock || 'Stock' }}</th>
                            <th class="px-6 py-4 text-right">{{ ui.net_profit || 'Net Profit' }}</th>
                            <th class="px-6 py-4 text-center">{{ ui.actions || 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-secondary/30">
                        <tr v-for="row in reportData" :key="row.raw_date" class="group hover:bg-white/50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="font-black text-primary uppercase tracking-tight text-xs">
                                    {{ row.date }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="font-bold text-green-600 text-xs">{{ formatCurrency(row.income) }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-xs font-medium text-muted-foreground">{{ formatCurrency(row.operational) }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-xs font-medium text-muted-foreground">{{ formatCurrency(row.payroll) }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-xs font-medium text-muted-foreground">{{ formatCurrency(row.stock) }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <component :is="row.profit >= 0 ? ArrowUpRight : ArrowDownRight" :class="['w-3 h-3', row.profit >= 0 ? 'text-green-600' : 'text-destructive']" />
                                    <span :class="['font-black text-xs', row.profit >= 0 ? 'text-green-600' : 'text-destructive']">
                                        {{ formatCurrency(row.profit) }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <button @click="printRow(row)" :title="ui.print || 'Print'" class="p-2 rounded-lg bg-secondary text-primary hover:bg-primary hover:text-primary-foreground transition-all">
                                        <Printer class="w-3 h-3" />
                                    </button>
                                    <button @click="exportPdf()" :title="ui.pdf || 'Export PDF'" class="p-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition-all">
                                        <FileDown class="w-3 h-3" />
                                    </button>
                                    <button @click="exportExcel()" :title="ui.excel || 'Export Excel'" class="p-2 rounded-lg bg-green-50 text-green-600 hover:bg-green-600 hover:text-white transition-all">
                                        <FileSpreadsheet class="w-3 h-3" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="reportData.length === 0">
                            <td colspan="7" class="px-6 py-12 text-center text-muted-foreground italic text-sm">
                                {{ ui.no_financial_data || 'No financial data found for this period.' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6" v-if="reportData.length > 0">
            <Card class="bg-green-500/5 border-green-500/20 shadow-none p-6 space-y-2">
                <p class="text-[10px] font-black uppercase tracking-widest text-green-700/60">{{ ui.total_revenue || 'Total Income' }}</p>
                <h3 class="text-2xl font-black text-green-600">{{ formatCurrency(reportData.reduce((sum, r) => sum + r.income, 0)) }}</h3>
            </Card>
            <Card class="bg-destructive/5 border-destructive/20 shadow-none p-6 space-y-2">
                <p class="text-[10px] font-black uppercase tracking-widest text-destructive/60">{{ ui.total_outcome || 'Total Outcome' }}</p>
                <h3 class="text-2xl font-black text-destructive">{{ formatCurrency(reportData.reduce((sum, r) => sum + r.total_outcome, 0)) }}</h3>
            </Card>
            <Card class="bg-primary/5 border-primary/20 shadow-none p-6 space-y-2">
                <p class="text-[10px] font-black uppercase tracking-widest text-primary/60">{{ ui.net_profit || 'Net Profit' }}</p>
                <h3 class="text-2xl font-black text-primary">{{ formatCurrency(reportData.reduce((sum, r) => sum + r.profit, 0)) }}</h3>
            </Card>
        </div>
    </div>
</template>
