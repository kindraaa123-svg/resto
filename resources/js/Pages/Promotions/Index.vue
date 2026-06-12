<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Input from '@/Components/ui/Input.vue'
import Modal from '@/Components/ui/Modal.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import ConfirmationModal from '@/Components/ui/ConfirmationModal.vue'
import { 
    Plus, Search, Tags, Edit2, Trash2, Tag, 
    Gift, Percent, Wallet, Calendar, Clock, 
    Building, CheckCircle2, XCircle, Info, ArrowRight, Utensils,
    CheckSquare, Square, ChevronDown, X, Image
} from '@lucide/vue'
import { ref, computed } from 'vue'

defineOptions({ layout: AppLayout })

const page = usePage()
const props = defineProps({
    promotions: Object,
    branches: Array,
    menus: Array,
    errors: Object,
    auth: Object,
    translations: Object,
})

const ui = computed(() => page.props.translations?.ui || {})

const searchQuery = ref('')
const showModal = ref(false)
const showConfirmModal = ref(false)
const editingPromotion = ref(null)
const promotionToDelete = ref(null)

const showMenuDropdown = ref(false)
const showBranchDropdown = ref(false)

const form = useForm({
    name: '',
    type: 'DISCOUNT_PERCENT',
    discount_value: '',
    buy_qty: 1,
    get_qty: 1,
    buy_menuid: '',
    get_menuid: '',
    min_purchase: 0,
    branchids: [],
    menuids: [],
    menu_addons: {},
    status: 'Available',
    datefrom: '',
    dateto: '',
    timefrom: '',
    timeto: '',
    days: [],
    image: null,
    _method: 'post'
})

const openAddModal = () => {
    editingPromotion.value = null
    form.reset()
    form._method = 'post'
    showModal.value = true
}

const openEditModal = (promotion) => {
    editingPromotion.value = promotion
    form.reset()
    form.name = promotion.name
    form.type = promotion.type
    form.discount_value = promotion.discount_value
    form.buy_qty = promotion.buy_qty
    form.get_qty = promotion.get_qty
    form.buy_menuid = promotion.buy_menuid
    form.get_menuid = promotion.get_menuid
    form.min_purchase = promotion.min_purchase
    form.branchids = promotion.branchids || []
    form.menuids = promotion.menuids || []
    form.menu_addons = promotion.menu_addons || {}
    form.status = promotion.status
    form.datefrom = promotion.datefrom
    form.dateto = promotion.dateto
    form.timefrom = promotion.timefrom
    form.timeto = promotion.timeto
    form.days = promotion.days || []
    form.image = null
    form._method = 'put'
    showModal.value = true
}

const confirmDelete = (promotion) => {
    promotionToDelete.value = promotion
    showConfirmModal.value = true
}

const submit = () => {
    if (editingPromotion.value) {
        form.post(route('promotions.update', editingPromotion.value.promotionid), {
            forceFormData: true,
            onSuccess: () => {
                showModal.value = false
                form.reset()
            }
        })
    } else {
        form.post(route('promotions.store'), {
            forceFormData: true,
            onSuccess: () => {
                showModal.value = false
                form.reset()
            }
        })
    }
}

const deletePromotion = () => {
    form.delete(route('promotions.destroy', promotionToDelete.value.promotionid), {
        onSuccess: () => {
            showConfirmModal.value = false
            promotionToDelete.value = null
        }
    })
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat(page.props.locale === 'id' ? 'id-ID' : 'en-US', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount || 0)
}

const daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']

const getAvailableAddons = (menuId) => {
    const menu = props.menus.find(m => m.menuid === menuId)
    return menu?.addons || []
}

const toggleMenuAddon = (menuId, addonId) => {
    if (!form.menu_addons) form.menu_addons = {}
    if (!form.menu_addons[menuId]) form.menu_addons[menuId] = []
    
    const index = form.menu_addons[menuId].indexOf(addonId)
    if (index > -1) {
        form.menu_addons[menuId].splice(index, 1)
    } else {
        form.menu_addons[menuId].push(addonId)
    }
}

const activeAddonDropdown = ref(null)

