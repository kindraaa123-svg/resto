<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { 
    CreditCard, 
    Utensils, 
    ListTodo, 
    Search,
    ChevronRight,
    ShoppingBag,
    Plus,
    Minus,
    Trash2,
    X,
    CheckCircle2,
    Wallet,
    Tag,
    Building,
    UtensilsCrossed,
    Package,
    Barcode,
    XCircle,
    MessageSquareText,
    Camera,
    CameraOff,
    Users as UsersIcon,
    UserCircle,
    Smartphone,
    Languages
} from '@lucide/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import axios from 'axios'
import Button from '@/Components/ui/Button.vue'
import Input from '@/Components/ui/Input.vue'
import Card from '@/Components/ui/Card.vue'
import Modal from '@/Components/ui/Modal.vue'
import QrcodeVue from 'qrcode.vue'
import { Html5QrcodeScanner, Html5Qrcode } from 'html5-qrcode'
import { cn } from '@/lib/utils'

const page = usePage()
const props = defineProps({
    categories: Array,
    packages: Array,
    products: Array,
    unpaidOrders: Array,
    vacantTables: Array,
    activePromotions: Array,
    errors: Object,
    auth: Object,
    ziggy: Object,
    system: Object,
    locale: String,
    translations: Object,
})

const ui = computed(() => page.props.translations?.ui || {})

const activeTab = ref('menu') // 'menu' or 'unpaid'
const activeType = ref('menu') // 'menu', 'package', or 'promo'
const searchQuery = ref('')
const activeCategory = ref(null)

// Payment State
const paymentMethod = ref('Cash')
const amountPaid = ref(0)
const orderType = ref('Table Order')
const customerName = ref('')

// Cart State
const cart = ref([])
const selectedTable = ref(null)
const activePromotion = ref(null)
const activeOrderId = ref(null)

// Watchers
watch(selectedTable, (newTable) => {
    if (newTable) {
        orderType.value = 'Table Order'
    } else {
        orderType.value = 'Takeaway'
    }
})

// Formatting
const formatPrice = (price) => {
    return new Intl.NumberFormat(page.props.locale === 'id' ? 'id-ID' : 'en-US', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(price || 0)
}

// Cart Logic
const cartSubtotal = computed(() => {
    return cart.value.reduce((total, item) => total + item.total_price, 0)
})

const cartDiscount = computed(() => {
    if (!activePromotion.value) return 0
    if (activePromotion.value.type === 'percentage') {
        return cartSubtotal.value * (activePromotion.value.value / 100)
    }
    return activePromotion.value.value
})

const cartTax = computed(() => {
    const afterDiscount = Math.max(0, cartSubtotal.value - cartDiscount.value)
    return afterDiscount * 0.11
})

const cartTotal = computed(() => {
    const afterDiscount = Math.max(0, cartSubtotal.value - cartDiscount.value)
    return afterDiscount + cartTax.value
})

const cartChange = computed(() => {
    return Math.max(0, (amountPaid.value || 0) - cartTotal.value)
})

// Watch cart total to update amountPaid default
watch(cartTotal, (newTotal) => {
    amountPaid.value = newTotal
}, { immediate: true })

// Form State
const showAddonModal = ref(false)
const showPaymentModal = ref(false)
const showTableModal = ref(false)
const showPromoListModal = ref(false)
const showPromoModal = ref(false)
const selectedMenuForAddons = ref(null)
const selectedPromo = ref(null)
const selectedQuantity = ref(1)
const selectedAddons = ref([])

// Camera/Scanner State
const showCameraModal = ref(false)
const showDeviceListModal = ref(false)
const pairingCode = ref(localStorage.getItem('pos_pairing_code') || Math.random().toString(36).substring(2, 8).toUpperCase())
const activeDevices = ref([])
const guestDevices = ref([])
const isRequestingRemote = ref(false)
const networkId = ref(null)
const scannerUrl = computed(() => route('staff.mobile-scanner'))
let html5QrScanner = null

// Initialize Pairing
onMounted(() => {
    localStorage.setItem('pos_pairing_code', pairingCode.value)
    
    // Listen for remote staff scans
    window.Echo.channel(`staff-scan.${pairingCode.value}`)
        .listen('.staff.scanned', (e) => {
            processBarcode(e.barcode)
            // Visual feedback
            if (window.navigator.vibrate) window.navigator.vibrate([50, 50, 50])
        })
})

// UI State
const isMobileView = ref(false)
const showMobileOrder = ref(false)

const checkViewSize = () => {
    isMobileView.value = window.innerWidth < 1024
}

const toggleMobileOrder = () => {
    showMobileOrder.value = !showMobileOrder.value
}

const handleMenuClick = (item) => {
    if (item.type === 'promo' || item.packageid) {
        selectedPromo.value = item
        showPromoModal.value = true
    } else {
        selectedMenuForAddons.value = item
        selectedQuantity.value = 1
        selectedAddons.value = []
        showAddonModal.value = true
    }
}

const toggleAddon = (addon) => {
    const index = selectedAddons.value.findIndex(a => a.addonid === addon.addonid)
    if (index > -1) {
        selectedAddons.value.splice(index, 1)
    } else {
        selectedAddons.value.push(addon)
    }
}

const confirmAddons = () => {
    const addonPrice = selectedAddons.value.reduce((total, a) => total + a.price, 0)
    const totalPrice = (selectedMenuForAddons.value.price + addonPrice) * selectedQuantity.value
    
    cart.value.push({
        ...selectedMenuForAddons.value,
        qty: selectedQuantity.value,
        selected_addons: [...selectedAddons.value],
        addon_name: selectedAddons.value.map(a => a.name).join(', '),
        total_price: totalPrice
    })
    
    showAddonModal.value = false
}

const addToCart = (item) => {
    cart.value.push({
        ...item,
        qty: 1,
        total_price: item.price
    })
}

const removeFromCart = (index) => {
    cart.value.splice(index, 1)
}

const incrementQty = (index) => {
    const item = cart.value[index]
    item.qty++
    updateItemTotal(index)
}

const decrementQty = (index) => {
    const item = cart.value[index]
    if (item.qty > 1) {
        item.qty--
        updateItemTotal(index)
    }
}

const updateItemTotal = (index) => {
    const item = cart.value[index]
    const addonPrice = (item.selected_addons || []).reduce((total, a) => total + a.price, 0)
    item.total_price = (item.price + addonPrice) * item.qty
}

const handleUnpaidOrderClick = (order) => {
    // Fill cart with order details
    cart.value = order.details.map(d => {
        const itemData = d.menu || d.product || {}
        return {
            ...itemData,
            qty: d.qty,
            selected_addons: d.addons || [],
            addon_name: d.addons?.map(a => a.name).join(', ') || '',
            total_price: d.total, // Total for this item calculated in controller
            order_detail_id: d.orderdetailid
        }
    })
    
    // Set order context
    selectedTable.value = { tableid: order.tableid, tablename: order.tablename }
    activeOrderId.value = order.orderid
    activeTab.value = 'menu' // Switch back to menu to see the cart
}

const clearCart = () => {
    cart.value = []
    selectedTable.value = null
    activePromotion.value = null
    activeOrderId.value = null
    customerName.value = ''
}

// Computed Filtered Items
const filteredItems = computed(() => {
    let items = []
    
    if (activeType.value === 'menu') {
        props.categories.forEach(cat => {
            if (!activeCategory.value || activeCategory.value === cat.id) {
                cat.menus.forEach(menu => {
                    items.push({ ...menu, categoryname: cat.name, type: 'menu' })
                })
            }
        })
    } else if (activeType.value === 'package') {
        props.packages.forEach(pkg => {
            items.push({ ...pkg, type: 'package', categoryname: 'Package' })
        })
    } else if (activeType.value === 'promo') {
        props.activePromotions.forEach(promo => {
            items.push({ 
                ...promo, 
                type: 'promo', 
                categoryname: 'Promotion',
                promo_type: promo.type, // BUY_X_GET_Y, DISCOUNT_PERCENT, etc
                buy_menu_name: promo.buy_menu?.name,
                get_menu_name: promo.get_menu?.name
            })
        })
    }

    // Filter by Search Query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        items = items.filter(item => 
            (item.name && item.name.toLowerCase().includes(query)) || 
            (item.barcode && item.barcode.includes(query))
        )
    }

    return items
})

