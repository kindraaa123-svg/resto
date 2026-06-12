<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, usePage, Link } from '@inertiajs/vue3'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import NewsletterCard from '@/Components/ui/NewsletterCard.vue'
import { 
    TrendingUp, 
    Users, 
    ShoppingBag, 
    ArrowUpRight,
    DollarSign,
    BarChart3,
    Utensils,
    Package,
    ClipboardList,
    Plus,
    LayoutDashboard,
    Calendar,
    ChevronRight,
    Search
} from '@lucide/vue'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { cn } from '@/lib/utils'

defineOptions({ layout: AppLayout })

const page = usePage()
const props = defineProps({
    income: { type: Number, default: 0 },
    chartData: { type: Object, default: () => ({ labels: [], series: [] }) },
    stats: { type: Object, default: () => ({ totalOrders: 0, todayOrders: 0 }) },
    errors: Object,
    auth: Object,
    ziggy: Object,
    system: Object,
    locale: String,
    translations: Object,
})

const ui = computed(() => page.props.translations?.ui || {})

const formatPrice = (price) => {
    return new Intl.NumberFormat(page.props.locale === 'id' ? 'id-ID' : 'en-US', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(price || 0)
}

const quickActions = computed(() => [
    { name: ui.value.new_order || 'New Order', description: 'Start a fresh customer transaction', icon: Plus, href: route('payments.index'), color: 'bg-primary text-white' },
    { name: ui.value.kitchen || 'Kitchen', description: 'Monitor and process orders', icon: Utensils, href: route('kitchen.index'), color: 'bg-white text-primary border-2 border-primary' },
    { name: ui.value.inventory || 'Inventory', description: 'Manage stocks and supplies', icon: Package, href: route('product.data-products.index'), color: 'bg-white text-primary border border-border' },
    { name: ui.value.reports || 'Reports', description: 'Analyze financial performance', icon: BarChart3, href: route('finance.reports'), color: 'bg-white text-primary border border-border' },
])

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

const chartOptions = computed(() => ({
    chart: {
        id: 'revenue-chart',
        toolbar: { show: false },
        zoom: { enabled: false },
        fontFamily: 'inherit',
    },
    colors: ['#16658F'],
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 3 },
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.4,
            opacityTo: 0.05,
            stops: [0, 90, 100]
        }
    },
    xaxis: {
        categories: props.chartData?.labels || [],
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: {
            style: { colors: '#666666', fontWeight: 500, fontSize: '10px' },
            rotate: isMobileView.value ? -45 : 0,
        }
    },
    yaxis: {
        labels: {
            formatter: (val) => formatPrice(val),
            style: { colors: '#666666', fontWeight: 500, fontSize: '10px' }
        }
    },
    grid: {
        borderColor: '#E6E6E6',
        strokeDashArray: 4,
    },
    tooltip: {
        theme: 'light',
        y: { formatter: (val) => formatPrice(val) }
    }
}))

const series = computed(() => [{
    name: ui.value.revenue || 'Revenue',
    data: props.chartData?.series || []
}])

const greeting = computed(() => {
    const hour = new Date().getHours()
    if (hour < 12) return ui.value.good_morning || 'Good Morning'
    if (hour < 18) return ui.value.good_afternoon || 'Good Afternoon'
    return ui.value.good_evening || 'Good Evening'
})
</script>

