<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { 
    ChevronLeft, 
    Search, 
    UtensilsCrossed, 
    ShoppingBag, 
    X, 
    Clock, 
    Tag, 
    Minus, 
    Plus, 
    CheckCircle2, 
    Percent, 
    Wallet, 
    Gift, 
    Info, 
    Trash2,
    CreditCard,
    Mic,
    MicOff,
    MessageSquareText,
    ArrowRight,
    SlidersHorizontal,
    BookOpen,
    Pizza,
    IceCream,
    CupSoda
} from '@lucide/vue'
import { useCart } from '@/Composables/useCart'
import LanguageToggle from '@/Components/shared/LanguageToggle.vue'
import Modal from '@/Components/ui/Modal.vue'
import Button from '@/Components/ui/Button.vue'

defineOptions({ layout: null })

const page = usePage()

const props = defineProps({
    table: Object,
    categories: Array,
    packages: Array,
    promotions: Array,
    pendingOrders: Array,
})

const ui = computed(() => page.props.translations?.ui || {})

const { items, totalItems, totalPrice: rawTotalPrice, addToCart, updateQuantity, removeFromCart, clearCart } = useCart()

const searchQuery = ref('')
const selectedCategoryId = ref('all')
const showPromoModal = ref(false)
const selectedPromo = ref(null)

const showItemModal = ref(false)
const selectedItem = ref(null)
const itemQuantity = ref(1)
const selectedItemAddons = ref([])

const showCheckoutSheet = ref(false)
const showInitialPromoModal = ref(false)
const showCashierModal = ref(false)
const lastOrderTotal = ref(0)
const pollingInterval = ref(null)

const startPolling = (orderId) => {
    if (pollingInterval.value) clearInterval(pollingInterval.value)
    
    pollingInterval.value = setInterval(async () => {
        try {
            const response = await fetch(route('public.table.checkStatus', { 
                barcode: props.table.barcode, 
                order: orderId 
            }))
            const data = await response.json()
            
            if (data.status !== 'pending') {
                clearInterval(pollingInterval.value)
                showCashierModal.value = false
                // Optional: refresh page or state to show served/cooking status
                router.reload()
            }
        } catch (e) {
            console.error('Polling error:', e)
        }
    }, 5000) // Poll every 5 seconds
}

// Strictly enforce modal if pending orders exist
watch(() => props.pendingOrders, (newOrders) => {
    if (newOrders?.length > 0) {
        const latestOrder = newOrders[0]
        lastOrderTotal.value = latestOrder.total
        showCashierModal.value = true
        startPolling(latestOrder.id)
    } else {
        showCashierModal.value = false
        if (pollingInterval.value) clearInterval(pollingInterval.value)
    }
}, { immediate: true, deep: true })

onMounted(() => {
    // Show promo only if no payment lock
    if (!showCashierModal.value && props.promotions?.length > 0) {
        setTimeout(() => {
            showInitialPromoModal.value = true
        }, 800)
    }
})

const selectedPayment = ref(null)
const orderNote = ref('')
const isListening = ref(false)
const listeningField = ref(null)
const showMobileCart = ref(false)

const paymentMethods = computed(() => [
    { id: 'Cashier Payment', name: ui.value.pay_at_cashier || 'Cashier', icon: Wallet, description: 'Pay Counter' },
])

const toggleListening = (field = 'order') => {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition
    if (!SpeechRecognition) return

    const recognition = new SpeechRecognition()
    recognition.lang = page.props.locale === 'id' ? 'id-ID' : 'en-US'
    recognition.interimResults = false

    if (!isListening.value || listeningField.value !== field) {
        recognition.start()
        isListening.value = true
        listeningField.value = field

        recognition.onresult = (event) => {
            const transcript = event.results[0][0].transcript
            if (field === 'order') {
                orderNote.value = (orderNote.value ? orderNote.value + ' ' : '') + transcript
            } else if (typeof field === 'number') {
                items.value[field].note = (items.value[field].note ? items.value[field].note + ' ' : '') + transcript
            }
        }
        recognition.onend = () => { isListening.value = false; listeningField.value = null }
    } else {
        recognition.stop()
        isListening.value = false; listeningField.value = null
    }
}

const subtotal = computed(() => items.value.reduce((acc, item) => acc + item.totalPrice, 0))
const tax = computed(() => subtotal.value * 0.11)

const activeDiscount = computed(() => {
    if (!props.promotions || props.promotions.length === 0) return null

    const applicable = props.promotions.filter(promo => {
        if (promo.min_purchase > 0 && subtotal.value < promo.min_purchase) return false
        if (promo.type === 'BUY_X_GET_Y') {
            const cartItem = items.value.find(i => i.menu.id === promo.buy_menuid)
            return cartItem && cartItem.quantity >= promo.buy_qty
        }
        return true
    })

    if (applicable.length === 0) return null

    let bestPromo = null
    let maxAmount = -1

    applicable.forEach(promo => {
        let currentAmount = 0
        if (promo.type === 'DISCOUNT_PERCENT') currentAmount = subtotal.value * (promo.discount_value / 100)
        else if (promo.type === 'DISCOUNT_FIXED') currentAmount = promo.discount_value
        else if (promo.type === 'BUY_X_GET_Y') currentAmount = 0.1

        if (currentAmount > maxAmount) {
            maxAmount = currentAmount
            bestPromo = promo
        }
    })

    if (!bestPromo) return null

    const promo = bestPromo
    let finalAmount = 0

    if (promo.type === 'DISCOUNT_PERCENT') finalAmount = subtotal.value * (promo.discount_value / 100)
    else if (promo.type === 'DISCOUNT_FIXED') finalAmount = promo.discount_value
    else if (promo.type === 'BUY_X_GET_Y') {
        return {
            id: promo.promotionid,
            name: promo.name,
            type: promo.type,
            amount: 0,
            text: `${ui.value.qualified_for || 'Qualified for'}: ${ui.value.free || 'Free'} ${promo.get_menu?.name}`,
            get_menu: promo.get_menu,
            get_qty: promo.get_qty
        }
    }

    return {
        id: promo.promotionid,
        name: promo.name,
        type: promo.type,
        amount: finalAmount,
        text: `${ui.value.discount_applied || 'Discount applied'}: ${promo.name}`
    }
})