// Barcode Hardware Scanner Support
let barcodeBuffer = ''
let lastKeyTime = 0

const handleGlobalKeyDown = (e) => {
    // Ignore if typing in an input or textarea
    const target = e.target
    if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA') {
        return
    }

    const currentTime = Date.now()
    
    // Hardware scanners type very fast. If the interval between keys is > 50ms, it's likely a human.
    if (currentTime - lastKeyTime > 50) {
        barcodeBuffer = ''
    }
    
    lastKeyTime = currentTime

    if (e.key === 'Enter') {
        if (barcodeBuffer.length > 2) {
            processBarcode(barcodeBuffer)
            barcodeBuffer = ''
            e.preventDefault()
        }
    } else if (e.key.length === 1) {
        barcodeBuffer += e.key
    }
}

// Lifecycle
onMounted(() => {
    checkViewSize()
    window.addEventListener('resize', checkViewSize)
    window.addEventListener('keydown', handleGlobalKeyDown)
})

onUnmounted(() => {
    window.removeEventListener('resize', checkViewSize)
    window.removeEventListener('keydown', handleGlobalKeyDown)
})

const processPayment = async () => {
    try {
        const response = await axios.post(route('api.cashier.pay'), {
            orderId: null, // New order from POS
            paymentMethod: paymentMethod.value,
            amountPaid: amountPaid.value,
            totalPrice: cartTotal.value,
            items: cart.value.map(item => ({
                id: item.menuid || item.id,
                is_package: item.type === 'package',
                is_product: item.type === 'product',
                quantity: item.qty,
                addons: item.selected_addons?.map(a => ({ addonid: a.addonid || a.id })) || []
            })),
            tableId: selectedTable.value?.tableid || null,
            orderType: orderType.value,
            appliedPromotionId: activePromotion.value?.id || null,
            appliedPromotionName: activePromotion.value?.name || null,
            appliedPromotionDiscount: cartDiscount.value
        })

        if (response.data.success) {
            const orderId = response.data.orderId
            window.open(route('api.cashier.receipt', { order: orderId }), '_blank')
            clearCart()
            showPaymentModal.value = false
        }
    } catch (error) {
        console.error('Payment processing failed', error)
        alert('Payment failed: Please check the console for details.')
    }
}

const applyPromo = (promo) => {
    activePromotion.value = promo
    showPromoListModal.value = false
}

// Camera Barcode Scanning
const cameras = ref([])
const selectedCameraId = ref(null)

const processBarcode = async (barcode) => {
    if (!barcode) return
    
    // Find menu by barcode
    let found = null
    props.categories.forEach(cat => {
        cat.menus.forEach(menu => {
            if (menu.barcode === barcode) found = { ...menu, categoryname: cat.categoryname }
        })
    })

    if (found) {
        handleMenuClick(found)
    } else {
        // Try searching in products if not in menu
        const product = props.products.find(p => p.barcode === barcode)
        if (product) {
            addToCart({ ...product, type: 'product' })
        }
    }
}

const onManualBarcodeSubmit = () => {
    processBarcode(searchQuery.value)
    searchQuery.value = ''
}

const startCamera = async () => {
    showCameraModal.value = true
    await nextTick()
    
    const devices = await Html5Qrcode.getCameras()
    cameras.value = devices
    
    if (devices.length > 0) {
        html5QrScanner = new Html5Qrcode("reader")
        const config = { fps: 10, qrbox: { width: 250, height: 250 } }
        
        html5QrScanner.start(
            { facingMode: "environment" }, 
            config, 
            (decodedText) => {
                processBarcode(decodedText)
                stopCamera()
            }
        )
    }
}

const stopCamera = () => {
    if (html5QrScanner) {
        html5QrScanner.stop().then(() => {
            showCameraModal.value = false
        })
    } else {
        showCameraModal.value = false
    }
}

