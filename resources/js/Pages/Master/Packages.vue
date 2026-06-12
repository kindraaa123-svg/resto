<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Input from '@/Components/ui/Input.vue'
import Modal from '@/Components/ui/Modal.vue'
import ConfirmationModal from '@/Components/ui/ConfirmationModal.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import { Plus, Pencil, Trash2, Search, Tag, X, Save, Package, CheckSquare, Square, Building, DollarSign, Filter, Layers, List, ChevronDown } from '@lucide/vue'
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash-es'

defineOptions({ layout: AppLayout })

const page = usePage()
const props = defineProps({
    promos: Object,
    packages: Array,
    menus: Array,
    addons: Array,
    freebies: Array,
    branches: Array,
    filters: Object,
    errors: Object,
    auth: Object,
    translations: Object,
})

const ui = computed(() => page.props.translations?.ui || {})

const searchQuery = ref(props.filters?.filter?.search || '')
const menuSearchQuery = ref('')
const showModal = ref(false)
const showConfirmModal = ref(false)
const promoToDelete = ref(null)
const impactData = ref([])
const editingPromo = ref(null)

watch(searchQuery, debounce((value) => {
    router.get(route('packages.index'), { filter: { search: value } }, {
        preserveState: true,
        replace: true
    })
}, 500))

const filteredPromos = computed(() => {
    return props.promos?.data || []
})

const filteredMenus = computed(() => {
    if (!menuSearchQuery.value) return props.menus
    return props.menus.filter(menu => 
        menu.name.toLowerCase().includes(menuSearchQuery.value.toLowerCase())
    )
})

const form = useForm({
    packagename: '',
    menuids: [], // For creation
    menu_qtys: {}, // For creation qty mapping
    menu_addons: {}, // For creation addon mapping
    freebie_ids: [], // For creation
    freebie_qtys: {}, // For creation qty mapping
    menuid: '',   // For update
    qty: 1,       // For update
    addonid: '',  // For update
    freeid: '', // Global for package (fallback/update single)
    branchids: [], // For creation
    branchid: '',  // For update
    all_ids: [], // Original group IDs for sync
    price: '',
    status: 'Available',
    datefrom: '',
    dateto: '',
    timefrom: '',
    timeto: '',
    days: [],
})

const showBranchDropdown = ref(false)
const showFreebieDropdown = ref(false)

const daysOfWeek = [
    'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'
]

const openAddModal = () => {
    editingPromo.value = null
    form.reset()
    form.menuids = []
    form.branchids = []
    form.freebie_ids = []
    form.freebie_qtys = {}
    form.days = []
    menuSearchQuery.value = ''
    showBranchDropdown.value = false
    showFreebieDropdown.value = false
    showModal.value = true
}

const openEditModal = (promo) => {
    editingPromo.value = promo
    form.reset()
    form.packagename = promo.packagename
    
    // Load existing menus
    form.menuids = promo.menus.map(m => m.menuid)
    promo.menus.forEach(m => {
        form.menu_qtys[m.menuid] = m.qty
        // Map all addon IDs for this menu
        form.menu_addons[m.menuid] = m.addons ? m.addons.map(a => a.addonid) : []
    })
    
    // Load existing freebies
    form.freebie_ids = promo.freebies.map(f => f.freeid)
    promo.freebies.forEach(f => {
        form.freebie_qtys[f.freeid] = f.qty
    })
    
    // Branch context
    form.branchids = promo.branchid ? [promo.branchid] : []
    form.all_ids = promo.all_promoids || []
    
    form.price = promo.price
    form.status = promo.status || 'Available'
    form.datefrom = promo.datefrom ? promo.datefrom.split(' ')[0] : ''
    form.dateto = promo.dateto ? promo.dateto.split(' ')[0] : ''
    form.timefrom = promo.timefrom ? promo.timefrom.substring(0, 5) : ''
    form.timeto = promo.timeto ? promo.timeto.substring(0, 5) : ''
    form.days = promo.days || []
    
    menuSearchQuery.value = ''
    showBranchDropdown.value = false
    showFreebieDropdown.value = false
    showModal.value = true
}

const toggleMenuSelection = (menuId) => {
    const index = form.menuids.indexOf(menuId)
    if (index > -1) {
        form.menuids.splice(index, 1)
        delete form.menu_qtys[menuId]
        delete form.menu_addons[menuId]
    } else {
        form.menuids.push(menuId)
        form.menu_qtys[menuId] = 1 // Default quantity
        form.menu_addons[menuId] = [] // Support multiple addons
    }
}

const toggleFreebieSelection = (freebieId) => {
    const index = form.freebie_ids.indexOf(freebieId)
    if (index > -1) {
        form.freebie_ids.splice(index, 1)
        delete form.freebie_qtys[freebieId]
    } else {
        form.freebie_ids.push(freebieId)
        form.freebie_qtys[freebieId] = 1 // Default quantity
    }
}

