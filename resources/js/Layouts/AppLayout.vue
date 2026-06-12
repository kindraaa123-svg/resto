<script setup>
import { Link, usePage, router } from '@inertiajs/vue3'
import { 
    LayoutDashboard, 
    Utensils, 
    Database, 
    ChevronDown, 
    Tags,
    Settings,
    LogOut,
    Plus,
    ShieldCheck,
    History,
    HardDriveDownload,
    Users as UsersIcon,
    MapPin,
    Terminal,
    UserCircle,
    BarChart3,
    DollarSign,
    Package,
    Layers,
    ClipboardList,
    Wallet,
    BookOpen,
    Menu as MenuIcon,
    X as XIcon,
    CreditCard,
    ChefHat,
    ListTodo,
    Trash2,
    Gift,
    Box
} from '@lucide/vue'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { cn } from '@/lib/utils'
import LanguageToggle from '@/Components/shared/LanguageToggle.vue'
import RemoteScannerHandler from '@/Components/feature-specific/RemoteScannerHandler.vue'

const page = usePage()
const masterOpen = ref(false)
const productsOpen = ref(false)
const stocksOpen = ref(false)
const itemsOpen = ref(false)
const financeOpen = ref(false)
const systemOpen = ref(false)
const profileOpen = ref(false)
const mobileMenuOpen = ref(false)
const isMobile = ref(false)

// Close profile dropdown when clicking outside
const closeProfileDropdown = (e) => {
    if (profileOpen.value && !e.target.closest('.profile-trigger')) {
        profileOpen.value = false
    }
}

// UI Translations
const ui = computed(() => page.props.translations?.ui || {})

const checkWindowSize = () => {
    isMobile.value = window.innerWidth < 1024
    if (!isMobile.value) {
        mobileMenuOpen.value = false
    }
}

onMounted(() => {
    checkWindowSize()
    window.addEventListener('resize', checkWindowSize)
    window.addEventListener('click', closeProfileDropdown)
})

onUnmounted(() => {
    window.removeEventListener('resize', checkWindowSize)
    window.removeEventListener('click', closeProfileDropdown)
})

const userPermissions = computed(() => page.props.auth?.user?.permissions || [])
const isSuperadmin = computed(() => page.props.auth?.user?.is_superadmin || false)

const hasPermission = (permission) => {
    if (!permission) return true
    if (isSuperadmin.value) return true
    return userPermissions.value.includes(permission)
}

const navigation = computed(() => [
    { name: ui.value.dashboard || 'Dashboard', href: route('dashboard'), icon: LayoutDashboard, current: page.url === '/', permission: 'dashboard.view' },
    { name: ui.value.payments || 'Payments',  href: route('payments.index'), icon: CreditCard, current: page.url.startsWith('/payments'), permission: 'payments.view' },
    { name: ui.value.kitchen || 'Kitchen',  href: route('kitchen.index'), icon: ChefHat, current: page.url.startsWith('/kitchen'), permission: 'kitchen.view' },
    { name: ui.value.menu_data || 'Menu Data', href: route('menu.index'), icon: Utensils, current: page.url.startsWith('/menu'), permission: 'menu.view' },
].filter(item => hasPermission(item.permission)))