<template>
    <Head :title="ui.dashboard || 'Home'" />
    
    <div class="max-w-[1400px] mx-auto space-y-10 pb-24">
        <!-- Modern Hero Section -->
        <div class="relative min-h-[360px] rounded-[32px] overflow-hidden bg-[#076A68] flex items-center p-8 lg:p-16 shadow-2xl shadow-primary/20">
            <!-- Decorative Elements -->
            <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-white opacity-[0.03] rounded-full -mr-32 -mt-32"></div>
            <div class="absolute bottom-0 left-0 w-[300px] h-[300px] bg-[#FCCC4B] opacity-[0.05] rounded-full -ml-16 -mb-16 blur-3xl"></div>
            <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-[0.02] pointer-events-none"></div>

            <div class="relative z-10 w-full flex flex-col lg:flex-row lg:items-center justify-between gap-12">
                <div class="space-y-6 max-w-2xl">
                    <div class="inline-flex items-center gap-3 px-4 py-2 bg-white/10 backdrop-blur-md rounded-full border border-white/20">
                        <span class="w-2 h-2 bg-[#FCCC4B] rounded-full animate-pulse"></span>
                        <span class="text-[10px] font-black text-white uppercase tracking-[0.2em]">
                            {{ new Date().toLocaleDateString($page.props.locale === 'id' ? 'id-ID' : 'en-US', { weekday: 'long', day: 'numeric', month: 'long' }) }}
                        </span>
                    </div>
                    
                    <h1 class="text-4xl lg:text-6xl font-black text-white leading-[1.1] tracking-tighter">
                        {{ greeting }},<br />
                        <span class="text-[#FCCC4B]">{{ $page.props.auth.user?.name }}</span>
                    </h1>
                    
                    <p class="text-lg text-white/70 font-medium leading-relaxed max-w-lg">
                        Welcome back to your command center. Everything is ready for a productive day at <span class="text-white font-bold">{{ $page.props.system?.name }}</span>.
                    </p>

                    <div class="flex flex-wrap gap-4 pt-4">
                        <Link :href="route('payments.index')" class="h-14 px-10 bg-[#FCCC4B] text-[#076A68] rounded-2xl font-black uppercase tracking-widest text-xs shadow-xl shadow-black/10 hover:scale-105 active:scale-95 transition-all flex items-center gap-3">
                            <ShoppingBag class="w-5 h-5" />
                            {{ ui.start_ordering || 'New Order' }}
                        </Link>
                        <Link :href="route('kitchen.index')" class="h-14 px-10 bg-white/10 backdrop-blur-md text-white border border-white/20 rounded-2xl font-black uppercase tracking-widest text-xs hover:bg-white/20 transition-all flex items-center gap-3">
                            <Utensils class="w-5 h-5" />
                            {{ ui.view_kitchen || 'Kitchen' }}
                        </Link>
                    </div>
                </div>

                <!-- Floating Stats Badge -->
                <div class="hidden xl:flex flex-col gap-4">
                    <div class="bg-white/10 backdrop-blur-xl p-6 rounded-3xl border border-white/20 w-64 shadow-2xl">
                        <p class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-1">Today's Revenue</p>
                        <p class="text-3xl font-black text-[#FCCC4B]">{{ formatPrice(props.income) }}</p>
                        <div class="mt-4 flex items-center gap-2 text-[10px] font-bold text-white/80">
                            <span class="p-1 bg-[#30C55D] rounded-md text-[8px] text-white">UP 12%</span>
                            from yesterday
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- High Impact Action Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <Link v-for="action in quickActions" :key="action.name" :href="action.href" class="group relative">
                <div :class="cn(
                    'h-full p-8 rounded-[32px] transition-all duration-500 border border-border shadow-sm hover:shadow-2xl hover:-translate-y-2 flex flex-col gap-6',
                    action.color.includes('bg-primary') ? 'bg-[#076A68] text-white border-none' : 'bg-white'
                )">
                    <div :class="cn(
                        'w-16 h-16 rounded-2xl flex items-center justify-center transition-transform group-hover:rotate-6',
                        action.color.includes('bg-primary') ? 'bg-white/20 text-[#FCCC4B]' : 'bg-[#076A68]/10 text-[#076A68]'
                    )">
                        <component :is="action.icon" class="w-8 h-8" />
                    </div>
                    <div>
                        <h3 class="text-lg font-black uppercase tracking-tighter">{{ action.name }}</h3>
                        <p :class="cn(
                            'text-xs font-medium mt-2 leading-relaxed',
                            action.color.includes('bg-primary') ? 'text-white/60' : 'text-muted-foreground'
                        )">{{ action.description }}</p>
                    </div>
                    <div class="mt-auto flex justify-end">
                        <div :class="cn(
                            'w-10 h-10 rounded-full flex items-center justify-center transition-all group-hover:bg-[#FCCC4B] group-hover:text-[#076A68]',
                            action.color.includes('bg-primary') ? 'bg-white/10 text-white' : 'bg-muted text-[#076A68]'
                        )">
                            <ArrowUpRight class="w-5 h-5" />
                        </div>
                    </div>
                </div>
            </Link>
        </div>

        <!-- Data Visualization Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Revenue Performance -->
            <div class="lg:col-span-2 bg-white rounded-[32px] border border-border p-8 shadow-sm">
                <div class="flex items-center justify-between mb-10 px-2">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-[#076A68]/10 rounded-2xl text-[#076A68]">
                            <TrendingUp class="w-6 h-6" />
                        </div>
                        <div>
                            <h2 class="text-xl font-black tracking-tight text-foreground uppercase">Revenue Growth</h2>
                            <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest mt-0.5">Last 7 Days Performance</p>
                        </div>
                    </div>
                    <Link :href="route('finance.reports')" class="text-[10px] font-black text-[#076A68] uppercase tracking-[0.2em] px-4 py-2 bg-[#076A68]/5 rounded-full hover:bg-[#076A68] hover:text-white transition-all">
                        Full Report
                    </Link>
                </div>
                
                <div class="h-[340px]">
                    <apexchart
                        v-if="series[0].data.length > 0"
                        type="area"
                        height="100%"
                        :options="chartOptions"
                        :series="series"
                    />
                </div>
            </div>

            <!-- Stats & Insights Column -->
            <div class="space-y-8">
                <!-- Compact Stats Card -->
                <div class="grid grid-cols-1 gap-6">
                    <div class="bg-white p-8 rounded-[32px] border border-border shadow-sm group hover:border-[#076A68]/30 transition-colors">
                        <div class="flex justify-between items-start mb-6">
                            <div class="p-3 bg-[#FCCC4B]/20 text-[#076A68] rounded-2xl">
                                <ShoppingBag class="w-6 h-6" />
                            </div>
                            <span class="text-[10px] font-black text-[#30C55D] bg-[#30C55D]/10 px-3 py-1 rounded-full uppercase">Healthy</span>
                        </div>
                        <p class="text-[10px] font-black text-muted-foreground uppercase tracking-widest mb-1">Total Orders</p>
                        <p class="text-4xl font-black text-foreground">{{ props.stats?.totalOrders || 0 }}</p>
                        <div class="mt-4 pt-4 border-t border-border/50">
                            <p class="text-[10px] font-bold text-muted-foreground">Successfully processed via portal</p>
                        </div>
                    </div>

                    <div class="bg-white p-8 rounded-[32px] border border-border shadow-sm group hover:border-[#076A68]/30 transition-colors">
                        <div class="flex justify-between items-start mb-6">
                            <div class="p-3 bg-[#076A68]/10 text-[#076A68] rounded-2xl">
                                <Users class="w-6 h-6" />
                            </div>
                            <span class="text-[10px] font-black text-primary bg-primary/10 px-3 py-1 rounded-full uppercase">Today</span>
                        </div>
                        <p class="text-[10px] font-black text-muted-foreground uppercase tracking-widest mb-1">Current Guests</p>
                        <p class="text-4xl font-black text-foreground">{{ props.stats?.todayOrders || 0 }}</p>
                        <div class="mt-4 pt-4 border-t border-border/50">
                            <p class="text-[10px] font-bold text-muted-foreground">Active sessions in your branch</p>
                        </div>
                    </div>
                </div>

                <div class="bg-[#FCCC4B] p-8 rounded-[32px] relative overflow-hidden shadow-xl shadow-[#FCCC4B]/10">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-white opacity-20 rounded-full -mr-8 -mt-8"></div>
                    <div class="relative z-10 space-y-4">
                        <h3 class="text-lg font-black text-[#076A68] leading-tight">Need expert assistance?</h3>
                        <p class="text-xs font-bold text-[#076A68]/70 leading-relaxed uppercase tracking-wider">Our support engineers are ready to help 24/7.</p>
                        <button class="w-full h-12 bg-[#076A68] text-white rounded-2xl font-black uppercase tracking-widest text-[10px] hover:scale-105 transition-transform shadow-lg shadow-black/10">
                            Contact Engineering
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Optional styling */
</style>