const toggleDaySelection = (day) => {
    const index = form.days.indexOf(day)
    if (index > -1) {
        form.days.splice(index, 1)
    } else {
        form.days.push(day)
        // Clear dates if days are selected
        form.datefrom = ''
        form.dateto = ''
    }
}

// Watch date inputs to clear days if set
watch(() => form.datefrom, (val) => {
    if (val) form.days = []
})
watch(() => form.dateto, (val) => {
    if (val) form.days = []
})

const toggleBranchSelection = (branchId) => {
    const index = form.branchids.indexOf(branchId)
    if (index > -1) {
        form.branchids.splice(index, 1)
    } else {
        form.branchids.push(branchId)
    }
}

const selectAllFiltered = () => {
    filteredMenus.value.forEach(menu => {
        if (!form.menuids.includes(menu.menuid)) {
            form.menuids.push(menu.menuid)
        }
    })
}

const clearSelection = () => {
    form.menuids = []
}

const getAvailableAddons = (menuId) => {
    const menu = props.menus.find(m => m.menuid === menuId)
    if (!menu) return []
    
    return props.addons.filter(addon => 
        addon.menuid === menu.menuid || 
        addon.categoryid === menu.categoryid ||
        (!addon.menuid && !addon.categoryid)
    )
}

const toggleMenuAddon = (menuId, addonId) => {
    if (!form.menu_addons[menuId]) form.menu_addons[menuId] = []
    const index = form.menu_addons[menuId].indexOf(addonId)
    if (index > -1) {
        form.menu_addons[menuId].splice(index, 1)
    } else {
        form.menu_addons[menuId].push(addonId)
    }
}

const activeAddonDropdown = ref(null) // Tracks which menu's addon dropdown is open

const submit = () => {
    if (editingPromo.value) {
        // If grouped, it might be better to just edit one at a time for now
        // or transform to bulk update if required.
        form.put(route('packages.update', editingPromo.value.menus[0]?.promoid || editingPromo.value.all_promoids[0]), {
            onSuccess: () => {
                showModal.value = false
                form.reset()
            }
        })
    } else {
        form.post(route('packages.store'), {
            onSuccess: () => {
                showModal.value = false
                form.reset()
            }
        })
    }
}

const confirmDelete = (promo) => {
    promoToDelete.value = promo
    impactData.value = [] // For grouped delete, calculating impact is complex
    showConfirmModal.value = true
}