const finalTotalPrice = computed(() => (subtotal.value - (activeDiscount.value?.amount || 0)) + tax.value)

const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(price || 0)
}

const confirmOrder = () => {
    if (!selectedPayment.value) {
        alert(ui.value.select_payment_method || 'Please select a payment method')
        return
    }

    // Capture total before clearing cart
    lastOrderTotal.value = finalTotalPrice.value

    router.post(route('public.table.processCheckout', { barcode: props.table.barcode }), {
        items: items.value,
        paymentMethod: selectedPayment.value,
        totalPrice: finalTotalPrice.value,
        note: orderNote.value,
        appliedPromotionId: activeDiscount.value?.id,
        appliedPromotionName: activeDiscount.value?.name,
        appliedPromotionDiscount: activeDiscount.value?.amount || 0,
        freeMenuItemId: activeDiscount.value?.type === 'BUY_X_GET_Y' ? activeDiscount.value.get_menu?.menuid : null,
        freeMenuItemQty: activeDiscount.value?.type === 'BUY_X_GET_Y' ? activeDiscount.value.get_qty : 0
    }, {
        onSuccess: (page) => {
            if (!page.props.flash?.error && Object.keys(page.props.errors).length === 0) {
                clearCart()
                showCheckoutSheet.value = false
                showMobileCart.value = false
                showCashierModal.value = true
            }
        }
    })
}

const cancelOrder = () => {
    if (!props.pendingOrders?.length) return
    
    if (confirm(ui.value.confirm_cancel_order || 'Are you sure you want to cancel this order?')) {
        router.post(route('public.table.cancel', { 
            barcode: props.table.barcode,
            order: props.pendingOrders[0].id 
        }), {}, {
            onSuccess: () => {
                showCashierModal.value = false
                if (pollingInterval.value) clearInterval(pollingInterval.value)
            }
        })
    }
}

const scrollToCategory = (id) => {
    selectedCategoryId.value = id
    if (id === 'all') {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return
    }
    const element = document.getElementById(`category-${id}`)
    if (element) {
        const offset = 120;
        const bodyRect = document.body.getBoundingClientRect().top;
        const elementRect = element.getBoundingClientRect().top;
        const elementPosition = elementRect - bodyRect;
        const offsetPosition = elementPosition - offset;

        window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth'
        });
    }
}

const getCategoryIcon = (name) => {
    const n = name.toLowerCase()
    if (n.includes('burger')) return UtensilsCrossed
    if (n.includes('pizza')) return Pizza
    if (n.includes('ice cream') || n.includes('dessert')) return IceCream
    if (n.includes('juice') || n.includes('drink')) return CupSoda
    return UtensilsCrossed
}

const openPromo = (promo) => {
    selectedPromo.value = promo
    showPromoModal.value = true
}

const addPromoToCart = () => {
    if (selectedPromo.value) {
        const packageMenu = {
            id: `pkg-${selectedPromo.value.packageid}`,
            packageid: selectedPromo.value.packageid,
            name: selectedPromo.value.name,
            price: selectedPromo.value.price,
            image: null,
            is_package: true,
            menus: selectedPromo.value.menus,
            freebies: selectedPromo.value.freebies,
            all_promoids: selectedPromo.value.all_promoids
        }
        addToCart(packageMenu, 1, [], selectedPromo.value.price)
        showPromoModal.value = false
        selectedPromo.value = null
    }
}

const openItemModal = (menu) => {
    if (!menu.is_available) return
    selectedItem.value = menu
    itemQuantity.value = 1
    selectedItemAddons.value = []
    showItemModal.value = true
}

const toggleItemAddon = (addon) => {
    const index = selectedItemAddons.value.findIndex(a => a.addonid === addon.addonid)
    if (index > -1) selectedItemAddons.value.splice(index, 1)
    else selectedItemAddons.value.push(addon)
}

const isItemAddonSelected = (addonId) => selectedItemAddons.value.some(a => a.addonid === addonId)

const itemTotalPrice = computed(() => {
    if (!selectedItem.value) return 0
    const addonsTotal = selectedItemAddons.value.reduce((sum, addon) => sum + addon.price, 0)
    return (selectedItem.value.price + addonsTotal) * itemQuantity.value
})

const addItemToCartFromModal = () => {
    if (selectedItem.value) {
        addToCart(selectedItem.value, itemQuantity.value, selectedItemAddons.value, itemTotalPrice.value)
        showItemModal.value = false
        selectedItem.value = null
    }
}

const filteredPromotions = computed(() => {
    const list = Array.isArray(props.packages) ? props.packages : []
    if (!searchQuery.value) return list
    return list.filter(promo => promo.name.toLowerCase().includes(searchQuery.value.toLowerCase()))
})

const filteredCategories = computed(() => {
    let cats = props.categories.map(cat => ({
        ...cat,
        menus: cat.menus.filter(menu => 
            menu.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            menu.description?.toLowerCase().includes(searchQuery.value.toLowerCase())
        )
    })).filter(cat => cat.menus.length > 0)

    if (selectedCategoryId.value !== 'all') {
        cats = cats.filter(cat => cat.id === selectedCategoryId.value)
    }
    
    return cats
})
</script>