const managementDropdowns = computed(() => [
    { 
        name: ui.value.finance || 'Finance', 
        icon: BarChart3, 
        items: [
            { name: ui.value.operationals || 'Operationals', href: route('finance.operationals.index'), icon: Wallet, permission: 'finance.operationals.view' },
            { name: ui.value.reports || 'Reports', href: route('finance.reports'), icon: BarChart3, permission: 'finance.reports.view' },
            { name: ui.value.salaries || 'Salaries', href: route('finance.salaries.index'), icon: DollarSign, permission: 'finance.salaries.view' },
        ].filter(item => hasPermission(item.permission)),
        isOpen: financeOpen
    },
    { 
        name: ui.value.products || 'Products', 
        icon: Package, 
        items: [
            { name: ui.value.products || 'Products', href: route('product.data-products.index'), icon: Package, permission: 'product.data-products.view' },
            { name: ui.value.product_stocks || 'Stocks', href: route('product.product-stocks.index'), icon: Box, permission: 'product.product-stocks.view' },
            { name: ui.value.product_logs || 'Logs', href: route('product.product-logs.index'), icon: ClipboardList, permission: 'product.product-logs.view' },
        ].filter(item => hasPermission(item.permission)),
        isOpen: productsOpen
    },
    { 
        name: ui.value.ingredients_menu || 'Ingredients', 
        icon: Utensils, 
        items: [
            { name: ui.value.raw_ingredients || 'Ingredients', href: route('ingredient.data-ingredients.index'), icon: Package, permission: 'ingredient.ingredients.view' },
            { name: ui.value.inventory_stock || 'Stocks', href: route('ingredient.ingredient-stocks.index'), icon: Layers, permission: 'ingredient.ingredient-stocks.view' },
            { name: ui.value.ingredient_logs || 'Logs', href: route('ingredient.ingredient-logs.index'), icon: ClipboardList, permission: 'ingredient.ingredient-logs.view' },
        ].filter(item => hasPermission(item.permission)),
        isOpen: stocksOpen
    },
    { 
        name: ui.value.items || 'Items', 
        icon: Layers, 
        items: [
            { name: ui.value.items || 'Items', href: route('item.data-items.index'), icon: Package, permission: 'item.items.view' },
            { name: ui.value.item_stock || 'Stocks', href: route('item.item-stocks.index'), icon: Layers, permission: 'item.item-stocks.view' },
            { name: ui.value.item_logs || 'Logs', href: route('item.item-logs.index'), icon: ClipboardList, permission: 'item.item-logs.view' },
        ].filter(item => hasPermission(item.permission)),
        isOpen: itemsOpen
    },
    { 
        name: ui.value.master_data || 'Master', 
        icon: Database, 
        items: [
            { name: ui.value.users || 'Users', href: route('master.users.index'), icon: UsersIcon, permission: 'master.users.view' },
            { name: ui.value.branches || 'Branches', href: route('master.branches.index'), icon: MapPin, permission: 'master.branches.view' },
            { name: ui.value.tables || 'Tables', href: route('master.tables.index'), icon: Database, permission: 'master.tables.view' },
            { name: ui.value.addons || 'Add-ons', href: route('master.addons.index'), icon: Plus, permission: 'master.addons.view' },
            { name: ui.value.categories || 'Categories', href: route('master.categories.index'), icon: Tags, permission: 'master.categories.view' },
            { name: ui.value.freebies || 'Freebies', href: route('master.freebies.index'), icon: Gift, permission: 'master.freebies.view' },
        ].filter(item => hasPermission(item.permission)),
        isOpen: masterOpen
    },
    { 
        name: ui.value.system || 'System', 
        icon: Terminal, 
        items: [
            { name: ui.value.activity_log || 'Activity Log', href: route('system.activity-log'), icon: History, permission: 'system.activity-log.view' },
            { name: ui.value.backup_database || 'Backup Database', href: route('system.backup.database'), icon: HardDriveDownload, permission: 'system.backup.manage' },
            { name: ui.value.permissions || 'Permissions', href: route('system.permissions'), icon: ShieldCheck, permission: 'system.permissions.manage' },
            { name: ui.value.recycle_bin || 'Recycle Bin', href: route('system.recycle-bin.index'), icon: Trash2, permission: 'system.settings.manage' },
            { name: ui.value.system_settings || 'Settings', href: route('system.settings'), icon: Settings, permission: 'system.settings.manage' },
        ].filter(item => hasPermission(item.permission)),
        isOpen: systemOpen
    },
].filter(d => d.items.length > 0))

const activeNavName = computed(() => {
    const allItems = [
        ...navigation.value,
        ...managementDropdowns.value.flatMap(d => d.items)
    ]
    return allItems.find(n => page.url === n.href || page.url.startsWith(n.href))?.name || ui.value.dashboard || 'Dashboard'
})
</script>