const toggleDay = (day) => {
    const index = form.days.indexOf(day)
    if (index > -1) {
        form.days.splice(index, 1)
    } else {
        form.days.push(day)
    }
}

const toggleBranchSelection = (branchId) => {
    const index = form.branchids.indexOf(branchId)
    if (index > -1) {
        form.branchids.splice(index, 1)
    } else {
        form.branchids.push(branchId)
    }
}

const toggleMenuSelection = (menuId) => {
    const index = form.menuids.indexOf(menuId)
    if (index > -1) {
        form.menuids.splice(index, 1)
    } else {
        form.menuids.push(menuId)
    }
}

const isAllMenusSelected = computed(() => props.menus.length > 0 && form.menuids.length === props.menus.length)
const toggleAllMenus = () => {
    if (isAllMenusSelected.value) {
        form.menuids = []
    } else {
        form.menuids = props.menus.map(m => m.menuid)
    }
}

const isAllBranchesSelected = computed(() => props.branches.length > 0 && form.branchids.length === props.branches.length)
const toggleAllBranches = () => {
    if (isAllBranchesSelected.value) {
        form.branchids = []
    } else {
        form.branchids = props.branches.map(b => b.branchid)
    }
}

const selectedMenus = computed(() => {
    return props.menus.filter(m => form.menuids.includes(m.menuid))
})
</script>