<template>
    <Head title="Menu" />

    <div class="flex h-screen bg-white overflow-hidden font-sans">
        
        <!-- MAIN MENU AREA (LEFT) -->
        <div class="flex-1 overflow-y-auto px-6 md:px-10 lg:px-12 pt-8 pb-32 lg:pb-12 bg-white no-scrollbar scroll-smooth">
            
            <!-- Mobile Menu Top Header -->
            <div class="flex lg:hidden items-center justify-between mb-6">
                <Link :href="route('public.table.welcome', { barcode: table.barcode })" class="p-2 bg-[#F5F6F8] rounded-xl text-[#043E54]">
                    <ChevronLeft class="w-5 h-5" />
                </Link>
                <div class="text-center">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest leading-none mb-1">{{ $page.props.system?.name }}</p>
                    <h1 class="text-sm font-black text-[#043E54] uppercase tracking-tight">Table {{ table.name }}</h1>
                </div>
                <LanguageToggle />
            </div>

            <!-- Search and Filter -->
            <div class="flex items-center gap-4 mb-8 sticky top-0 bg-white/80 backdrop-blur-md z-20 py-2">
                <div class="relative flex-1 group">
                    <Search class="absolute left-5 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5 transition-colors group-focus-within:text-[#8EC7D1]" />
                    <input 
                        v-model="searchQuery"
                        type="text" 
                        placeholder="Search items here..." 
                        class="w-full bg-[#F5F6F8] border-none rounded-[1rem] pl-14 pr-4 py-4 text-sm font-medium focus:ring-2 focus:ring-[#8EC7D1] outline-none text-[#043E54] placeholder:text-gray-400"
                    />
                </div>
                <button class="bg-[#043E54] p-4 rounded-[1rem] text-white hover:bg-[#065A7A] transition-colors shadow-lg shadow-[#043E54]/20">
                    <SlidersHorizontal class="w-5 h-5" />
                </button>
            </div>

            <!-- Category Navigation -->
            <div class="flex gap-6 overflow-x-auto no-scrollbar mb-12 pb-2 items-center">
                <button 
                    @click="scrollToCategory('all')"
                    class="flex flex-col items-center justify-center min-w-[75px] h-[85px] rounded-[1.2rem] border-2 transition-all gap-2 shrink-0"
                    :class="selectedCategoryId === 'all' ? 'border-[#8EC7D1] bg-white shadow-xl shadow-[#8EC7D1]/10 scale-105' : 'border-transparent bg-transparent opacity-60 hover:opacity-100'"
                >
                    <div class="w-10 h-10 rounded-xl bg-[#F5F6F8] flex items-center justify-center transition-colors" :class="selectedCategoryId === 'all' ? 'bg-[#8EC7D1]/10' : ''">
                        <BookOpen class="w-6 h-6" :class="selectedCategoryId === 'all' ? 'text-[#8EC7D1]' : 'text-gray-400'" />
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-widest" :class="selectedCategoryId === 'all' ? 'text-[#043E54]' : 'text-gray-500'">All</span>
                </button>

                <button 
                    v-if="packages?.length > 0"
                    @click="scrollToCategory('packages')"
                    class="flex flex-col items-center justify-center min-w-[75px] h-[85px] rounded-[1.2rem] border-2 transition-all gap-2 shrink-0"
                    :class="selectedCategoryId === 'packages' ? 'border-[#8EC7D1] bg-white shadow-xl shadow-[#8EC7D1]/10 scale-105' : 'border-transparent bg-transparent opacity-60 hover:opacity-100'"
                >
                    <div class="w-10 h-10 rounded-xl bg-[#F5F6F8] flex items-center justify-center transition-colors" :class="selectedCategoryId === 'packages' ? 'bg-[#8EC7D1]/10' : ''">
                        <Tag class="w-6 h-6" :class="selectedCategoryId === 'packages' ? 'text-[#8EC7D1]' : 'text-gray-400'" />
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-widest" :class="selectedCategoryId === 'packages' ? 'text-[#043E54]' : 'text-gray-500'">Packages</span>
                </button>

                <button 
                    v-for="cat in categories" 
                    :key="cat.id"
                    @click="scrollToCategory(cat.id)"
                    class="flex flex-col items-center justify-center min-w-[75px] h-[85px] rounded-[1.2rem] border-2 transition-all gap-2 shrink-0"
                    :class="selectedCategoryId === cat.id ? 'border-[#8EC7D1] bg-white shadow-xl shadow-[#8EC7D1]/10 scale-105' : 'border-transparent bg-transparent opacity-60 hover:opacity-100'"
                >
                    <div class="w-10 h-10 rounded-xl bg-[#F5F6F8] flex items-center justify-center transition-colors" :class="selectedCategoryId === cat.id ? 'bg-[#8EC7D1]/10' : ''">
                        <component :is="getCategoryIcon(cat.name)" class="w-6 h-6" :class="selectedCategoryId === cat.id ? 'text-[#8EC7D1]' : 'text-gray-400'" />
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-widest" :class="selectedCategoryId === cat.id ? 'text-[#043E54]' : 'text-gray-500'">{{ cat.name }}</span>
                </button>
            </div>

            <!-- Title -->
            <h2 class="text-2xl font-black text-[#043E54] mb-20 tracking-tight uppercase">Choose Items</h2>

            <!-- Grid Items -->
            <div v-if="filteredPromotions.length > 0 || filteredCategories.length > 0" class="space-y-24">
                <!-- Packages Grid -->
                <section v-if="filteredPromotions.length > 0 && (selectedCategoryId === 'all' || selectedCategoryId === 'packages')" id="category-packages">
                    <div class="flex items-center justify-between px-1 mb-16">
                        <h2 class="text-2xl font-black tracking-tight text-[#043E54] uppercase">Special Packages</h2>
                        <div class="h-1 w-8 bg-[#8EC7D1] rounded-full"></div>
                    </div>
                    
                    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-6 gap-y-24">
                        <div 
                            v-for="pkg in filteredPromotions" 
                            :key="pkg.packageid"
                            @click="openPromo(pkg)"
                            class="relative bg-white rounded-[2rem] border-2 border-[#F5F6F8] hover:border-[#8EC7D1] px-5 pb-6 pt-20 flex flex-col transition-all cursor-pointer group hover:shadow-[0_20px_50px_rgba(142,199,209,0.25)] hover:-translate-y-1"
                        >
                            <div class="absolute -top-14 left-1/2 -translate-x-1/2 w-32 h-32 z-10 transition-transform duration-500 group-hover:scale-110 group-hover:-rotate-3">
                                <div class="w-full h-full flex items-center justify-center bg-[#043E54] rounded-full border-4 border-white shadow-2xl text-white">
                                    <Tag class="w-12 h-12" stroke-width="2.5" />
                                </div>
                                <div class="absolute -top-1 -right-1 bg-yellow-400 text-[#043E54] text-[9px] font-black px-3 py-1.5 rounded-full shadow-lg border-2 border-white transform rotate-12">
                                    BUNDLE
                                </div>
                            </div>

                            <div class="flex-1 flex flex-col justify-between pt-2">
                                <div class="text-center space-y-2">
                                    <h3 class="text-[13px] font-black text-[#043E54] uppercase tracking-tight leading-tight line-clamp-2 h-8 flex items-center justify-center">{{ pkg.name }}</h3>
                                    <p class="text-[10px] font-medium text-gray-400 leading-relaxed line-clamp-3">
                                        {{ pkg.menus.map(m => `${m.qty}x ${m.name}`).join(', ') }}
                                    </p>
                                </div>
                                
                                <div class="flex items-center justify-between mt-8">
                                    <div class="flex flex-col">
                                        <span class="text-lg font-black text-[#043E54] tracking-tighter">{{ formatPrice(pkg.price) }}</span>
                                        <span class="text-[9px] font-bold text-[#2BB159] leading-none uppercase">Save Big!</span>
                                    </div>
                                    <button class="w-10 h-10 rounded-xl bg-[#043E54] text-white flex items-center justify-center group-hover:bg-[#065A7A] transition-all shadow-lg shadow-[#043E54]/20 active:scale-90">
                                        <Plus class="w-6 h-6" stroke-width="3" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <template v-if="filteredCategories.length > 0">
                    <section v-for="cat in filteredCategories" :key="cat.id" :id="`category-${cat.id}`">
                        <div class="flex items-center justify-between px-1 mb-16">
                            <h2 class="text-2xl font-black tracking-tight text-[#043E54] uppercase">{{ cat.name }}</h2>
                            <div class="h-1 w-8 bg-[#8EC7D1] rounded-full"></div>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-6 gap-y-24">
                            <div 
                                v-for="menu in cat.menus" 
                                :key="menu.id"
                                @click="openItemModal(menu)"
                                :class="[
                                    'relative bg-white rounded-[2rem] border-2 border-[#F5F6F8] hover:border-[#8EC7D1] px-5 pb-6 pt-20 flex flex-col transition-all cursor-pointer group',
                                    menu.is_available ? 'hover:shadow-[0_20px_50px_rgba(142,199,209,0.25)] hover:-translate-y-1' : 'opacity-50 grayscale cursor-not-allowed border-none bg-gray-50'
                                ]"
                            >
                                <div class="absolute -top-14 left-1/2 -translate-x-1/2 w-32 h-32 z-10 transition-transform duration-500 group-hover:scale-110 group-hover:-rotate-3">
                                    <img v-if="menu.image" :src="menu.image" class="w-full h-full object-contain drop-shadow-[0_20px_30px_rgba(0,0,0,0.15)]" />
                                    <div v-else class="w-full h-full flex items-center justify-center bg-white rounded-full border border-gray-100 shadow-xl text-gray-200">
                                        <UtensilsCrossed class="w-12 h-12" />
                                    </div>
                                    
                                    <div v-if="!menu.is_available" class="absolute inset-0 bg-black/40 rounded-full flex items-center justify-center backdrop-blur-[2px]">
                                        <span class="bg-destructive text-white text-[8px] font-black uppercase tracking-widest px-3 py-1 rounded-full shadow-2xl">{{ ui.sold_out || 'Sold Out' }}</span>
                                    </div>
                                    <div v-if="menu.discounted_price < menu.price" class="absolute -top-2 -right-2 bg-yellow-400 text-[#043E54] text-[9px] font-black px-3 py-1.5 rounded-full shadow-lg border-2 border-white transform rotate-12">
                                        PROMO
                                    </div>
                                </div>

                                <div class="flex-1 flex flex-col justify-between pt-2">
                                    <div class="text-center space-y-2">
                                        <h3 class="text-[13px] font-black text-[#043E54] uppercase tracking-tight leading-tight line-clamp-2 h-8 flex items-center justify-center">{{ menu.name }}</h3>
                                        <p class="text-[10px] font-medium text-gray-400 leading-relaxed line-clamp-3">
                                            {{ menu.description || 'Our signature recipe prepared with fresh ingredients and authentic flavors.' }}
                                        </p>
                                    </div>
                                    
                                    <div class="flex items-center justify-between mt-8">
                                        <div class="flex flex-col">
                                            <span class="text-lg font-black text-[#043E54] tracking-tighter">{{ formatPrice(menu.discounted_price || menu.price) }}</span>
                                            <span v-if="menu.discounted_price < menu.price" class="text-[9px] font-bold text-gray-300 line-through leading-none">{{ formatPrice(menu.price) }}</span>
                                        </div>
                                        <button class="w-10 h-10 rounded-xl bg-[#043E54] text-white flex items-center justify-center group-hover:bg-[#065A7A] transition-all shadow-lg shadow-[#043E54]/20 active:scale-90">
                                            <Plus class="w-6 h-6" stroke-width="3" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </template>
            </div>
            <div v-else class="py-32 text-center space-y-4">
                <div class="w-24 h-24 bg-[#F5F6F8] rounded-[2.5rem] flex items-center justify-center mx-auto text-gray-200">
                    <Search class="w-10 h-10" />
                </div>
                <p class="font-black text-[#043E54] uppercase tracking-widest text-sm">No items found</p>
            </div>
        </div>

        <!-- RIGHT SIDEBAR: BILLS -->
        <aside 
            class="fixed inset-y-0 right-0 z-50 w-full sm:w-[420px] lg:w-[380px] xl:w-[420px] bg-white border-l-4 border-[#F5F6F8] shadow-2xl lg:shadow-none lg:static lg:flex flex-col transform transition-transform duration-500 ease-in-out"
            :class="showMobileCart ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'"
        >
            <!-- Header -->
            <div class="px-10 py-10 flex items-center justify-between border-b border-[#F5F6F8]">
                <div>
                    <h2 class="text-3xl font-black text-[#043E54] uppercase tracking-tighter">Bills</h2>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">{{ totalItems }} items selected</p>
                </div>
                <button @click="showMobileCart = false" class="lg:hidden w-12 h-12 bg-[#F5F6F8] rounded-2xl text-gray-400 hover:text-[#043E54] transition-colors flex items-center justify-center">
                    <X class="w-6 h-6" />
                </button>
            </div>

            <!-- Item List -->
            <div class="flex-1 overflow-y-auto px-8 pt-6 space-y-6 no-scrollbar">
                <div v-for="(item, index) in items" :key="index" class="group flex flex-col gap-3 p-4 bg-white rounded-3xl border-2 border-[#F5F6F8] hover:border-[#8EC7D1]/30 transition-all">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-[#F5F6F8] overflow-hidden shrink-0 shadow-inner flex items-center justify-center p-1.5">
                            <img v-if="item.menu.image" :src="item.menu.image" class="w-full h-full object-contain" />
                            <UtensilsCrossed v-else class="w-8 h-8 text-gray-200" />
                        </div>
                        
                        <div class="flex-1 min-w-0">
                            <h4 class="text-[13px] font-black text-[#043E54] uppercase tracking-tight truncate leading-none mb-1">{{ item.menu.name }}</h4>
                            <p class="text-sm font-black text-[#043E54] opacity-60 tracking-tight">{{ formatPrice(item.totalPrice) }}</p>
                        </div>

                        <div class="flex flex-col items-end gap-2 shrink-0">
                            <div class="flex items-center gap-2 bg-[#F5F6F8] rounded-full p-1 border border-[#F3F4F6]">
                                <button @click="updateQuantity(index, item.quantity - 1)" class="w-5 h-5 rounded-full bg-[#8EC7D1] text-white flex items-center justify-center hover:scale-110 transition-all shadow-md"><Minus class="w-3 h-3" stroke-width="4"/></button>
                                <span class="text-[11px] font-black text-[#043E54] w-4 text-center">{{ String(item.quantity).padStart(2, '0') }}</span>
                                <button @click="updateQuantity(index, item.quantity + 1)" class="w-5 h-5 rounded-full bg-[#8EC7D1] text-white flex items-center justify-center hover:scale-110 transition-all shadow-md"><Plus class="w-3 h-3" stroke-width="4"/></button>
                            </div>
                            <button @click="removeFromCart(index)" class="text-[8px] text-[#8EC7D1] font-black tracking-widest hover:text-red-500 transition-colors uppercase">Remove</button>
                        </div>
                    </div>

                    <!-- Addons Display -->
                    <div v-if="item.addons?.length" class="flex flex-wrap gap-1.5 px-1">
                        <span v-for="addon in item.addons" :key="addon.addonid" class="text-[8px] font-black bg-[#F5F6F8] text-[#8EC7D1] px-2 py-0.5 rounded-md border border-[#8EC7D1]/20 uppercase tracking-widest">+ {{ addon.name }}</span>
                    </div>

                    <!-- Per-item Note with Voice -->
                    <div class="relative group/note px-1">
                        <div class="flex items-center gap-2">
                            <div class="relative flex-1">
                                <input 
                                    v-model="item.note"
                                    type="text" 
                                    placeholder="Add note..." 
                                    class="w-full bg-[#F8F9FB] border-none rounded-xl pl-3 pr-8 py-2 text-[10px] font-bold text-[#043E54] placeholder:text-gray-300 focus:ring-1 focus:ring-[#8EC7D1]/30 outline-none"
                                />
                                <button 
                                    @click="toggleListening(index)"
                                    :class="['absolute right-2 top-1/2 -translate-y-1/2 transition-colors', isListening && listeningField === index ? 'text-[#2BB159] animate-pulse' : 'text-gray-300 hover:text-[#8EC7D1]']"
                                >
                                    <Mic class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="items.length === 0" class="flex flex-col items-center justify-center py-20 text-gray-200 gap-4 opacity-50">
                    <div class="w-20 h-20 bg-[#F5F6F8] rounded-[2rem] flex items-center justify-center">
                        <ShoppingBag class="w-10 h-10" />
                    </div>
                    <p class="text-xs font-black uppercase tracking-widest text-[#043E54]">Your cart is empty</p>
                </div>
            </div>

            <!-- Summary & Checkout Footer (Shortened) -->
            <div class="p-8 bg-white border-t-2 border-[#F5F6F8]">
                <div class="space-y-3 mb-5 px-1">
                    <div class="flex justify-between items-center text-[10px] font-black text-[#043E54]/40 uppercase tracking-widest leading-none">
                        <span>Sub Total</span>
                        <span class="text-[#043E54]">{{ formatPrice(subtotal) }}</span>
                    </div>
                    <div class="flex justify-between items-center text-[10px] font-black text-[#043E54]/40 uppercase tracking-widest leading-none">
                        <span>Tax 11% (VAT Included)</span>
                        <span class="text-[#043E54]">{{ formatPrice(tax) }}</span>
                    </div>
                    <div v-if="activeDiscount" class="flex justify-between items-center text-[10px] font-black text-green-500 uppercase tracking-widest leading-none">
                        <span>Discount Applied</span>
                        <span>-{{ formatPrice(activeDiscount.amount) }}</span>
                    </div>
                </div>

                <div class="border-t border-dashed border-[#F5F6F8] mb-6 w-full opacity-60"></div>

                <div class="flex justify-between items-end mb-6 px-1">
                    <span class="text-xs font-black text-[#043E54] uppercase tracking-widest mb-1">Total</span>
                    <span class="text-3xl font-black text-[#2BB159] tracking-tighter leading-none">{{ formatPrice(finalTotalPrice) }}</span>
                </div>

                <button 
                    @click="showCheckoutSheet = true"
                    :disabled="items.length === 0"
                    class="w-full bg-[#043E54] text-white py-5 rounded-2xl font-black text-xs uppercase tracking-[0.3em] disabled:opacity-30 disabled:grayscale transition-all hover:scale-[1.02] active:scale-95 shadow-[0_15px_40px_rgba(4,62,84,0.3)] relative overflow-hidden group"
                >
                    <div class="absolute inset-0 bg-white/10 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                    Place Order
                </button>
            </div>
        </aside>

        <!-- Mobile Floating Cart Button -->
        <button 
            v-if="totalItems > 0 && !showMobileCart" 
            @click="showMobileCart = true" 
            class="lg:hidden fixed bottom-10 right-8 w-20 h-20 bg-[#043E54] rounded-[2.2rem] shadow-[0_25px_60px_rgba(4,62,84,0.4)] flex items-center justify-center text-white z-40 active:scale-90 transition-transform border-4 border-white"
        >
            <ShoppingBag class="w-8 h-8" />
            <span class="absolute -top-2 -right-2 w-8 h-8 bg-[#2BB159] border-4 border-white rounded-full text-[11px] flex items-center justify-center font-black shadow-lg">{{ totalItems }}</span>
        </button>

        <!-- Payment & Notes Overlay -->
        <transition 
            enter-active-class="transition-opacity duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0"
        >
            <div v-if="showCheckoutSheet" @click="showCheckoutSheet = false" class="fixed inset-0 bg-black/50 backdrop-blur-md z-[70]"></div>
        </transition>

        <transition
            enter-active-class="transition-transform duration-700 cubic-bezier(0.16, 1, 0.3, 1)" enter-from-class="translate-y-full" enter-to-class="translate-y-0"
            leave-active-class="transition-transform duration-500 cubic-bezier(0.7, 0, 0.84, 0)" leave-from-class="translate-y-0" leave-to-class="translate-y-full"
        >
            <div v-if="showCheckoutSheet" class="fixed bottom-0 left-0 right-0 sm:left-1/2 sm:-translate-x-1/2 sm:w-[500px] sm:bottom-auto sm:top-1/2 sm:-translate-y-1/2 sm:rounded-[3.5rem] bg-white rounded-t-[3.5rem] shadow-2xl z-[80] flex flex-col max-h-[92vh] overflow-hidden">
                <div class="px-10 pt-12 pb-6 border-b border-[#F5F6F8] flex items-center justify-between">
                    <h3 class="text-2xl font-black text-[#043E54] uppercase tracking-tighter">Finalize Order</h3>
                    <button @click="showCheckoutSheet = false" class="w-12 h-12 bg-[#F5F6F8] rounded-2xl text-gray-400 hover:text-[#043E54] transition-colors flex items-center justify-center"><X class="w-6 h-6" /></button>
                </div>
                
                <div class="p-10 space-y-10 overflow-y-auto no-scrollbar">
                    <!-- Notes -->
                    <div class="space-y-4">
                        <div class="flex justify-between items-center px-1">
                            <label class="text-[10px] font-black text-[#043E54]/40 uppercase tracking-[0.3em]">Order Notes</label>
                            <button @click="toggleListening()" :class="['w-10 h-10 rounded-2xl flex items-center justify-center transition-all shadow-sm', isListening ? 'bg-[#043E54] text-white animate-pulse' : 'bg-[#F5F6F8] text-[#043E54] hover:bg-[#8EC7D1] hover:text-white']">
                                <Mic class="w-5 h-5" />
                            </button>
                        </div>
                        <textarea v-model="orderNote" class="w-full bg-[#F5F6F8] border-none rounded-[1.8rem] p-6 text-sm font-bold focus:ring-4 focus:ring-[#8EC7D1]/10 outline-none text-[#043E54] min-h-[140px] placeholder:text-gray-300 resize-none leading-relaxed" placeholder="Add any special requests here... (e.g. less spicy, no onions)"></textarea>
                    </div>

                    <!-- Payment -->
                    <div class="space-y-4">
                        <label class="text-[10px] font-black text-[#043E54]/40 uppercase tracking-[0.3em] px-1">Payment Method</label>
                        <div class="grid grid-cols-2 gap-5">
                            <button 
                                v-for="method in paymentMethods" :key="method.id"
                                @click="selectedPayment = method.id"
                                :class="[
                                    'p-8 rounded-[2.5rem] border-4 flex flex-col items-center gap-4 transition-all duration-500 relative overflow-hidden',
                                    selectedPayment === method.id 
                                        ? 'border-[#043E54] bg-[#043E54] text-white shadow-2xl shadow-[#043E54]/30 scale-[1.05]' 
                                        : 'border-transparent bg-[#F5F6F8] text-[#043E54] hover:border-[#8EC7D1]'
                                ]"
                            >
                                <div :class="['w-14 h-14 rounded-2xl flex items-center justify-center transition-colors', selectedPayment === method.id ? 'bg-white/20' : 'bg-white shadow-sm']">
                                    <component :is="method.icon" class="w-7 h-7" stroke-width="2.5" />
                                </div>
                                <span class="text-xs font-black uppercase tracking-widest text-center">{{ method.name }}</span>
                                <div v-if="selectedPayment === method.id" class="absolute top-4 right-4 w-6 h-6 rounded-full bg-white flex items-center justify-center shadow-lg animate-in zoom-in-50">
                                    <CheckCircle2 class="w-4 h-4 text-[#043E54]" stroke-width="4" />
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="p-10 border-t-4 border-[#F5F6F8] bg-gray-50/50">
                    <button 
                        @click="confirmOrder"
                        :disabled="!selectedPayment"
                        class="w-full bg-[#2BB159] text-white py-6 rounded-[2rem] font-black text-xs uppercase tracking-[0.3em] disabled:opacity-30 disabled:grayscale transition-all hover:scale-[1.02] active:scale-95 shadow-[0_20px_50px_rgba(43,177,89,0.3)] flex items-center justify-center gap-3"
                    >
                        Confirm & Pay {{ formatPrice(finalTotalPrice) }}
                        <CheckCircle2 class="w-6 h-6" stroke-width="3" />
                    </button>
                </div>
            </div>
        </transition>

        <!-- Item Detail Modal -->
        <Modal :show="showItemModal" title="Item Details" @close="showItemModal = false">
            <div class="p-10 space-y-10">
                <div class="relative w-56 h-56 mx-auto">
                    <div class="absolute inset-0 bg-[#F5F6F8] rounded-full p-6 animate-pulse"></div>
                    <img v-if="selectedItem?.image" :src="selectedItem?.image" class="relative z-10 w-full h-full object-contain drop-shadow-[0_25px_40px_rgba(0,0,0,0.2)]" />
                    <div v-else class="relative z-10 w-full h-full flex items-center justify-center text-gray-200"><UtensilsCrossed class="w-20 h-20" /></div>
                </div>
                
                <div class="text-center space-y-3">
                    <h2 class="text-3xl font-black text-[#043E54] uppercase tracking-tighter leading-tight">{{ selectedItem?.name }}</h2>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest leading-relaxed px-4">{{ selectedItem?.description || 'A delicious treat crafted with the finest ingredients and authentic culinary passion.' }}</p>
                    <div class="text-4xl font-black text-[#043E54] tracking-tighter mt-4">{{ formatPrice(selectedItem?.discounted_price || selectedItem?.price) }}</div>
                </div>

                <div class="flex items-center justify-center gap-10 bg-[#F5F6F8] w-max mx-auto p-3 rounded-[2.5rem] border-2 border-white shadow-inner">
                    <button @click="itemQuantity > 1 ? itemQuantity-- : null" class="w-14 h-14 rounded-2xl bg-white text-[#043E54] flex items-center justify-center shadow-lg hover:bg-[#8EC7D1] hover:text-white transition-all active:scale-90"><Minus class="w-6 h-6" stroke-width="4" /></button>
                    <span class="text-4xl font-black text-[#043E54] w-12 text-center tracking-tighter">{{ String(itemQuantity).padStart(2, '0') }}</span>
                    <button @click="itemQuantity < 50 ? itemQuantity++ : null" class="w-14 h-14 rounded-2xl bg-[#043E54] text-white flex items-center justify-center shadow-lg hover:bg-[#065A7A] transition-all active:scale-90"><Plus class="w-6 h-6" stroke-width="4" /></button>
                </div>

                <button 
                    @click="addItemToCartFromModal" 
                    class="w-full h-20 rounded-[2rem] text-xs font-black uppercase tracking-[0.3em] bg-[#043E54] text-white hover:bg-[#065A7A] transition-all shadow-[0_20px_50px_rgba(4,62,84,0.3)] active:scale-95 group relative overflow-hidden"
                >
                    <div class="absolute inset-0 bg-white/10 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                    Add {{ itemQuantity }} to Cart &bull; {{ formatPrice(itemTotalPrice) }}
                </button>
            </div>
        </Modal>

        <!-- Promo Detail Modal -->
        <Modal :show="showPromoModal" title="Promo Details" @close="showPromoModal = false">
            <div class="p-10 space-y-8">
                <div class="text-center space-y-3">
                    <div class="w-20 h-20 bg-[#8EC7D1]/10 text-[#8EC7D1] rounded-[2.2rem] flex items-center justify-center mx-auto mb-4 shadow-xl shadow-[#8EC7D1]/5">
                        <Tag class="w-10 h-10" />
                    </div>
                    <h2 class="text-3xl font-black text-[#043E54] uppercase tracking-tighter leading-tight">{{ selectedPromo?.name }}</h2>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.4em]">Special Offer</p>
                </div>

                <div class="space-y-4">
                    <div v-for="menu in selectedPromo?.menus" :key="menu.id" class="bg-[#F5F6F8] p-5 rounded-[2rem] flex items-center gap-5 border-2 border-white shadow-sm">
                        <div class="w-14 h-14 rounded-2xl bg-white flex items-center justify-center font-black text-lg text-[#043E54] shadow-inner">{{ menu.qty }}x</div>
                        <div class="flex-1">
                            <p class="text-[13px] font-black text-[#043E54] uppercase tracking-tight">{{ menu.name }}</p>
                            <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Selected Item</p>
                        </div>
                    </div>
                </div>

                <div class="pt-8 border-t-4 border-[#F5F6F8] flex flex-col gap-6">
                    <div class="flex items-center justify-between px-2">
                        <span class="text-xs font-black uppercase tracking-[0.2em] text-gray-400">Total Price</span>
                        <span class="text-4xl font-black text-[#043E54] tracking-tighter leading-none">{{ formatPrice(selectedPromo?.price) }}</span>
                    </div>
                    <button 
                        @click="addPromoToCart" 
                        class="w-full h-20 rounded-[2rem] text-xs font-black uppercase tracking-[0.3em] bg-[#043E54] text-white hover:bg-[#065A7A] transition-all shadow-[0_20px_50px_rgba(4,62,84,0.3)] active:scale-95 group relative overflow-hidden"
                    >
                        <div class="absolute inset-0 bg-white/10 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                        Claim Package
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Initial Promo Popup Modal -->
        <Modal :show="showInitialPromoModal" title="Special Offers For You" @close="showInitialPromoModal = false">
            <div class="p-10 space-y-12">
                <div class="text-center space-y-4">
                    <div class="w-24 h-24 bg-[#043E54] text-white rounded-[2.5rem] flex items-center justify-center mx-auto mb-4 shadow-2xl shadow-[#043E54]/20 animate-bounce">
                        <Gift class="w-12 h-12" />
                    </div>
                    <h2 class="text-4xl font-black text-[#043E54] uppercase tracking-tighter leading-tight">Exclusive Promos!</h2>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-[0.4em]">Don't miss out on today's specials</p>
                </div>

                <div class="space-y-6 max-h-[40vh] overflow-y-auto no-scrollbar px-2">
                    <!-- Standard Promotions (Discounts/Buy X Get Y) -->
                    <div v-for="promo in promotions" :key="promo.promotionid" class="bg-gradient-to-br from-[#043E54] to-[#065A7A] p-8 rounded-[2.5rem] text-white relative overflow-hidden shadow-xl border-4 border-white">
                        <div class="absolute -right-8 -bottom-8 opacity-10 transform rotate-12">
                            <Percent class="w-32 h-32" />
                        </div>
                        <div class="relative z-10 space-y-4">
                            <span class="px-4 py-1.5 rounded-full bg-white/20 backdrop-blur-md text-[10px] font-black uppercase tracking-widest border border-white/20">{{ promo.type.replace(/_/g, ' ') }}</span>
                            <h3 class="text-2xl font-black uppercase tracking-tight">{{ promo.name }}</h3>
                            <p class="text-sm font-bold text-yellow-400">
                                <template v-if="promo.type === 'DISCOUNT_PERCENT'">Save {{ promo.discount_value }}% on your order!</template>
                                <template v-else-if="promo.type === 'DISCOUNT_FIXED'">Get {{ formatPrice(promo.discount_value) }} instant discount!</template>
                                <template v-else-if="promo.type === 'BUY_X_GET_Y'">Buy {{ promo.buy_qty }} Get {{ promo.get_qty }} FREE!</template>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="pt-6">
                    <button 
                        @click="showInitialPromoModal = false" 
                        class="w-full h-20 rounded-[2.2rem] text-xs font-black uppercase tracking-[0.3em] bg-[#043E54] text-white hover:bg-[#065A7A] transition-all shadow-[0_20px_50px_rgba(4,62,84,0.3)] active:scale-95"
                    >
                        Start Ordering
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Cashier Payment Instruction Modal (Persistent) -->
        <Modal :show="showCashierModal" title="Payment Required" :persistent="true">
            <div class="p-10 space-y-12 text-center">
                <div class="w-24 h-24 bg-[#2BB159] text-white rounded-[2.5rem] flex items-center justify-center mx-auto mb-4 shadow-2xl shadow-[#2BB159]/20 animate-pulse">
                    <Wallet class="w-12 h-12" />
                </div>
                <div class="space-y-4">
                    <h2 class="text-4xl font-black text-[#043E54] uppercase tracking-tighter leading-tight">Order Placed!</h2>
                    <p class="text-sm font-bold text-gray-500 uppercase tracking-widest leading-loose">
                        Please proceed to the cashier to complete your payment. <br/>
                        Your order will be processed once payment is confirmed.
                    </p>
                </div>
                
                <div class="bg-[#F5F6F8] p-8 rounded-[2.5rem] border-2 border-white shadow-inner space-y-2">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em]">Amount to Pay</p>
                    <p class="text-4xl font-black text-[#043E54] tracking-tighter">{{ formatPrice(lastOrderTotal) }}</p>
                </div>

                <div class="flex items-center justify-center gap-3 text-[#8EC7D1] animate-bounce">
                    <Clock class="w-5 h-5" />
                    <span class="text-[10px] font-black uppercase tracking-widest">Waiting for confirmation...</span>
                </div>

                <div class="pt-6 border-t border-gray-100">
                    <button 
                        @click="cancelOrder" 
                        class="w-full py-4 text-xs font-black uppercase tracking-widest text-destructive/60 hover:text-destructive transition-colors flex items-center justify-center gap-2"
                    >
                        <X class="w-4 h-4" />
                        Cancel Order
                    </button>
                </div>
            </div>
        </Modal>

    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>