</script>

<template>
    <AppLayout>
        <Head :title="ui.payments || 'POS Payments'" />

        <div class="flex flex-col lg:flex-row h-screen -m-6 lg:-m-8 overflow-hidden bg-[#F5F5F5]">
            <!-- Left: Categories (Scrollable) -->
            <div class="hidden lg:flex w-[200px] flex-col border-r border-[#D1D1D1] bg-white shrink-0">
                <div class="p-6 border-b border-[#D1D1D1] bg-white shrink-0">
                    <h2 class="text-sm font-bold text-[#2D2D2D] uppercase tracking-wider">{{ ui.categories || 'Menu' }}</h2>
                </div>
                <div class="flex-1 overflow-y-auto p-4 space-y-4 no-scrollbar">
                    <!-- Standard Menu -->
                    <div class="space-y-1">
                        <p class="px-4 text-[10px] font-bold text-[#666666] uppercase tracking-widest mb-2">{{ ui.food_drinks || 'Food & Drinks' }}</p>
                        <button 
                            @click="activeCategory = null; activeType = 'menu'"
                            :class="cn(
                                'w-full flex items-center h-[40px] px-4 rounded-[6px] text-sm font-semibold transition-all',
                                (!activeCategory && activeType === 'menu') ? 'bg-primary text-white shadow-md' : 'text-[#4A4A4A] hover:bg-[#F5F5F5] hover:text-primary'
                            )"
                        >
                            {{ ui.all_menu || 'Semua Menu' }}
                        </button>
                        <button 
                            v-for="cat in categories" 
                            :key="cat.id"
                            @click="activeCategory = cat.id; activeType = 'menu'"
                            :class="cn(
                                'w-full flex items-center h-[40px] px-4 rounded-[6px] text-sm font-semibold transition-all',
                                (activeCategory === cat.id && activeType === 'menu') ? 'bg-primary text-white shadow-md' : 'text-[#4A4A4A] hover:bg-[#F5F5F5] hover:text-primary'
                            )"
                        >
                            {{ cat.name || 'Unnamed Category' }}
                        </button>
                    </div>

                    <!-- Packages Section -->
                    <div class="space-y-1 pt-2 border-t border-[#D1D1D1]">
                        <p class="px-4 text-[10px] font-bold text-[#666666] uppercase tracking-widest mb-2">{{ ui.special_deals || 'Special Deals' }}</p>
                        <button 
                            @click="activeCategory = null; activeType = 'package'"
                            :class="cn(
                                'w-full flex items-center h-[40px] px-4 rounded-[6px] text-sm font-semibold transition-all',
                                activeType === 'package' ? 'bg-primary text-white shadow-md' : 'text-[#4A4A4A] hover:bg-[#F5F5F5] hover:text-primary'
                            )"
                        >
                            <Package class="w-4 h-4 mr-2" />
                            {{ ui.packages || 'Packages' }}
                        </button>
                        <button 
                            @click="activeCategory = null; activeType = 'promo'"
                            :class="cn(
                                'w-full flex items-center h-[40px] px-4 rounded-[6px] text-sm font-semibold transition-all',
                                activeType === 'promo' ? 'bg-primary text-white shadow-md' : 'text-[#4A4A4A] hover:bg-[#F5F5F5] hover:text-primary'
                            )"
                        >
                            <Tag class="w-4 h-4 mr-2" />
                            {{ ui.promotions || 'Promotions' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Center: Product Grid -->
            <div class="flex-1 flex flex-col min-w-0 h-full">
                <!-- Topbar for Search & Tabs -->
                <header class="h-[72px] shrink-0 bg-white border-b border-[#E6E6E6] flex items-center px-6 gap-6 sticky top-0 z-10">
                    <!-- Tab Switcher -->
                    <div class="flex bg-[#F5F5F5] p-1 rounded-xl shrink-0">
                        <button 
                            @click="activeTab = 'menu'"
                            :class="cn(
                                'px-6 py-2 rounded-lg text-xs font-black uppercase tracking-widest transition-all',
                                activeTab === 'menu' ? 'bg-white text-primary shadow-sm' : 'text-[#999999] hover:text-primary'
                            )"
                        >
                            {{ ui.menu || 'Menu' }}
                        </button>
                        <button 
                            @click="activeTab = 'unpaid'"
                            :class="cn(
                                'px-6 py-2 rounded-lg text-xs font-black uppercase tracking-widest transition-all relative',
                                activeTab === 'unpaid' ? 'bg-white text-primary shadow-sm' : 'text-[#999999] hover:text-primary'
                            )"
                        >
                            {{ ui.unpaid || 'Unpaid' }}
                            <span v-if="unpaidOrders.length > 0" class="absolute -top-1 -right-1 w-5 h-5 bg-destructive text-white text-[10px] flex items-center justify-center rounded-full border-2 border-white animate-pulse">
                                {{ unpaidOrders.length }}
                            </span>
                        </button>
                    </div>

                    <div class="flex-1 relative max-w-md">
                        <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-[#999999]" />
                        <Input 
                            v-model="searchQuery" 
                            :placeholder="ui.search_menu || 'Search menu...'"
                            class="pl-12 bg-[#F5F5F5] border-none"
                        />
                    </div>
                    
                    <!-- Mobile Category Tabs (Horizontal Scroll) -->
                    <div class="lg:hidden flex-1 overflow-x-auto flex gap-2 no-scrollbar px-2">
                         <button 
                            @click="activeCategory = null"
                            :class="cn(
                                'whitespace-nowrap px-4 py-2 rounded-full text-xs font-bold uppercase transition-all',
                                !activeCategory ? 'bg-primary text-white' : 'bg-white text-primary border border-primary/20'
                            )"
                        >
                            {{ ui.all || 'All' }}
                        </button>
                        <button 
                            v-for="cat in categories" 
                            :key="cat.categoryid"
                            @click="activeCategory = cat.categoryid"
                            :class="cn(
                                'whitespace-nowrap px-4 py-2 rounded-full text-xs font-bold uppercase transition-all',
                                activeCategory === cat.categoryid ? 'bg-primary text-white' : 'bg-white text-primary border border-primary/20'
                            )"
                        >
                            {{ cat.categoryname }}
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
                        <Button variant="secondary" size="icon" @click="showDeviceListModal = true">
                            <Camera class="w-5 h-5" />
                        </Button>
                        <Button v-if="isMobileView" variant="primary" size="icon" @click="toggleMobileOrder" class="relative">
                            <ShoppingBag class="w-5 h-5" />
                            <span v-if="cart.length > 0" class="absolute -top-1 -right-1 w-5 h-5 bg-destructive text-white text-[10px] flex items-center justify-center rounded-full font-black border-2 border-white">{{ cart.length }}</span>
                        </Button>
                    </div>
                </header>

                <!-- Main Content (Scrollable) -->
                <div class="flex-1 overflow-y-auto p-6 no-scrollbar pb-24">
                    <!-- Menu View -->
                    <div v-if="activeTab === 'menu'" class="grid grid-cols-2 lg:grid-cols-3 gap-6 animate-in fade-in duration-500">
                        <Card 
                            v-for="menu in filteredItems" 
                            :key="menu.menuid || menu.packageid || menu.promotionid || menu.id"
                            class="flex flex-col overflow-hidden cursor-pointer group h-full border-[#5BA3C3]/30 min-h-[280px]"
                            @click="handleMenuClick(menu)"
                        >
                            <!-- Image/Icon Header -->
                            <div class="aspect-[4/3] relative bg-[#F5F5F5] shrink-0 overflow-hidden flex items-center justify-center">
                                <template v-if="menu.image_url">
                                    <img 
                                        :src="menu.image_url" 
                                        class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                                    />
                                </template>
                                <template v-else>
                                    <div class="w-full h-full flex flex-col items-center justify-center gap-3 bg-primary/5 text-primary">
                                        <Package v-if="menu.type === 'package'" class="w-12 h-12 opacity-40" />
                                        <Tag v-else-if="menu.type === 'promo'" class="w-12 h-12 opacity-40" />
                                        <Utensils v-else class="w-12 h-12 opacity-40" />
                                        <span v-if="menu.type !== 'menu'" class="text-[9px] font-black uppercase tracking-[0.2em] opacity-60">{{ menu.type }}</span>
                                    </div>
                                </template>
                                
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/5 transition-colors duration-300"></div>
                                <div class="absolute top-3 right-3 translate-y-2 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-300">
                                    <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center shadow-lg border-2 border-white">
                                        <Plus class="w-5 h-5" />
                                    </div>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="p-5 flex flex-col flex-1">
                                <div class="space-y-1 mb-3">
                                    <h3 class="text-xs font-bold text-[#2D2D2D] line-clamp-2 uppercase tracking-tight leading-tight group-hover:text-primary transition-colors">
                                        {{ menu.name || menu.packagename }}
                                    </h3>
                                    <!-- Promo/Package Specific Subtext -->
                                    <div v-if="menu.type === 'promo'" class="space-y-1">
                                        <p class="text-[10px] font-black text-[#30C55D] uppercase tracking-wide">
                                            {{ menu.promo_type?.replace(/_/g, ' ') }}
                                        </p>
                                        <p v-if="menu.promo_type === 'BUY_X_GET_Y'" class="text-[9px] font-semibold text-[#666666] leading-tight">
                                            Beli <span class="text-primary">{{ menu.buy_qty }} {{ menu.buy_menu_name }}</span> 
                                            Gratis <span class="text-[#30C55D]">{{ menu.get_qty }} {{ menu.get_menu_name }}</span>
                                        </p>
                                        <p v-if="menu.min_purchase > 0" class="text-[9px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-sm inline-block mt-1">
                                            Min. Beli {{ formatPrice(menu.min_purchase) }}
                                        </p>
                                    </div>
                                    <p v-if="menu.type === 'package'" class="text-[10px] font-medium text-primary uppercase tracking-wide">
                                        {{ menu.menus?.length }} Items Included
                                    </p>
                                </div>

                                <div class="mt-auto pt-4 border-t border-[#F5F5F5] flex flex-col gap-1">
                                    <span class="text-[10px] font-bold text-[#999999] uppercase tracking-tighter truncate">{{ menu.categoryname }}</span>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-black text-primary tracking-tight">
                                            {{ menu.type === 'promo' && menu.promo_type === 'DISCOUNT_PERCENT' ? menu.discount_value + '%' : formatPrice(menu.price) }}
                                        </span>
                                        <span v-if="menu.type === 'promo' && menu.min_purchase > 0" class="text-[8px] font-bold text-[#999999] uppercase">Min {{ formatPrice(menu.min_purchase) }}</span>
                                    </div>
                                </div>
                            </div>
                        </Card>
                    </div>

                    <!-- Unpaid Orders View -->
                    <div v-else class="space-y-4 animate-in slide-in-from-bottom-4 duration-500">
                        <div v-if="unpaidOrders.length === 0" class="h-64 flex flex-col items-center justify-center text-center opacity-30">
                            <ListTodo class="w-16 h-16 mb-4 text-[#999999]" />
                            <p class="text-sm font-black uppercase tracking-[0.2em]">{{ ui.no_unpaid_orders || 'No unpaid orders found' }}</p>
                        </div>
                        
                        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <Card 
                                v-for="order in unpaidOrders" 
                                :key="order.orderid"
                                class="p-6 border-l-4 border-l-amber-500 hover:shadow-xl transition-all cursor-pointer group"
                                @click="handleUnpaidOrderClick(order)"
                            >
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <UtensilsCrossed class="w-4 h-4 text-primary" />
                                            <span class="text-sm font-black text-[#2D2D2D] uppercase tracking-wider">{{ order.tablename || 'No Table' }}</span>
                                        </div>
                                        <p class="text-[10px] font-bold text-[#999999] uppercase tracking-widest">{{ order.orderno }} • {{ new Date(order.created_at).toLocaleTimeString() }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-lg font-black text-primary tracking-tighter">{{ formatPrice(order.total) }}</span>
                                        <p class="text-[9px] font-bold text-amber-600 uppercase tracking-widest">{{ order.details_count || 0 }} Items</p>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between pt-4 border-t border-[#F5F5F5]">
                                    <div class="flex -space-x-2">
                                        <div v-for="i in Math.min(order.details_count, 3)" :key="i" class="w-8 h-8 rounded-full bg-[#F5F5F5] border-2 border-white flex items-center justify-center">
                                            <Utensils class="w-4 h-4 text-[#999999] opacity-40" />
                                        </div>
                                        <div v-if="order.details_count > 3" class="w-8 h-8 rounded-full bg-primary/10 border-2 border-white flex items-center justify-center text-[10px] font-black text-primary">
                                            +{{ order.details_count - 3 }}
                                        </div>
                                    </div>
                                    <Button size="sm" class="uppercase tracking-widest text-[9px] font-black gap-2">
                                        {{ ui.pay_now || 'Pay Now' }}
                                        <ChevronRight class="w-3 h-3 transition-transform group-hover:translate-x-1" />
                                    </Button>
                                </div>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Bill Summary / Cart (Fixed Width) -->
            <div class="hidden lg:flex w-[380px] flex-col border-l border-[#E6E6E6] bg-white shadow-[-4px_0_12px_rgba(0,0,0,0.02)] shrink-0">
                <div class="p-6 border-b border-[#E6E6E6] shrink-0 flex items-center justify-between bg-white">
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-3">
                            <ShoppingBag class="w-5 h-5 text-primary" />
                            <h2 class="text-sm font-bold text-[#2D2D2D] uppercase tracking-wider">{{ ui.current_order || 'Current Order' }}</h2>
                        </div>
                        <button @click="showTableModal = true" class="text-[10px] font-black text-primary uppercase tracking-[0.2em] flex items-center gap-1 hover:opacity-70 transition-all">
                            <UtensilsCrossed class="w-3 h-3" />
                            {{ selectedTable ? selectedTable.tablename : 'Select Table' }}
                        </button>
                    </div>
                    <Button variant="secondary" size="sm" @click="clearCart" v-if="cart.length > 0">
                        <Trash2 class="w-4 h-4 mr-2" />
                        {{ ui.clear || 'Clear' }}
                    </Button>
                </div>

                <!-- Cart Items (Scrollable) -->
                <div class="flex-1 overflow-y-auto p-6 space-y-4 no-scrollbar">
                    <div v-if="cart.length === 0" class="h-full flex flex-col items-center justify-center text-center opacity-40">
                        <ShoppingBag class="w-16 h-16 mb-4 text-[#E6E6E6]" />
                        <p class="text-sm font-semibold text-[#666666] uppercase tracking-widest">{{ ui.no_items || 'No items selected' }}</p>
                    </div>
                    
                    <div v-for="(item, index) in cart" :key="index" class="flex gap-4 group animate-in fade-in slide-in-from-right-4 duration-300">
                        <div class="w-16 h-16 rounded-[8px] overflow-hidden bg-[#F5F5F5] shrink-0 border border-[#E6E6E6]">
                            <img v-if="item.image_url" :src="item.image_url" class="w-full h-full object-cover" />
                            <Utensils v-else class="w-full h-full p-4 text-[#E6E6E6]" />
                        </div>
                        <div class="flex-1 min-w-0 flex flex-col py-1">
                            <div class="flex justify-between items-start gap-2">
                                <h4 class="text-xs font-bold text-[#2D2D2D] truncate uppercase tracking-tight">{{ item.name }}</h4>
                                <span class="text-xs font-bold text-primary shrink-0 tracking-tighter">{{ formatPrice(item.total_price) }}</span>
                            </div>
                            <p v-if="item.addon_name" class="text-[9px] text-primary font-bold uppercase mt-0.5">+ {{ item.addon_name }}</p>
                            
                            <div class="mt-auto flex items-center justify-between">
                                <div class="flex items-center bg-[#F5F5F5] rounded-full px-1 py-1 gap-4">
                                    <button @click="decrementQty(index)" class="w-6 h-6 flex items-center justify-center text-primary hover:bg-[#E6E6E6] rounded-full transition-colors">
                                        <Minus class="w-3 h-3" />
                                    </button>
                                    <span class="text-xs font-black text-[#2D2D2D] min-w-[20px] text-center">{{ item.qty }}</span>
                                    <button @click="incrementQty(index)" class="w-6 h-6 flex items-center justify-center text-primary hover:bg-[#E6E6E6] rounded-full transition-colors">
                                        <Plus class="w-3 h-3" />
                                    </button>
                                </div>
                                <button @click="removeFromCart(index)" class="lg:opacity-0 group-hover:opacity-100 p-2 text-destructive hover:bg-destructive/5 rounded-full transition-all">
                                    <Trash2 class="w-3 h-3" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary & Checkout (Fixed Bottom) -->
                <div class="p-6 border-t border-[#E6E6E6] bg-white space-y-4 shrink-0">
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-medium text-[#666666] uppercase tracking-wider">{{ ui.subtotal || 'Subtotal' }}</span>
                            <span class="text-xs font-bold text-[#2D2D2D] tracking-tighter">{{ formatPrice(cartSubtotal) }}</span>
                        </div>
                        <div v-if="activePromotion" class="flex justify-between items-center text-[#30C55D]">
                            <span class="text-xs font-medium uppercase tracking-wider">{{ ui.discount || 'Discount' }}</span>
                            <span class="text-xs font-bold tracking-tighter">-{{ formatPrice(cartDiscount) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-[#666666]">
                            <span class="text-xs font-medium uppercase tracking-wider">{{ ui.tax || 'Tax (11%)' }}</span>
                            <span class="text-xs font-bold tracking-tighter">{{ formatPrice(cartTax) }}</span>
                        </div>
                        <div class="flex justify-between items-center pt-2 border-t border-[#F5F5F5]">
                            <span class="text-sm font-bold text-[#2D2D2D] uppercase tracking-widest">{{ ui.total || 'Total' }}</span>
                            <span class="text-lg font-bold text-[#30C55D] tracking-tighter">{{ formatPrice(cartTotal) }}</span>
                        </div>
                    </div>

                    <Button 
                        @click="showPaymentModal = true" 
                        class="w-full h-14 uppercase tracking-[0.2em] text-xs font-black shadow-lg shadow-primary/20"
                        :disabled="cart.length === 0"
                    >
                        {{ ui.process_checkout || 'Process Checkout' }}
                    </Button>
                </div>
            </div>

            <!-- Mobile Cart Modal -->
            <transition
                enter-active-class="transition-transform duration-300 ease-out"
                enter-from-class="translate-y-full"
                enter-to-class="translate-y-0"
                leave-active-class="transition-transform duration-200 ease-in"
                leave-from-class="translate-y-0"
                leave-to-class="translate-y-full"
            >
                <div v-if="isMobileView && showMobileOrder" class="fixed inset-0 z-50 flex flex-col bg-white lg:hidden">
                    <div class="p-6 border-b border-[#E6E6E6] flex items-center justify-between bg-white shrink-0">
                        <div class="flex items-center gap-3">
                            <ShoppingBag class="w-5 h-5 text-primary" />
                            <h2 class="text-lg font-bold text-[#2D2D2D] uppercase tracking-wider">{{ ui.my_order || 'My Order' }}</h2>
                        </div>
                        <Button variant="secondary" size="icon" @click="toggleMobileOrder">
                            <X class="w-5 h-5" />
                        </Button>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto p-6 space-y-4 no-scrollbar">
                        <div v-for="(item, index) in cart" :key="index" class="flex gap-4">
                            <div class="w-16 h-16 rounded-[8px] overflow-hidden bg-[#F5F5F5] shrink-0 border border-[#E6E6E6]">
                                <img v-if="item.image_url" :src="item.image_url" class="w-full h-full object-cover" />
                                <Utensils v-else class="w-full h-full p-4 text-[#E6E6E6]" />
                            </div>
                            <div class="flex-1 min-w-0 flex flex-col py-1">
                                <div class="flex justify-between items-start gap-2">
                                    <h4 class="text-sm font-bold text-[#2D2D2D] truncate uppercase tracking-tight">{{ item.name }}</h4>
                                    <span class="text-sm font-bold text-primary shrink-0 tracking-tighter">{{ formatPrice(item.total_price) }}</span>
                                </div>
                                <p v-if="item.addon_name" class="text-[10px] text-primary font-bold uppercase mt-0.5">+ {{ item.addon_name }}</p>
                                <div class="mt-auto flex items-center justify-between">
                                    <div class="flex items-center bg-[#F5F5F5] rounded-full px-2 py-1 gap-6">
                                        <button @click="decrementQty(index)" class="w-8 h-8 flex items-center justify-center text-primary bg-white shadow-sm rounded-full">
                                            <Minus class="w-4 h-4" />
                                        </button>
                                        <span class="text-sm font-black text-[#2D2D2D]">{{ item.qty }}</span>
                                        <button @click="incrementQty(index)" class="w-8 h-8 flex items-center justify-center text-primary bg-white shadow-sm rounded-full">
                                            <Plus class="w-4 h-4" />
                                        </button>
                                    </div>
                                    <button @click="removeFromCart(index)" class="p-2 text-destructive">
                                        <Trash2 class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 border-t border-[#E6E6E6] bg-white space-y-4 shrink-0 pb-10">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-bold text-[#2D2D2D] uppercase tracking-wider">{{ ui.total || 'Total' }}</span>
                            <span class="text-xl font-bold text-[#30C55D] tracking-tighter">{{ formatPrice(cartTotal) }}</span>
                        </div>
                        <Button 
                            @click="showPaymentModal = true" 
                            class="w-full h-14 uppercase tracking-[0.2em] text-xs font-black shadow-lg shadow-primary/20"
                            :disabled="cart.length === 0"
                        >
                            {{ ui.process_checkout || 'Process Checkout' }}
                        </Button>
                    </div>
                </div>
            </transition>
        </div>

        <!-- Addon Selection Modal -->
        <Modal :show="showAddonModal" :title="ui.customize_order || 'Customize Order'" @close="showAddonModal = false">
            <div class="p-6 space-y-6">
                <!-- Selected Item Preview -->
                <div class="flex gap-4 p-4 bg-secondary/30 rounded-3xl border border-secondary/50">
                    <div class="w-20 h-20 rounded-2xl bg-card overflow-hidden shrink-0 shadow-sm border border-secondary/30">
                        <img v-if="selectedMenuForAddons?.image_url" :src="selectedMenuForAddons.image_url" class="w-full h-full object-cover" />
                        <Utensils v-else class="w-full h-full p-6 text-muted-foreground/30" />
                    </div>
                    <div class="flex-1 min-w-0 flex flex-col justify-center">
                        <span class="text-[10px] font-black text-primary uppercase tracking-widest mb-1">{{ selectedMenuForAddons?.categoryname }}</span>
                        <h3 class="text-lg font-black text-foreground uppercase tracking-tight truncate">{{ selectedMenuForAddons?.name }}</h3>
                        <p class="text-sm font-black text-primary tracking-tighter mt-1">{{ formatPrice(selectedMenuForAddons?.price) }}</p>
                    </div>
                </div>

                <!-- Quantity Selection -->
                <div class="space-y-3">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground ml-1">{{ ui.order_quantity || 'Order Quantity' }}</h3>
                    <div class="flex items-center justify-center gap-8 bg-secondary/30 p-4 rounded-3xl border border-secondary/50">
                        <button 
                            @click="selectedQuantity > 1 ? selectedQuantity-- : null"
                            class="w-12 h-12 flex items-center justify-center bg-card rounded-2xl text-primary shadow-md hover:bg-primary hover:text-white transition-all"
                        >
                            <Minus class="w-6 h-6" />
                        </button>
                        <span class="text-3xl font-black text-primary w-12 text-center">{{ selectedQuantity }}</span>
                        <button 
                            @click="selectedQuantity++"
                            class="w-12 h-12 flex items-center justify-center bg-card rounded-2xl text-primary shadow-md hover:bg-primary hover:text-white transition-all"
                        >
                            <Plus class="w-6 h-6" />
                        </button>
                    </div>
                </div>

                <!-- Addons Grid -->
                <div v-if="selectedMenuForAddons?.addons?.length > 0" class="space-y-3">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground ml-1">{{ ui.available_addons || 'Available Add-ons' }}</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-64 overflow-y-auto no-scrollbar pr-2">
                        <button 
                            v-for="addon in selectedMenuForAddons.addons" 
                            :key="addon.id"
                            @click="addon.is_available ? toggleAddon(addon) : null"
                            :disabled="!addon.is_available"
                            :class="[
                                'flex items-center justify-between p-4 rounded-2xl border transition-all text-left group',
                                !addon.is_available ? 'opacity-40 grayscale cursor-not-allowed bg-secondary/30 border-secondary/50' : 
                                selectedAddons.find(a => a.addonid === (addon.addonid || addon.id)) ? 'bg-primary text-primary-foreground border-primary shadow-lg shadow-primary/20 scale-105' : 'bg-card text-foreground border-secondary/50 hover:border-primary/50'
                            ]"
                        >
                            <div class="flex items-center gap-3">
                                <Plus class="w-4 h-4" />
                                <span class="text-xs font-black uppercase tracking-widest">{{ addon.name }}</span>
                                <span v-if="!addon.is_available" class="text-[8px] font-black bg-destructive text-white px-2 py-0.5 rounded-full ml-2">{{ ui.sold_out || 'SOLD OUT' }}</span>
                            </div>
                            <span class="text-xs font-black">{{ formatPrice(addon.price) }}</span>
                        </button>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-6 border-t border-secondary/50">
                    <Button variant="ghost" @click="showAddonModal = false" class="rounded-xl font-bold uppercase tracking-widest text-[10px]">{{ ui.cancel || 'Cancel' }}</Button>
                    <Button @click="confirmAddons" class="px-8 h-12 rounded-2xl shadow-xl shadow-primary/30 font-black uppercase tracking-widest text-xs gap-2">
                        {{ ui.add_to_order || 'Add to Order' }}
                        <span class="px-2 py-0.5 rounded-md bg-white/20 text-[10px] font-black">{{ formatPrice((selectedMenuForAddons?.price + selectedAddons.reduce((sum, a) => sum + a.price, 0)) * selectedQuantity) }}</span>
                    </Button>
                </div>
            </div>
        </Modal>

        <!-- (Payment & Table Selection Modals should be here for full functionality) -->
        <Modal :show="showTableModal" :title="ui.select_table || 'Select Table'" @close="showTableModal = false">
             <div class="p-6 space-y-6">
                <div class="grid grid-cols-3 sm:grid-cols-4 gap-4">
                    <button 
                        v-for="table in vacantTables" 
                        :key="table.tableid || table.id"
                        @click="selectedTable = { tableid: (table.tableid || table.id), tablename: (table.name || table.tablename) }; showTableModal = false; showPaymentModal = true"
                        :class="cn(
                            'aspect-square flex flex-col items-center justify-center rounded-2xl border transition-all gap-2',
                            selectedTable?.tableid === (table.tableid || table.id) ? 'bg-primary text-white border-primary shadow-lg shadow-primary/20 scale-105' : 'bg-white border-[#E6E6E6] text-[#666666] hover:border-primary/50'
                        )"
                    >
                        <UtensilsCrossed class="w-6 h-6" />
                        <span class="text-xs font-bold uppercase tracking-tight">{{ table.name || table.tablename }}</span>
                    </button>
                </div>
             </div>
        </Modal>

        <!-- Promo List Modal -->
        <Modal :show="showPromoListModal" :title="ui.select_promo || 'Select Promo'" @close="showPromoListModal = false">
             <div class="p-6 space-y-4">
                <div v-if="activePromotions.length === 0" class="text-center py-8 opacity-40">
                    <Tag class="w-12 h-12 mx-auto mb-2" />
                    <p class="text-sm font-bold uppercase tracking-widest">No active promos</p>
                </div>
                <div v-else class="grid gap-3">
                    <button 
                        v-for="promo in activePromotions" 
                        :key="promo.id"
                        @click="applyPromo(promo)"
                        :class="cn(
                            'flex items-center justify-between p-4 rounded-2xl border transition-all text-left',
                            activePromotion?.id === promo.id ? 'bg-[#30C55D]/10 border-[#30C55D] text-[#30C55D]' : 'bg-white border-[#E6E6E6] hover:border-primary/50'
                        )"
                    >
                        <div class="flex items-center gap-3">
                            <Tag class="w-5 h-5" />
                            <div>
                                <span class="text-sm font-bold uppercase tracking-tight">{{ promo.name }}</span>
                                <p class="text-[10px] opacity-60">{{ promo.description }}</p>
                            </div>
                        </div>
                        <CheckCircle2 v-if="activePromotion?.id === promo.id" class="w-5 h-5" />
                    </button>
                    <button @click="applyPromo(null)" class="w-full py-3 text-xs font-bold text-destructive uppercase tracking-widest hover:bg-destructive/5 rounded-xl transition-all">
                        Remove Promo
                    </button>
                </div>
             </div>
        </Modal>

        <!-- Payment Modal -->
        <Modal :show="showPaymentModal" :title="ui.payment_summary || 'Payment Summary'" @close="showPaymentModal = false">
            <div class="p-6 space-y-6">
                <div class="bg-[#F5F5F5] p-6 rounded-3xl space-y-4">
                    <!-- Customer Name -->
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-[0.2em] text-[#666666] ml-1">Customer Name (Optional)</label>
                        <Input 
                            v-model="customerName"
                            placeholder="Enter name..."
                            class="bg-white border-none rounded-xl h-12 px-4 text-sm font-bold placeholder:font-medium placeholder:text-[#999999] focus:ring-2 focus:ring-primary/20"
                        />
                    </div>
                    
                    <!-- Table Selection (If Dine In) -->
                    <div v-if="orderType === 'Table Order'" class="space-y-2 pt-2 border-t border-[#E6E6E6]">
                        <label class="text-[10px] font-black uppercase tracking-[0.2em] text-[#666666] ml-1">Table Number</label>
                        <button 
                            @click="showPaymentModal = false; showTableModal = true"
                            class="w-full flex justify-between items-center bg-white rounded-xl h-12 px-4 transition-all hover:bg-primary/5 border border-transparent hover:border-primary/20"
                        >
                            <span :class="['text-sm font-bold uppercase', selectedTable ? 'text-primary' : 'text-[#999999]']">
                                {{ selectedTable ? selectedTable.tablename : 'Select a table...' }}
                            </span>
                            <ChevronRight class="w-4 h-4 text-[#999999]" />
                        </button>
                    </div>

                    <div class="flex justify-between items-center pt-4 border-t border-[#E6E6E6]">
                        <span class="text-lg font-bold text-[#2D2D2D] uppercase tracking-widest">Total Amount</span>
                        <span class="text-2xl font-bold text-[#30C55D]">{{ formatPrice(cartTotal) }}</span>
                    </div>
                </div>

                <!-- Order Type -->
                <div class="space-y-3">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground ml-1">Order Type</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <button 
                            @click="orderType = 'Table Order'"
                            :class="cn(
                                'h-16 flex items-center justify-center gap-2 rounded-2xl border-2 transition-all',
                                orderType === 'Table Order' ? 'border-primary text-primary bg-primary/5 shadow-lg shadow-primary/10' : 'border-[#E6E6E6] text-[#666666] hover:border-primary/50'
                            )"
                        >
                            <Utensils class="w-5 h-5" />
                            <span class="text-xs font-bold uppercase tracking-widest">Dine In</span>
                        </button>
                        <button 
                            @click="orderType = 'Takeaway'"
                            :class="cn(
                                'h-16 flex items-center justify-center gap-2 rounded-2xl border-2 transition-all',
                                orderType === 'Takeaway' ? 'border-primary text-primary bg-primary/5 shadow-lg shadow-primary/10' : 'border-[#E6E6E6] text-[#666666] hover:border-primary/50'
                            )"
                        >
                            <ShoppingBag class="w-5 h-5" />
                            <span class="text-xs font-bold uppercase tracking-widest">Takeaway</span>
                        </button>
                    </div>
                </div>

                <div class="space-y-3 pt-4 border-t border-[#E6E6E6]">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground ml-1">Payment Method</h3>
                    <div class="grid grid-cols-1 gap-3">
                        <button 
                            @click="paymentMethod = 'Cash'"
                            :class="cn(
                                'h-20 flex flex-col items-center justify-center gap-2 rounded-2xl border-2 transition-all',
                                paymentMethod === 'Cash' ? 'border-primary text-primary bg-primary/5 shadow-lg shadow-primary/10' : 'border-[#E6E6E6] text-[#666666] hover:border-primary/50'
                            )"
                        >
                            <Wallet class="w-6 h-6" />
                            <span class="text-xs font-bold uppercase tracking-widest">Cash</span>
                        </button>
                    </div>
                </div>

                <div class="space-y-4 pt-4 border-t border-[#E6E6E6]">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-black uppercase tracking-[0.2em] text-[#666666]">Amount Paid</span>
                        <div class="relative w-48">
                             <Input 
                                v-model.number="amountPaid" 
                                type="number"
                                class="h-14 text-right font-black text-xl text-primary bg-[#F5F5F5] border-none rounded-2xl pr-4"
                             />
                        </div>
                    </div>
                    <div class="flex justify-between items-center py-4 px-6 bg-amber-50 rounded-2xl border border-amber-100">
                        <span class="text-xs font-black uppercase tracking-[0.2em] text-amber-700">Change</span>
                        <span class="text-xl font-black text-amber-600 tracking-tighter">{{ formatPrice(cartChange) }}</span>
                    </div>
                </div>

                <div class="flex flex-col gap-3 pt-4">
                    <Button 
                        @click="processPayment" 
                        class="w-full h-16 uppercase tracking-[0.2em] text-xs font-black shadow-xl shadow-primary/20"
                    >
                        Confirm & Print Receipt
                    </Button>
                    <Button variant="ghost" @click="showPaymentModal = false" class="uppercase tracking-widest text-[10px] font-bold">
                        Go Back
                    </Button>
                </div>
            </div>
        </Modal>

        <!-- Scanner Pairing Modal -->
        <Modal :show="showDeviceListModal" :title="ui.scanner_setup || 'Mobile Scanner Setup'" @close="showDeviceListModal = false">
             <div class="p-6 flex flex-col items-center justify-center space-y-6">
                <div class="text-center space-y-2">
                    <Smartphone class="w-12 h-12 text-primary mx-auto mb-4" />
                    <h3 class="text-lg font-black uppercase tracking-widest text-[#2D2D2D]">Pair Your Device</h3>
                    <p class="text-xs font-medium text-[#666666]">Open the scanner link on your phone and enter this pairing code:</p>
                </div>
                
                <div class="bg-[#F5F5F5] px-8 py-4 rounded-3xl border-2 border-[#E6E6E6]">
                    <span class="text-4xl font-black text-primary tracking-[0.3em]">{{ pairingCode }}</span>
                </div>
                
                <div class="w-full pt-6 border-t border-[#E6E6E6]">
                    <p class="text-[10px] font-bold text-center text-[#999999] uppercase tracking-widest mb-4">Or scan this QR code</p>
                    <div class="flex justify-center p-4 bg-white rounded-2xl border border-[#E6E6E6] shadow-sm w-max mx-auto">
                        <QrcodeVue :value="scannerUrl + '?code=' + pairingCode" :size="150" level="H" />
                    </div>
                </div>

                <div class="w-full flex gap-3 pt-4">
                    <Button @click="showDeviceListModal = false" class="w-full h-12 uppercase tracking-widest text-xs font-black">Close</Button>
                </div>
             </div>
        </Modal>

    </AppLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