<template>
    <Head :title="ui.promotions || 'Promotions'" />

    <div class="max-w-7xl mx-auto space-y-6 lg:space-y-8 pb-20">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-2 lg:px-0">
            <div>
                <h1 class="text-2xl lg:text-3xl font-black tracking-tight text-primary uppercase">{{ ui.promotions || 'Promotions' }}</h1>
                <p class="text-muted-foreground text-[10px] lg:text-sm font-medium uppercase tracking-widest mt-1">{{ ui.manage_promo_rules || 'Manage Discounts & Special Offers' }}</p>
            </div>
            
            <Button @click="openAddModal" class="gap-2 h-12 w-full sm:w-auto px-6 rounded-2xl shadow-lg shadow-primary/20">
                <Plus class="w-5 h-5" />
                {{ ui.add_promotion || 'Add Promotion' }}
            </Button>
        </div>

        <!-- Table Card -->
        <Card class="border-none shadow-sm bg-card/50 overflow-hidden mx-2 lg:mx-0">
            <div class="p-4 lg:p-6 border-b border-secondary/50 flex flex-col sm:flex-row items-center gap-4">
                <div class="relative w-full sm:max-w-xs">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                    <Input 
                        v-model="searchQuery" 
                        :placeholder="ui.search_promotions || 'Search promotions...'" 
                        class="pl-10 bg-background h-11 border-transparent focus:border-primary/20 text-sm"
                    />
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-secondary/20 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">
                            <th class="px-6 py-4">{{ ui.promo_name || 'Promotion Name' }}</th>
                            <th class="px-6 py-4">{{ ui.type || 'Type' }}</th>
                            <th class="px-6 py-4 text-center">{{ ui.status || 'Status' }}</th>
                            <th class="px-6 py-4 text-right">{{ ui.actions || 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-secondary/30">
                        <tr v-for="promo in promotions.data" :key="promo.promotionid" class="group hover:bg-white/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="font-black text-primary text-sm uppercase tracking-wide">{{ promo.name }}</span>
                                <div class="flex flex-wrap items-center gap-3 mt-1">
                                        <span class="text-[9px] font-bold text-muted-foreground uppercase flex items-center gap-1">
                                            <Building class="w-3 h-3" />
                                            {{ promo.branchids?.length ? `${promo.branchids.length} ${ui.branches || 'Branches'}` : ui.all_branches || 'All Branches' }}
                                        </span>
                                        <span v-if="promo.type === 'DISCOUNT_PERCENT' || promo.type === 'DISCOUNT_FIXED'" class="text-[9px] font-bold text-muted-foreground uppercase flex items-center gap-1">
                                            <Utensils class="w-3 h-3" />
                                            {{ promo.menuids?.length ? `${promo.menuids.length} ${ui.menus || 'Menus'}` : ui.all_menus || 'All Menus' }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-primary flex items-center gap-1.5">
                                        <Percent v-if="promo.type === 'DISCOUNT_PERCENT'" class="w-3.5 h-3.5" />
                                        <Wallet v-else-if="promo.type === 'DISCOUNT_FIXED'" class="w-3.5 h-3.5" />
                                        <Gift v-else class="w-3.5 h-3.5" />
                                        {{ promo.type === 'DISCOUNT_PERCENT' ? (ui.discount_percentage || 'Percentage Discount') : (promo.type === 'DISCOUNT_FIXED' ? (ui.discount_amount || 'Fixed Discount') : (ui.buy_x_get_y || 'Buy X Get Y')) }}
                                    </span>
                                    <span class="text-xs font-bold text-muted-foreground mt-1">
                                        <template v-if="promo.type === 'DISCOUNT_PERCENT'">{{ promo.discount_value }}% {{ ui.available || 'Off' }}</template>
                                        <template v-else-if="promo.type === 'DISCOUNT_FIXED'">{{ formatCurrency(promo.discount_value) }} {{ ui.available || 'Off' }}</template>
                                        <template v-else-if="promo.type === 'BUY_X_GET_Y'">
                                            {{ ui.buy || 'Buy' }} {{ promo.buy_qty }} {{ promo.buy_menu?.name }} {{ ui.get || 'Get' }} {{ promo.get_qty }} {{ promo.get_menu?.name }}
                                        </template>
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span :class="[
                                    'px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest',
                                    promo.status === 'Available' ? 'bg-green-100 text-green-700' : 
                                    promo.status === 'Certain Period' ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700'
                                ]">
                                    {{ promo.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <button @click="openEditModal(promo)" class="p-2 hover:bg-primary/10 text-primary rounded-lg transition-colors">
                                        <Edit2 class="w-4 h-4" />
                                    </button>
                                    <button @click="confirmDelete(promo)" class="p-2 hover:bg-destructive/10 text-destructive rounded-lg transition-colors">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-4 lg:p-6 border-t border-secondary/50">
                <Pagination :links="promotions.links" />
            </div>
        </Card>

        <!-- Promotion Modal -->
        <Modal :show="showModal" :title="editingPromotion ? ui.edit_promotion || 'Edit Promotion' : ui.add_promotion || 'Add Promotion'" @close="showModal = false">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Basic Info -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground ml-1">{{ ui.promo_name || 'Promo Name' }}</label>
                        <Input v-model="form.name" placeholder="e.g. Happy Hour Special" class="h-11 font-bold" required />
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground ml-1">{{ ui.promo_type || 'Type' }}</label>
                        <select v-model="form.type" class="w-full h-11 px-4 rounded-xl border border-secondary bg-background font-bold text-sm focus:ring-1 focus:ring-primary outline-none">
                            <option value="DISCOUNT_PERCENT">{{ ui.discount_percentage || 'Percentage Discount (%)' }}</option>
                            <option value="DISCOUNT_FIXED">{{ ui.discount_amount || 'Fixed Amount Discount (Rp)' }}</option>
                            <option value="BUY_X_GET_Y">{{ ui.buy_x_get_y || 'Buy X Get Y Free' }}</option>
                        </select>
                    </div>
                </div>

                <!-- Conditional Settings -->
                <div v-if="form.type === 'DISCOUNT_PERCENT' || form.type === 'DISCOUNT_FIXED'" class="p-4 bg-primary/5 rounded-2xl border border-primary/10 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-primary uppercase tracking-widest">{{ form.type === 'DISCOUNT_PERCENT' ? (ui.discount_percentage || 'Discount Percentage') : (ui.discount_amount || 'Discount Amount') }}</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 w-10 flex items-center justify-center text-primary font-black">
                                    {{ form.type === 'DISCOUNT_PERCENT' ? '%' : 'Rp' }}
                                </div>
                                <Input v-model="form.discount_value" type="number" step="any" class="pl-10 h-11 font-black" required />
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-primary uppercase tracking-widest">{{ ui.min_purchase || 'Min. Purchase' }}</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 w-10 flex items-center justify-center text-primary font-black">Rp</div>
                                <Input v-model="form.min_purchase" type="number" class="pl-10 h-11 font-black" />
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-2 pt-2 border-t border-primary/10 relative">
                        <label class="text-[10px] font-black text-primary uppercase tracking-widest">{{ ui.select_menus || 'Apply To Specific Menus (Optional)' }}</label>
                        
                        <div class="relative">
                            <button 
                                type="button" 
                                @click="showMenuDropdown = !showMenuDropdown"
                                class="w-full h-11 px-4 rounded-xl border border-primary/20 bg-white font-bold text-sm focus:ring-1 focus:ring-primary outline-none flex items-center justify-between transition-colors hover:border-primary/40"
                            >
                                <span :class="form.menuids.length ? 'text-primary' : 'text-muted-foreground/70'">
                                    {{ form.menuids.length ? `${form.menuids.length} ${ui.menus || 'Menus'} Selected` : `-- ${ui.select_menus || 'Select Menus'} --` }}
                                </span>
                                <ChevronDown :class="['w-4 h-4 text-primary transition-transform', showMenuDropdown ? 'rotate-180' : '']" />
                            </button>

                            <div v-if="showMenuDropdown" class="absolute top-full left-0 right-0 mt-2 bg-white rounded-2xl shadow-2xl border border-secondary p-2 z-[100] animate-in fade-in slide-in-from-top-2 duration-200">
                                <div class="flex items-center justify-between px-2 py-1 mb-1 border-b border-secondary/50">
                                    <span class="text-[9px] font-black uppercase text-muted-foreground tracking-widest">{{ ui.select_menus || 'Select Menus' }}</span>
                                    <button type="button" @click="showMenuDropdown = false" class="text-muted-foreground hover:text-primary"><X class="w-3 h-3" /></button>
                                </div>
                                <div v-if="menus.length > 0" class="px-2 pb-2 mb-1 border-b border-secondary/30">
                                    <button 
                                        type="button" 
                                        @click="toggleAllMenus"
                                        class="flex items-center gap-2 w-full p-2 rounded-xl text-xs font-bold uppercase tracking-tight transition-colors hover:bg-secondary/20"
                                        :class="isAllMenusSelected ? 'text-primary' : 'text-muted-foreground'"
                                    >
                                        <component :is="isAllMenusSelected ? CheckSquare : Square" class="w-4 h-4 shrink-0" />
                                        {{ ui.select_all || 'Select All Menus' }}
                                    </button>
                                </div>
                                <div class="max-h-48 overflow-y-auto no-scrollbar space-y-1">
                                    <div 
                                        v-for="menu in menus" 
                                        :key="menu.menuid"
                                        @click="toggleMenuSelection(menu.menuid)"
                                        class="flex items-center gap-3 p-2.5 rounded-xl cursor-pointer transition-colors"
                                        :class="form.menuids.includes(menu.menuid) ? 'bg-primary/10 text-primary' : 'hover:bg-secondary/20 text-foreground'"
                                    >
                                        <component :is="form.menuids.includes(menu.menuid) ? CheckSquare : Square" class="w-4 h-4 shrink-0" />
                                        <span class="text-xs font-bold uppercase tracking-tight truncate">{{ menu.name }}</span>
                                    </div>
                                    <div v-if="menus.length === 0" class="p-4 text-center text-xs text-muted-foreground italic">
                                        {{ ui.no_menu_found || 'No menus available' }}
                                    </div>
                                </div>
                            </div>
                            <!-- Backdrop to close -->
                            <div v-if="showMenuDropdown" class="fixed inset-0 z-40" @click="showMenuDropdown = false"></div>
                        </div>
                        
                        <p class="text-[9px] font-bold text-muted-foreground ml-1">If no menus are selected, the discount applies to all items in the cart.</p>

                        <!-- Selected Menus with Addon Settings -->
                        <div v-if="selectedMenus.length > 0" class="mt-4 border-2 border-primary/10 rounded-[1.5rem] p-3 space-y-2 bg-white/50 shadow-inner max-h-[360px] overflow-y-auto no-scrollbar relative z-10">
                            <div 
                                v-for="menu in selectedMenus" 
                                :key="menu.menuid"
                                class="flex flex-col gap-2"
                            >
                                <div class="flex items-center justify-between p-3 rounded-2xl bg-white border border-primary/20 shadow-sm relative">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-xl flex items-center justify-center bg-primary/10 text-primary">
                                            <Utensils class="w-4 h-4" />
                                        </div>
                                        <span class="text-xs font-black uppercase tracking-tight">{{ menu.name }}</span>
                                    </div>
                                    
                                    <div v-if="getAvailableAddons(menu.menuid).length > 0" class="flex items-center">
                                        <button 
                                            type="button"
                                            @click="activeAddonDropdown = activeAddonDropdown === menu.menuid ? null : menu.menuid"
                                            class="h-8 bg-secondary/30 border border-secondary text-foreground rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 px-3 hover:bg-secondary/50 transition-colors"
                                        >
                                            <span class="truncate">{{ form.menu_addons?.[menu.menuid]?.length ? `${form.menu_addons[menu.menuid].length} ${ui.addons || 'Addons'}` : ui.no_addons_found || 'No Addon' }}</span>
                                            <ChevronDown :class="['w-3 h-3 shrink-0 transition-transform', activeAddonDropdown === menu.menuid ? 'rotate-180' : '']" />
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Inline Addon List (Accordion) -->
                                <div v-if="activeAddonDropdown === menu.menuid && getAvailableAddons(menu.menuid).length > 0" class="pl-12 pr-2 pb-2 animate-in slide-in-from-top-2 fade-in duration-200">
                                    <div class="bg-card rounded-2xl border border-secondary p-3 space-y-1 shadow-sm">
                                        <div class="flex items-center justify-between px-2 py-1 mb-2 border-b border-secondary/50">
                                            <span class="text-[9px] font-black uppercase text-primary tracking-widest">{{ ui.included_addons || 'Included Addons' }}</span>
                                            <button type="button" @click="activeAddonDropdown = null" class="text-muted-foreground hover:text-primary"><X class="w-3 h-3" /></button>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            <div 
                                                v-for="addon in getAvailableAddons(menu.menuid)" 
                                                :key="addon.addonid"
                                                @click="toggleMenuAddon(menu.menuid, addon.addonid)"
                                                class="flex items-center justify-between p-2.5 rounded-xl cursor-pointer transition-colors"
                                                :class="form.menu_addons?.[menu.menuid]?.includes(addon.addonid) ? 'bg-primary border border-primary text-primary-foreground shadow-sm' : 'bg-background border border-secondary hover:border-primary/30 text-foreground'"
                                            >
                                                <div class="flex items-center gap-2">
                                                    <component :is="form.menu_addons?.[menu.menuid]?.includes(addon.addonid) ? CheckSquare : Square" class="w-3.5 h-3.5" />
                                                    <span class="text-[10px] font-bold uppercase tracking-tight truncate">{{ addon.name }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="form.type === 'BUY_X_GET_Y'" class="p-4 sm:p-5 bg-blue-50 rounded-2xl border border-blue-100 space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-blue-700 uppercase tracking-widest">{{ ui.buy_condition || 'Buy Condition' }}</label>
                            <div class="flex items-center gap-2">
                                <Input v-model.number="form.buy_qty" type="number" min="1" class="w-16 sm:w-20 shrink-0 h-11 font-black text-center border-blue-200 focus:border-blue-500 focus:ring-blue-500" />
                                <div class="relative flex-1 min-w-0">
                                    <select v-model="form.buy_menuid" class="w-full h-11 pl-3 pr-8 sm:pl-4 rounded-xl border border-blue-200 bg-white font-bold text-xs sm:text-sm focus:ring-1 focus:ring-blue-500 outline-none truncate appearance-none" required>
                                        <option value="">-- {{ ui.select_menus || 'Choose Menu' }} --</option>
                                        <option v-for="menu in menus" :key="menu.menuid" :value="menu.menuid">{{ menu.name }}</option>
                                    </select>
                                    <ChevronDown class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-blue-500 pointer-events-none" />
                                </div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <label class="text-[10px] font-black text-blue-700 uppercase tracking-widest">{{ ui.get_bonus || 'Get Bonus' }}</label>
                            <div class="flex items-center gap-2">
                                <Input v-model.number="form.get_qty" type="number" min="1" class="w-16 sm:w-20 shrink-0 h-11 font-black text-center border-blue-200 focus:border-blue-500 focus:ring-blue-500" />
                                <div class="relative flex-1 min-w-0">
                                    <select v-model="form.get_menuid" class="w-full h-11 pl-3 pr-8 sm:pl-4 rounded-xl border border-blue-200 bg-white font-bold text-xs sm:text-sm focus:ring-1 focus:ring-blue-500 outline-none truncate appearance-none" required>
                                        <option value="">-- {{ ui.select_menus || 'Choose Menu' }} --</option>
                                        <option v-for="menu in menus" :key="menu.menuid" :value="menu.menuid">{{ menu.name }}</option>
                                    </select>
                                    <ChevronDown class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-blue-500 pointer-events-none" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Scheduling & Availability -->
                <div class="space-y-6 pt-4 border-t border-secondary/50">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="space-y-2 relative">
                            <label class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground ml-1">{{ ui.branch_availability || 'Branch Availability (Optional)' }}</label>
                            
                            <div class="relative">
                                <button 
                                    type="button" 
                                    @click="showBranchDropdown = !showBranchDropdown"
                                    class="w-full h-11 px-4 rounded-xl border border-secondary bg-background font-bold text-sm focus:ring-1 focus:ring-primary outline-none flex items-center justify-between transition-colors hover:border-primary/40"
                                >
                                    <span :class="form.branchids.length ? 'text-primary' : 'text-muted-foreground'">
                                        {{ form.branchids.length ? `${form.branchids.length} ${ui.branches || 'Branches'} Selected` : `-- ${ui.select_branches || 'Select Branches'} --` }}
                                    </span>
                                    <ChevronDown :class="['w-4 h-4 text-muted-foreground transition-transform', showBranchDropdown ? 'rotate-180' : '']" />
                                </button>

                                <div v-if="showBranchDropdown" class="absolute top-full left-0 right-0 mt-2 bg-white rounded-2xl shadow-2xl border border-secondary p-2 z-[100] animate-in fade-in slide-in-from-top-2 duration-200">
                                    <div class="flex items-center justify-between px-2 py-1 mb-1 border-b border-secondary/50">
                                        <span class="text-[9px] font-black uppercase text-muted-foreground tracking-widest">{{ ui.select_branches || 'Select Branches' }}</span>
                                        <button type="button" @click="showBranchDropdown = false" class="text-muted-foreground hover:text-primary"><X class="w-3 h-3" /></button>
                                    </div>
                                    <div v-if="branches.length > 0" class="px-2 pb-2 mb-1 border-b border-secondary/30">
                                        <button 
                                            type="button" 
                                            @click="toggleAllBranches"
                                            class="flex items-center gap-2 w-full p-2 rounded-xl text-xs font-bold uppercase tracking-tight transition-colors hover:bg-secondary/20"
                                            :class="isAllBranchesSelected ? 'text-primary' : 'text-muted-foreground'"
                                        >
                                            <component :is="isAllBranchesSelected ? CheckSquare : Square" class="w-4 h-4 shrink-0" />
                                            {{ ui.select_all || 'Select All Branches' }}
                                        </button>
                                    </div>
                                    <div class="max-h-48 overflow-y-auto no-scrollbar space-y-1">
                                        <div 
                                            v-for="branch in branches" 
                                            :key="branch.branchid"
                                            @click="toggleBranchSelection(branch.branchid)"
                                            class="flex items-center gap-3 p-2.5 rounded-xl cursor-pointer transition-colors"
                                            :class="form.branchids.includes(branch.branchid) ? 'bg-primary/10 text-primary' : 'hover:bg-secondary/20 text-foreground'"
                                        >
                                            <component :is="form.branchids.includes(branch.branchid) ? CheckSquare : Square" class="w-4 h-4 shrink-0" />
                                            <span class="text-xs font-bold uppercase tracking-tight truncate">{{ branch.branchname }}</span>
                                        </div>
                                    </div>
                                </div>
                                <!-- Backdrop to close -->
                                <div v-if="showBranchDropdown" class="fixed inset-0 z-40" @click="showBranchDropdown = false"></div>
                            </div>
                            
                            <p class="text-[9px] font-bold text-muted-foreground ml-1">If no branches are selected, the promotion applies to all branches.</p>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground ml-1">{{ ui.status || 'Promotion Status' }}</label>
                            <div class="flex items-center gap-2 h-11">
                                <button v-for="s in ['Available', 'Certain Period', 'Inactive']" :key="s" type="button" @click="form.status = s"
                                    :class="['flex-1 h-full rounded-xl border text-[10px] font-black uppercase tracking-widest transition-all', form.status === s ? 'bg-primary border-primary text-primary-foreground shadow-md' : 'bg-background border-secondary text-muted-foreground opacity-50 hover:opacity-100']"
                                >
                                    {{ s === 'Available' ? (ui.available || 'Available') : (s === 'Certain Period' ? (ui.certain_period || 'Certain Period') : (ui.inactive || 'Inactive')) }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="form.status === 'Certain Period'" class="space-y-6 animate-in slide-in-from-top-2 duration-300">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-muted-foreground uppercase flex items-center gap-1.5"><Calendar class="w-3 h-3" /> {{ ui.date_range || 'Date Range' }}</label>
                                <div class="flex items-center gap-2">
                                    <Input v-model="form.datefrom" type="date" class="h-11 font-bold text-xs" />
                                    <ArrowRight class="w-4 h-4 text-muted-foreground" />
                                    <Input v-model="form.dateto" type="date" class="h-11 font-bold text-xs" />
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-muted-foreground uppercase flex items-center gap-1.5"><Clock class="w-3 h-3" /> {{ ui.time_range || 'Time Range' }}</label>
                                <div class="flex items-center gap-2">
                                    <Input v-model="form.timefrom" type="time" class="h-11 font-bold text-xs" />
                                    <ArrowRight class="w-4 h-4 text-muted-foreground" />
                                    <Input v-model="form.timeto" type="time" class="h-11 font-bold text-xs" />
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-muted-foreground uppercase flex items-center gap-1.5">{{ ui.active_days || 'Active Days' }}</label>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="day in daysOfWeek" :key="day" type="button" @click="toggleDay(day)"
                                    :class="['px-3 py-2 rounded-xl border text-[10px] font-black uppercase tracking-widest transition-all', form.days.includes(day) ? 'bg-primary border-primary text-primary-foreground shadow-sm' : 'bg-background border-secondary text-muted-foreground hover:border-primary/30']"
                                >
                                    {{ day.substring(0, 3) }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-end gap-3 pt-6 pb-4 border-t border-secondary/50">
                    <Button type="button" variant="ghost" @click="showModal = false" class="rounded-xl font-bold order-2 sm:order-1">{{ ui.cancel || 'Cancel' }}</Button>
                    <Button type="submit" class="gap-2 px-8 h-12 rounded-2xl shadow-lg shadow-primary/20 order-1 sm:order-2" :disabled="form.processing">
                        <CheckCircle2 class="w-4 h-4" />
                        {{ editingPromotion ? (ui.update_promotion || 'Update Promotion') : (ui.create_promotion || 'Create Promotion') }}
                    </Button>
                </div>
            </form>
        </Modal>

        <ConfirmationModal 
            :show="showConfirmModal"
            :title="ui.delete_promotion || 'Delete Promotion'"
            :message="(ui.confirm_delete_promotion || 'Are you sure you want to delete \':name\'? This action cannot be undone.').replace(':name', promotionToDelete?.name)"
            type="danger"
            :confirmText="ui.delete || 'Delete'"
            @close="showConfirmModal = false"
            @confirm="deletePromotion"
        />
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