<template>
    <div class="flex flex-col h-screen bg-background overflow-hidden">
        <RemoteScannerHandler />

        <!-- Top Navbar -->
        <header class="bg-white border-b border-border sticky top-0 z-50 px-6 h-16 flex items-center justify-between">
            <!-- Brand & Logo -->
            <div class="flex items-center gap-8">
                <Link :href="route('dashboard')" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 bg-primary rounded-xl flex items-center justify-center text-white shadow-lg shadow-primary/20 transition-transform group-hover:scale-105">
                        <img v-if="$page.props.system?.logo" :src="$page.props.system.logo" class="w-full h-full object-cover rounded-xl" />
                        <span v-else class="font-black">{{ $page.props.system?.name?.charAt(0) || 'P' }}</span>
                    </div>
                    <h2 class="hidden md:block text-base font-black tracking-tighter text-primary uppercase">{{ $page.props.system?.name || 'POS' }}</h2>
                </Link>

                <!-- Desktop Navigation -->
                <nav class="hidden lg:flex items-center gap-1">
                    <template v-for="item in navigation" :key="item.name">
                        <Link 
                            :href="item.href"
                            :class="cn(
                                'flex items-center gap-2 px-4 h-10 text-sm font-bold rounded-xl transition-all',
                                item.current 
                                    ? 'bg-accent text-accent-foreground shadow-sm' 
                                    : 'text-muted-foreground hover:bg-muted hover:text-primary'
                            )"
                        >
                            <component :is="item.icon" class="w-4 h-4" />
                            {{ item.name }}
                        </Link>
                    </template>

                    <!-- Management Dropdowns (Desktop) -->
                    <div v-for="dropdown in managementDropdowns" :key="dropdown.name" class="relative group/dropdown">
                        <button 
                            class="flex items-center gap-2 px-4 h-10 text-sm font-bold text-muted-foreground rounded-xl hover:bg-muted hover:text-primary transition-all"
                        >
                            <component :is="dropdown.icon" class="w-4 h-4" />
                            {{ dropdown.name }}
                            <ChevronDown class="w-3 h-3 opacity-50 group-hover/dropdown:rotate-180 transition-transform" />
                        </button>
                        
                        <div class="absolute top-full left-0 mt-1 w-48 bg-white border border-border rounded-2xl shadow-xl opacity-0 translate-y-2 pointer-events-none group-hover/dropdown:opacity-100 group-hover/dropdown:translate-y-0 group-hover/dropdown:pointer-events-auto transition-all p-2 space-y-1">
                            <Link 
                                v-for="sub in dropdown.items" 
                                :key="sub.name"
                                :href="sub.href"
                                class="flex items-center gap-3 px-3 py-2 text-xs font-bold text-muted-foreground rounded-lg hover:bg-muted hover:text-primary transition-colors"
                            >
                                <component :is="sub.icon" class="w-3.5 h-3.5" />
                                {{ sub.name }}
                            </Link>
                        </div>
                    </div>
                </nav>
            </div>

            <!-- Right Side Tools -->
            <div class="flex items-center gap-3">
                <LanguageToggle class="hidden sm:block" />
                
                <div class="h-6 w-[1px] bg-border mx-1 hidden sm:block"></div>

                <!-- Profile Dropdown -->
                <div class="relative profile-trigger">
                    <button 
                        @click="profileOpen = !profileOpen"
                        class="flex items-center gap-2 pl-2 pr-4 h-10 rounded-xl hover:bg-muted transition-all group"
                    >
                        <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-colors">
                            <UserCircle class="w-5 h-5" />
                        </div>
                        <span class="hidden sm:inline text-xs font-bold text-foreground">{{ $page.props.auth?.user?.name || 'Staff' }}</span>
                        <ChevronDown class="w-3 h-3 opacity-50 group-hover:opacity-100 transition-all" :class="profileOpen && 'rotate-180'" />
                    </button>

                    <transition
                        enter-active-class="transition-all duration-200 ease-out"
                        enter-from-class="opacity-0 translate-y-2 scale-95"
                        enter-to-class="opacity-100 translate-y-0 scale-100"
                        leave-active-class="transition-all duration-150 ease-in"
                        leave-from-class="opacity-100 translate-y-0 scale-100"
                        leave-to-class="opacity-0 translate-y-2 scale-95"
                    >
                        <div v-if="profileOpen" class="absolute top-full right-0 mt-2 w-48 bg-white border border-border rounded-2xl shadow-xl p-2 space-y-1 z-[60]">
                            <Link 
                                :href="route('profile')"
                                @click="profileOpen = false"
                                class="flex items-center gap-3 px-4 py-3 text-xs font-bold text-muted-foreground rounded-xl hover:bg-muted hover:text-primary transition-colors"
                            >
                                <UserCircle class="w-4 h-4" />
                                {{ ui.profile || 'Profile' }}
                            </Link>
                            <Link 
                                :href="route('logout')" 
                                method="post" 
                                as="button"
                                class="w-full flex items-center gap-3 px-4 py-3 text-xs font-bold text-destructive rounded-xl hover:bg-destructive/10 transition-colors text-left"
                            >
                                <LogOut class="w-4 h-4" />
                                {{ ui.logout || 'Logout' }}
                            </Link>
                        </div>
                    </transition>
                </div>

                <!-- Mobile Menu Button -->
                <button 
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    class="lg:hidden w-10 h-10 flex items-center justify-center rounded-xl bg-muted text-foreground transition-all"
                >
                    <MenuIcon v-if="!mobileMenuOpen" class="w-5 h-5" />
                    <XIcon v-else class="w-5 h-5" />
                </button>
            </div>
        </header>

        <!-- Mobile Menu Overlay -->
        <transition
            enter-active-class="transition-all duration-300 ease-in-out"
            enter-from-class="opacity-0 -translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-300 ease-in-out"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-4"
        >
            <div 
                v-if="mobileMenuOpen" 
                class="fixed inset-0 top-16 bg-white z-40 lg:hidden overflow-y-auto p-6 space-y-8"
            >
                <div class="space-y-4">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-4">Navigation</p>
                    <div class="grid grid-cols-1 gap-2">
                        <Link 
                            v-for="item in navigation" 
                            :key="item.name"
                            :href="item.href"
                            @click="mobileMenuOpen = false"
                            :class="cn(
                                'flex items-center gap-4 px-5 h-14 rounded-2xl text-sm font-black uppercase tracking-widest transition-all',
                                item.current ? 'bg-primary text-white' : 'bg-muted text-muted-foreground'
                            )"
                        >
                            <component :is="item.icon" class="w-5 h-5" />
                            {{ item.name }}
                        </Link>
                    </div>
                </div>

                <div v-for="dropdown in managementDropdowns" :key="dropdown.name" class="space-y-4">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground/60 px-4">{{ dropdown.name }}</p>
                    <div class="grid grid-cols-2 gap-3">
                        <Link 
                            v-for="sub in dropdown.items" 
                            :key="sub.name"
                            :href="sub.href"
                            @click="mobileMenuOpen = false"
                            class="flex flex-col items-center justify-center gap-2 p-4 rounded-2xl bg-muted text-muted-foreground hover:text-primary transition-all"
                        >
                            <component :is="sub.icon" class="w-5 h-5" />
                            <span class="text-[10px] font-bold text-center">{{ sub.name }}</span>
                        </Link>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto bg-background p-6">
            <div class="max-w-[1400px] mx-auto min-h-full flex flex-col">
                <!-- Page Title -->
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-black tracking-tight text-foreground uppercase">{{ activeNavName }}</h1>
                        <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest mt-1">Management Portal</p>
                    </div>
                </div>

                <!-- Page Content Slot -->
                <div class="flex-1">
                    <slot />
                </div>

                <!-- Footer -->
                <footer class="mt-12 pt-8 pb-4 border-t border-border/50">
                    <p class="text-[10px] font-bold text-muted-foreground/40 text-center uppercase tracking-[0.3em]">
                        &copy; {{ new Date().getFullYear() }} {{ $page.props.system?.name || 'POS System' }} • All Rights Reserved
                    </p>
                </footer>
            </div>
        </main>
    </div>
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