const deletePromo = () => {
    if (promoToDelete.value) {
        form.defaults('ids', promoToDelete.value.all_promoids)
        form.delete(route('packages.destroy', promoToDelete.value.all_promoids[0]), {
            onFinish: () => {
                showConfirmModal.value = false
                promoToDelete.value = null
            }
        })
    }
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat(page.props.locale === 'id' ? 'id-ID' : 'en-US', {
        style: 'currency',
        currency: page.props.locale === 'id' ? 'IDR' : 'USD',
        minimumFractionDigits: 0
    }).format(amount)
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
    <Head :title="ui.packages || 'Promotions'" />

    <div class="max-w-7xl mx-auto space-y-6 lg:space-y-8 pb-20">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-2 lg:px-0">
            <div>
                <h1 class="text-2xl lg:text-3xl font-black tracking-tight text-primary uppercase">{{ ui.packages || 'Packages' }}</h1>
                <p class="text-muted-foreground text-[10px] lg:text-sm font-medium uppercase tracking-widest mt-1">{{ ui.manage_packages || 'Manage Packages' }}</p>
            </div>
            
            <Button @click="openAddModal" class="gap-2 h-12 w-full sm:w-auto px-6 rounded-2xl shadow-lg shadow-primary/20">
                <Plus class="w-5 h-5" />
                {{ ui.add_package || 'Add Package' }}
            </Button>
        </div>

        <Card class="border-none shadow-sm bg-card/50 overflow-hidden mx-2 lg:mx-0">
            <!-- Table Controls -->
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

            <!-- Desktop Table -->
            <div v-if="!isMobileView" class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-secondary/20 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">
                            <th class="px-6 py-4 w-20">PKG ID</th>
                            <th class="px-6 py-4">{{ ui.package || 'Package' }}</th>
                            <th class="px-6 py-4">{{ ui.contents || 'Package Contents' }}</th>
                            <th class="px-6 py-4">{{ ui.branch || 'Branch' }}</th>
                            <th class="px-6 py-4">{{ ui.price || 'Price' }}</th>
                            <th class="px-6 py-4">{{ ui.status || 'Status' }}</th>
                            <th class="px-6 py-4">{{ ui.duration || 'Duration' }}</th>
                            <th class="px-6 py-4 text-right">{{ ui.actions || 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-secondary/30">
                        <tr v-for="promo in filteredPromos" :key="promo.packageid + '-' + promo.branchid + '-' + promo.price" class="group hover:bg-white/50 transition-colors">
                            <td class="px-6 py-4 text-sm font-bold text-muted-foreground">
                                #{{ promo.packageid }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 rounded-lg bg-primary/5 text-primary">
                                        <Package class="w-4 h-4" />
                                    </div>
                                    <span class="font-black text-primary uppercase tracking-tight text-sm">
                                        {{ promo.packagename }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-3 py-1">
                                    <!-- Menus -->
                                    <div v-for="menu in promo.menus" :key="menu.promoid" class="flex items-start gap-3">
                                        <div class="mt-0.5 p-1 rounded bg-secondary/20 text-muted-foreground">
                                            <Tag class="w-3 h-3" />
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="font-bold text-foreground text-[13px]">
                                                {{ menu.menuname }} <span class="text-muted-foreground text-[11px]">x{{ menu.qty }}</span>
                                            </span>
                                            <div v-if="menu.addons?.length" class="flex flex-wrap gap-x-2 gap-y-0.5 mt-0.5">
                                                <span v-for="addon in menu.addons" :key="addon.addonid" class="text-[9px] font-black uppercase text-primary/70 tracking-widest">+ {{ addon.name }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Freebies -->
                                    <div v-for="freebie in promo.freebies" :key="'f-' + freebie.promoid" class="flex items-start gap-3">
                                        <div class="mt-0.5 p-1 rounded bg-green-100 text-green-600">
                                            <Tag class="w-3 h-3" />
                                        </div>
                                        <span class="text-[11px] font-black uppercase text-green-600 tracking-widest mt-0.5">Free: {{ freebie.name }} x{{ freebie.qty }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2 text-muted-foreground">
                                    <Building class="w-3.5 h-3.5" />
                                    <span class="text-xs font-bold">{{ promo.branchname }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-black text-primary text-sm">{{ formatCurrency(promo.price) }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="[
                                    'px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest',
                                    promo.status === 'Available' ? 'bg-green-100 text-green-700' :
                                    promo.status === 'Unavailable' ? 'bg-red-100 text-red-700' :
                                    'bg-yellow-100 text-yellow-700'
                                ]">
                                    {{ ui[promo.status.toLowerCase().replace(' ', '_')] || promo.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs font-bold text-muted-foreground">
                                <div v-if="promo.status === 'Certain Period'" class="flex flex-col gap-1">
                                    <div v-if="promo.datefrom || promo.dateto" class="flex items-center gap-1 text-primary/80">
                                        <ChevronDown class="w-3 h-3 rotate-90" />
                                        <span>{{ promo.datefrom ? new Date(promo.datefrom).toLocaleDateString() : '—' }} - {{ promo.dateto ? new Date(promo.dateto).toLocaleDateString() : '—' }}</span>
                                    </div>
                                    <div v-if="promo.timefrom || promo.timeto" class="flex items-center gap-1 text-primary/80">
                                        <ChevronDown class="w-3 h-3 rotate-90" />
                                        <span>{{ promo.timefrom ? promo.timefrom.substring(0, 5) : '00:00' }} - {{ promo.timeto ? promo.timeto.substring(0, 5) : '23:59' }}</span>
                                    </div>
                                    <div v-if="promo.days && promo.days.length" class="flex flex-wrap gap-1">
                                        <span v-for="day in promo.days" :key="day" class="bg-secondary/30 px-1.5 py-0.5 rounded text-[9px] uppercase tracking-tighter">{{ day.substring(0, 3) }}</span>
                                    </div>
                                </div>
                                <span v-else>—</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <button 
                                        @click="openEditModal(promo)"
                                        class="p-2 hover:bg-primary/10 text-primary rounded-lg transition-colors"
                                    >
                                        <Pencil class="w-4 h-4" />
                                    </button>
                                    <button 
                                        @click="confirmDelete(promo)"
                                        class="p-2 hover:bg-destructive/10 text-destructive rounded-lg transition-colors"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="filteredPromos.length === 0">
                            <td colspan="8" class="px-6 py-12 text-center text-muted-foreground italic text-sm">
                                {{ ui.no_packages_found || 'No promotion records found.' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div v-else class="bg-secondary/5">
                <div class="divide-y divide-secondary/30 p-2 space-y-2">
                    <div v-for="promo in filteredPromos" :key="promo.packageid + '-' + promo.branchid" class="bg-white border border-secondary/50 rounded-3xl p-4 shadow-sm relative overflow-hidden flex flex-col gap-4">
                        <div class="flex flex-col gap-2">
                            <div class="flex items-center gap-2">
                                <Package class="w-4 h-4 text-primary" />
                                <span class="text-sm font-black text-primary uppercase">{{ promo.packagename }}</span>
                            </div>
                            <div class="flex flex-col gap-3 mt-1 ml-1 pl-5 border-l-2 border-secondary/30">
                                <div v-for="menu in promo.menus" :key="menu.promoid" class="flex flex-col">
                                    <span class="text-sm font-bold text-foreground">{{ menu.menuname }} <span class="text-muted-foreground text-xs">x{{ menu.qty }}</span></span>
                                    <div v-if="menu.addons?.length" class="flex flex-wrap gap-x-2 gap-y-0.5 mt-0.5">
                                        <span v-for="addon in menu.addons" :key="addon.addonid" class="text-[9px] font-black uppercase text-primary/70 tracking-widest">+ {{ addon.name }}</span>
                                    </div>
                                </div>
                                <div v-for="freebie in promo.freebies" :key="'f-' + freebie.promoid">
                                    <span class="text-[10px] font-black uppercase text-green-600 tracking-widest">Free: {{ freebie.name }} x{{ freebie.qty }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <Building class="w-3.5 h-3.5 text-muted-foreground" />
                                <span class="text-[10px] font-bold text-muted-foreground uppercase">{{ promo.branchname }}</span>
                            </div>
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-[9px] font-black text-muted-foreground uppercase tracking-widest block">PKG #{{ promo.packageid }}</span>
                                <span class="text-sm font-black text-primary">{{ formatCurrency(promo.price) }}</span>
                            </div>
                        </div>

                        <div class="flex gap-2 pt-2 border-t border-secondary/30">
                            <button @click="openEditModal(promo)" class="flex-1 p-3 bg-primary/10 text-primary rounded-xl transition-all active:scale-95 border border-primary/20 flex items-center justify-center gap-2">
                                <Pencil class="w-4 h-4" />
                                <span class="text-[10px] font-black uppercase tracking-widest">Edit</span>
                            </button>
                            <button @click="confirmDelete(promo)" class="p-3 bg-destructive/10 text-destructive rounded-xl transition-all active:scale-95 border border-destructive/20 flex items-center justify-center">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div v-if="filteredPromos.length === 0" class="py-12 text-center">
                        <div class="inline-flex p-4 rounded-full bg-secondary/30 text-muted-foreground mb-3">
                            <Tag class="w-8 h-8 opacity-20" />
                        </div>
                        <h3 class="text-sm font-black text-primary uppercase">{{ ui.no_packages_found || 'No promotions found.' }}</h3>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div class="p-4 lg:p-6 border-t border-secondary/50">
                <Pagination :links="promos.links" />
            </div>
        </Card>

        <!-- CRUD Modal -->
        <Modal :show="showModal" :title="editingPromo ? (ui.edit_package || 'Edit Package') : (ui.add_new_package || 'Add New Package')" @close="showModal = false" max-width="2xl">
            <form @submit.prevent="submit" class="space-y-8">
                <!-- Section 1: Target Info -->
                <div class="bg-secondary/5 rounded-3xl p-5 lg:p-6 border border-secondary/20 space-y-5">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="p-1.5 rounded-lg bg-primary/10 text-primary">
                            <Layers class="w-4 h-4" />
                        </div>
                        <h3 class="text-xs font-black uppercase tracking-widest text-primary">{{ ui.target_configuration || 'Target Configuration' }}</h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.package || 'Package' }}</label>
                            <div class="relative">
                                <Package class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground z-10" />
                                <Input 
                                    v-model="form.packagename" 
                                    type="text"
                                    class="pl-10 h-12 bg-background border-secondary rounded-2xl text-[13px] lg:text-sm font-black text-primary focus:ring-2 focus:ring-primary/20 w-full" 
                                    :placeholder="ui.enter_package_name || 'Enter package name'"
                                    required
                                />
                            </div>
                            <div v-if="form.errors.packagename" class="text-[10px] text-destructive font-black uppercase px-1 mt-1">{{ form.errors.packagename }}</div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.branch || 'Branch' }}</label>
                            
                            <div class="relative">
                                <Building class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground z-10 pointer-events-none" />
                                <div 
                                    @click="showBranchDropdown = !showBranchDropdown"
                                    class="w-full h-12 bg-background border border-secondary rounded-2xl pl-10 pr-4 text-[13px] lg:text-sm focus:ring-2 focus:ring-primary/20 flex items-center justify-between cursor-pointer"
                                >
                                    <span :class="form.branchids.length ? 'text-primary font-bold' : 'text-muted-foreground font-medium'">
                                        {{ form.branchids.length ? `${form.branchids.length} Branches Selected` : (ui.all_branches || 'All Branches') }}
                                    </span>
                                    <ChevronDown class="w-4 h-4 text-muted-foreground" />
                                </div>
                                
                                <!-- Backdrop -->
                                <div v-if="showBranchDropdown" class="fixed inset-0 z-20" @click="showBranchDropdown = false"></div>
                                
                                <!-- Dropdown List -->
                                <div v-if="showBranchDropdown" class="absolute z-30 top-full left-0 right-0 mt-2 bg-card border border-secondary rounded-2xl shadow-xl overflow-hidden">
                                    <div class="max-h-60 overflow-y-auto p-2 space-y-1 no-scrollbar">
                                        <div 
                                            @click="form.branchids = []"
                                            class="flex items-center gap-3 p-3 rounded-xl cursor-pointer transition-colors"
                                            :class="form.branchids.length === 0 ? 'bg-primary/10 text-primary' : 'hover:bg-secondary/20'"
                                        >
                                            <component :is="form.branchids.length === 0 ? CheckSquare : Square" class="w-4 h-4" />
                                            <span class="text-sm font-bold uppercase tracking-tight">{{ ui.all_branches || 'All Branches' }}</span>
                                        </div>
                                        
                                        <div 
                                            v-for="branch in branches" :key="branch.branchid"
                                            @click="toggleBranchSelection(branch.branchid)"
                                            class="flex items-center gap-3 p-3 rounded-xl cursor-pointer transition-colors"
                                            :class="form.branchids.includes(branch.branchid) ? 'bg-primary/10 text-primary' : 'hover:bg-secondary/20'"
                                        >
                                            <component :is="form.branchids.includes(branch.branchid) ? CheckSquare : Square" class="w-4 h-4" />
                                            <span class="text-sm font-bold uppercase tracking-tight">{{ branch.branchname }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="form.errors.branchids" class="text-[10px] text-destructive font-black uppercase px-1 mt-1">{{ form.errors.branchids }}</div>
                        </div>
                    </div>

                    <div class="space-y-2 pt-2">
                        <label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.price || 'Promo Price' }}</label>
                        <div class="relative">
                            <div class="absolute left-0 top-0 bottom-0 w-12 flex items-center justify-center bg-primary/5 border-r border-secondary rounded-l-2xl text-primary font-black text-sm">
                                <DollarSign class="w-4 h-4" />
                            </div>
                            <Input 
                                v-model="form.price" 
                                type="number"
                                step="any"
                                class="pl-14 h-12 bg-background border-secondary rounded-2xl text-[13px] lg:text-sm font-black text-primary focus:ring-2 focus:ring-primary/20" 
                                :placeholder="ui.enter_price || '0'"
                                required
                            />
                        </div>
                        <div v-if="form.errors.price" class="text-[10px] text-destructive font-black uppercase px-1 mt-1">{{ form.errors.price }}</div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-2">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.status || 'Status' }}</label>
                            <div class="relative">
                                <select 
                                    v-model="form.status" 
                                    class="w-full h-12 bg-background border-secondary rounded-2xl px-4 text-[13px] lg:text-sm font-bold focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all appearance-none"
                                >
                                    <option value="Available">{{ ui.available || 'Available' }}</option>
                                    <option value="Unavailable">{{ ui.unavailable || 'Unavailable' }}</option>
                                    <option value="Certain Period">{{ ui.certain_period || 'Certain Period' }}</option>
                                </select>
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                                    <ChevronDown class="w-4 h-4 text-muted-foreground" />
                                </div>
                            </div>
                            <div v-if="form.errors.status" class="text-[10px] text-destructive font-black uppercase px-1 mt-1">{{ form.errors.status }}</div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.freebies || 'Bonus Freebies' }}</label>
                            
                            <!-- Custom Multi-Select for Freebies -->
                            <div class="relative">
                                <Tag class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground z-10 pointer-events-none" />
                                <div 
                                    @click="showFreebieDropdown = !showFreebieDropdown"
                                    class="w-full h-12 bg-background border border-secondary rounded-2xl pl-10 pr-4 text-[13px] lg:text-sm focus:ring-2 focus:ring-primary/20 flex items-center justify-between cursor-pointer"
                                >
                                    <span :class="form.freebie_ids.length ? 'text-primary font-bold' : 'text-muted-foreground font-medium'">
                                        {{ form.freebie_ids.length ? `${form.freebie_ids.length} Freebies Selected` : (ui.no_freebies || 'No Freebies') }}
                                    </span>
                                    <ChevronDown class="w-4 h-4 text-muted-foreground" />
                                </div>
                                
                                <!-- Backdrop -->
                                <div v-if="showFreebieDropdown" class="fixed inset-0 z-20" @click="showFreebieDropdown = false"></div>
                                
                                <!-- Dropdown List -->
                                <div v-if="showFreebieDropdown" class="absolute z-30 top-full left-0 right-0 mt-2 bg-card border border-secondary rounded-2xl shadow-xl overflow-hidden">
                                    <div class="max-h-60 overflow-y-auto p-2 space-y-1 no-scrollbar">
                                        <div 
                                            v-for="freebie in freebies" :key="freebie.freeid"
                                            @click="toggleFreebieSelection(freebie.freeid)"
                                            class="flex items-center gap-3 p-3 rounded-xl cursor-pointer transition-colors"
                                            :class="form.freebie_ids.includes(freebie.freeid) ? 'bg-primary/10 text-primary' : 'hover:bg-secondary/20'"
                                        >
                                            <component :is="form.freebie_ids.includes(freebie.freeid) ? CheckSquare : Square" class="w-4 h-4" />
                                            <span class="text-sm font-bold uppercase tracking-tight">{{ freebie.name }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Selected Freebies Qty Adjustment -->
                            <div v-if="form.freebie_ids.length > 0" class="flex flex-wrap gap-2 mt-2">
                                <div v-for="id in form.freebie_ids" :key="id" class="flex items-center gap-2 bg-primary/10 text-primary px-3 py-1.5 rounded-xl border border-primary/20 group animate-in fade-in zoom-in duration-200">
                                    <span class="text-[9px] font-black uppercase tracking-tight truncate max-w-[100px]">
                                        {{ freebies.find(f => f.freeid === id)?.name || 'Unknown' }}
                                    </span>
                                    <Input 
                                        v-model.number="form.freebie_qtys[id]" 
                                        type="number"
                                        min="1"
                                        class="w-12 h-6 text-center bg-white/50 border-primary/20 text-primary rounded-lg text-[10px] font-bold focus:ring-primary/50 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                        @click.stop
                                    />
                                </div>
                            </div>
                            <div v-if="form.errors.freebie_ids" class="text-[10px] text-destructive font-black uppercase px-1 mt-1">{{ form.errors.freebie_ids }}</div>
                        </div>

                        <div v-if="form.status === 'Certain Period'" class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.date_from || 'Date From' }}</label>
                            <Input 
                                v-model="form.datefrom" 
                                type="date"
                                class="h-12 bg-background border-secondary rounded-2xl text-[13px] lg:text-sm font-medium focus:ring-2 focus:ring-primary/20" 
                                :disabled="form.days.length > 0"
                            />
                            <div v-if="form.errors.datefrom" class="text-[10px] text-destructive font-black uppercase px-1 mt-1">{{ form.errors.datefrom }}</div>
                        </div>

                        <div v-if="form.status === 'Certain Period'" class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.date_to || 'Date To' }}</label>
                            <Input 
                                v-model="form.dateto" 
                                type="date"
                                class="h-12 bg-background border-secondary rounded-2xl text-[13px] lg:text-sm font-medium focus:ring-2 focus:ring-primary/20" 
                                :disabled="form.days.length > 0"
                            />
                            <div v-if="form.errors.dateto" class="text-[10px] text-destructive font-black uppercase px-1 mt-1">{{ form.errors.dateto }}</div>
                        </div>

                        <!-- Time Range -->
                        <div v-if="form.status === 'Certain Period'" class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.time_from || 'Time From' }}</label>
                            <Input 
                                v-model="form.timefrom" 
                                type="time"
                                class="h-12 bg-background border-secondary rounded-2xl text-[13px] lg:text-sm font-medium focus:ring-2 focus:ring-primary/20" 
                            />
                            <div v-if="form.errors.timefrom" class="text-[10px] text-destructive font-black uppercase px-1 mt-1">{{ form.errors.timefrom }}</div>
                        </div>

                        <div v-if="form.status === 'Certain Period'" class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.time_to || 'Time To' }}</label>
                            <Input 
                                v-model="form.timeto" 
                                type="time"
                                class="h-12 bg-background border-secondary rounded-2xl text-[13px] lg:text-sm font-medium focus:ring-2 focus:ring-primary/20" 
                            />
                            <div v-if="form.errors.timeto" class="text-[10px] text-destructive font-black uppercase px-1 mt-1">{{ form.errors.timeto }}</div>
                        </div>

                        <!-- Day Selector -->
                        <div v-if="form.status === 'Certain Period'" class="sm:col-span-2 space-y-3 pt-2">
                            <label class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground ml-1" :class="{'opacity-50': form.datefrom || form.dateto}">{{ ui.active_days || 'Active Days' }}</label>
                            <div class="flex flex-wrap gap-2" :class="{'pointer-events-none opacity-50': form.datefrom || form.dateto}">
                                <div 
                                    v-for="day in daysOfWeek" :key="day"
                                    @click="toggleDaySelection(day)"
                                    :class="[
                                        'flex items-center gap-2 px-4 py-2 rounded-xl cursor-pointer border transition-all duration-300',
                                        form.days.includes(day) 
                                            ? 'bg-primary/10 border-primary text-primary shadow-sm' 
                                            : 'bg-background border-secondary text-muted-foreground hover:border-primary/30'
                                    ]"
                                >
                                    <component :is="form.days.includes(day) ? CheckSquare : Square" class="w-3.5 h-3.5" />
                                    <span class="text-[10px] font-black uppercase tracking-wider">{{ ui[day.toLowerCase()] || day }}</span>
                                </div>
                            </div>
                            <p v-if="form.datefrom || form.dateto" class="text-[9px] text-destructive font-black uppercase ml-1">{{ ui.days_disabled || 'Specific days cannot be chosen when a date range is set.' }}</p>
                            <p v-else class="text-[9px] text-muted-foreground italic font-medium ml-1">* {{ ui.days_hint || 'If no days selected, it will be active every day in the period.' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Menu Selection & Package Contents -->
                <div class="space-y-5">
                    <div class="flex items-center justify-between px-1">
                        <div class="flex items-center gap-2">
                            <div class="p-1.5 rounded-lg bg-primary/10 text-primary">
                                <List class="w-4 h-4" />
                            </div>
                            <h3 class="text-xs font-black uppercase tracking-widest text-primary">{{ ui.menu_selection || 'Menu Selection' }}</h3>
                        </div>
                        <div class="flex items-center gap-3">
                            <button type="button" @click="selectAllFiltered" class="text-[9px] font-black uppercase tracking-widest text-primary hover:underline">{{ ui.select_all || 'Select All' }}</button>
                            <div class="w-1 h-1 rounded-full bg-secondary"></div>
                            <button type="button" @click="clearSelection" class="text-[9px] font-black uppercase tracking-widest text-destructive hover:underline">{{ ui.clear || 'Clear' }}</button>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <!-- Menu Search inside selection area -->
                        <div class="relative">
                            <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                            <Input 
                                v-model="menuSearchQuery" 
                                :placeholder="ui.filter_menus || 'Filter menus by name...'" 
                                class="pl-11 h-12 bg-secondary/5 border-secondary/50 rounded-2xl text-xs font-medium focus:bg-background transition-all"
                            />
                        </div>

                        <!-- Enhanced Multi-select Menu List -->
                        <div class="border-2 border-secondary/20 rounded-[2.5rem] p-3 space-y-2 bg-secondary/5 shadow-inner min-h-[150px] max-h-[360px] overflow-y-auto no-scrollbar">
                            <div 
                                v-for="menu in filteredMenus" 
                                :key="menu.menuid"
                                class="flex flex-col gap-2"
                            >
                                <div
                                    @click="toggleMenuSelection(menu.menuid)"
                                    :class="[
                                        'group flex items-center justify-between p-4 rounded-[1.8rem] cursor-pointer transition-all duration-300 relative',
                                        form.menuids.includes(menu.menuid) 
                                            ? 'bg-primary text-primary-foreground shadow-xl shadow-primary/20 scale-[0.98]' 
                                            : 'bg-background border border-secondary hover:border-primary/30 hover:shadow-md'
                                    ]"
                                >
                                    <div class="flex items-center gap-4">
                                        <div :class="[
                                            'w-10 h-10 rounded-2xl flex items-center justify-center transition-colors',
                                            form.menuids.includes(menu.menuid) ? 'bg-white/20' : 'bg-secondary/10 group-hover:bg-primary/10 group-hover:text-primary'
                                        ]">
                                            <Tag class="w-4 h-4" />
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs font-black uppercase tracking-tight">{{ menu.name }}</span>
                                            <span :class="['text-[10px] font-bold', form.menuids.includes(menu.menuid) ? 'text-white/60' : 'text-muted-foreground']">
                                                {{ formatCurrency(menu.price) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div v-if="form.menuids.includes(menu.menuid)" class="flex flex-col sm:flex-row items-center gap-2" @click.stop>
                                            <!-- Quantity Input -->
                                            <Input 
                                                v-model.number="form.menu_qtys[menu.menuid]" 
                                                type="number"
                                                min="1"
                                                class="w-16 h-8 text-center bg-white/20 border-white/30 text-white placeholder:text-white/50 rounded-xl text-xs font-bold focus:ring-white/50 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                                title="Quantity"
                                            />
                                            
                                            <!-- Toggle Inline Addons -->
                                            <button 
                                                v-if="getAvailableAddons(menu.menuid).length > 0"
                                                type="button"
                                                @click="activeAddonDropdown = activeAddonDropdown === menu.menuid ? null : menu.menuid"
                                                class="w-24 sm:w-28 h-8 bg-white/20 border-white/30 text-white rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center justify-between px-3 hover:bg-white/30 transition-colors"
                                            >
                                                <span class="truncate">{{ form.menu_addons[menu.menuid]?.length ? `${form.menu_addons[menu.menuid].length} Addons` : 'No Addon' }}</span>
                                                <ChevronDown :class="['w-3 h-3 ml-1 shrink-0 transition-transform', activeAddonDropdown === menu.menuid ? 'rotate-180' : '']" />
                                            </button>
                                        </div>
                                        <component :is="form.menuids.includes(menu.menuid) ? CheckSquare : Square" :class="['w-5 h-5 transition-transform group-active:scale-90', form.menuids.includes(menu.menuid) ? 'text-white' : 'text-muted-foreground/30']" />
                                    </div>
                                </div>
                                
                                <!-- Inline Addon List (Accordion) -->
                                <div v-if="form.menuids.includes(menu.menuid) && activeAddonDropdown === menu.menuid && getAvailableAddons(menu.menuid).length > 0" class="pl-14 pr-4 py-2 animate-in slide-in-from-top-2 fade-in duration-200">
                                    <div class="bg-card rounded-2xl border border-secondary p-3 space-y-1">
                                        <div class="flex items-center justify-between px-2 py-1 mb-2 border-b border-secondary/50">
                                            <span class="text-[9px] font-black uppercase text-primary tracking-widest">Select Addons</span>
                                            <button type="button" @click="activeAddonDropdown = null" class="text-muted-foreground hover:text-primary"><X class="w-3 h-3" /></button>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            <div 
                                                v-for="addon in getAvailableAddons(menu.menuid)" 
                                                :key="addon.addonid"
                                                @click="toggleMenuAddon(menu.menuid, addon.addonid)"
                                                class="flex items-center justify-between p-2.5 rounded-xl cursor-pointer transition-colors"
                                                :class="form.menu_addons[menu.menuid]?.includes(addon.addonid) ? 'bg-primary border border-primary text-primary-foreground shadow-sm' : 'bg-background border border-secondary hover:border-primary/30 text-foreground'"
                                            >
                                                <div class="flex items-center gap-2">
                                                    <component :is="form.menu_addons[menu.menuid]?.includes(addon.addonid) ? CheckSquare : Square" class="w-3.5 h-3.5" />
                                                    <span class="text-[10px] font-bold uppercase tracking-tight truncate">{{ addon.name }}</span>
                                                </div>
                                                <span class="text-[8px] font-black shrink-0">+{{ formatCurrency(addon.price) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div v-if="filteredMenus.length === 0" class="py-12 text-center">
                                <div class="inline-flex p-4 rounded-full bg-secondary/30 text-muted-foreground mb-3 opacity-20">
                                    <Search class="w-8 h-8" />
                                </div>
                                <p class="text-xs font-black uppercase tracking-widest text-muted-foreground">{{ ui.no_menus_match || 'No menus found' }}</p>
                            </div>
                        </div>
                        <div v-if="form.errors.menuids" class="text-[10px] text-destructive font-black uppercase px-1 mt-1">{{ form.errors.menuids }}</div>
                        
                        <!-- Selection Footer -->
                        <div class="flex items-center justify-between px-4 py-3 bg-primary/5 rounded-2xl border border-primary/10">
                            <span class="text-[10px] font-black uppercase tracking-widest text-primary">{{ ui.selected_items || 'Selected Items' }}</span>
                            <div class="flex items-center gap-2">
                                <div class="h-6 px-3 rounded-full bg-primary flex items-center justify-center text-[10px] font-black text-primary-foreground">
                                    {{ form.menuids.length }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="flex flex-col sm:flex-row justify-end gap-3 pt-6 border-t border-secondary/50">
                    <Button type="button" variant="ghost" @click="showModal = false" class="rounded-2xl font-black uppercase tracking-widest text-[10px] h-12 order-2 sm:order-1 px-8 hover:bg-secondary/50">{{ ui.cancel || 'Cancel' }}</Button>
                    <Button type="submit" class="gap-2 px-10 h-12 rounded-2xl shadow-xl shadow-primary/20 order-1 sm:order-2 active:scale-95 transition-transform" :disabled="form.processing || (!editingPromo && form.menuids.length === 0)">
                        <Save class="w-4 h-4" />
                        <span class="font-black uppercase tracking-widest text-[10px]">{{ editingPromo ? (ui.update_package || 'Update Package') : (ui.create_package || 'Create Package') }}</span>
                    </Button>
                </div>
            </form>
        </Modal>

        <ConfirmationModal 
            :show="showConfirmModal" 
            :title="ui.delete_package || 'Delete Package'"
            :message="ui.confirm_delete_package || 'Are you sure you want to delete this package?'"
            :impact="impactData"
            type="danger"
            :confirmText="ui.delete || 'Delete'"
            @close="showConfirmModal = false"
            @confirm="deletePromo"
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



