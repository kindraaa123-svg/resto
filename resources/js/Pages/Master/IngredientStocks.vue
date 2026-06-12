<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router, usePage, useForm } from '@inertiajs/vue3'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Input from '@/Components/ui/Input.vue'
import Modal from '@/Components/ui/Modal.vue'
import ConfirmationModal from '@/Components/ui/ConfirmationModal.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import { Search, Building, Box, Scale, Plus, Package, Layers, ArrowUpCircle, ArrowDownCircle, DollarSign, Save, X, Trash2, ArrowRightCircle, Info, Image as ImageIcon, CheckCircle, XCircle, Clock } from '@lucide/vue'
import { ref, watch, computed, onMounted, onUnmounted } from 'vue'

defineOptions({ layout: AppLayout })

const page = usePage()
const props = defineProps({
    ingredientStocks: Object,
    ingredients: Array,
    branches: Array,
    existingStockIds: Array,
    pendingRequests: Array,
    pendingDisposals: Array,
    filters: Object,
    errors: Object,
    auth: Object,
    translations: Object,
})

const ui = computed(() => page.props.translations?.ui || {})
const userBranchId = computed(() => page.props.auth.user.branchid)
const isBranchUser = computed(() => !!userBranchId.value)

const activeFilters = ref({
    search: props.filters?.search || '',
    branchid: isBranchUser.value ? userBranchId.value : (props.filters?.branchid || ''),
})

const showLogModal = ref(false)
const showRequestModal = ref(false)
const showDisposeModal = ref(false)
const showAddStockModal = ref(false)
const showAdjustStockModal = ref(false)
const showDeleteModal = ref(false)
const stockToDelete = ref(null)
const activePendingTab = ref('requests') // 'requests' or 'disposals'

const addStockForm = useForm({
    ingredientid: '',
    branchid: isBranchUser.value ? userBranchId.value : '',
    qty: '',
    total_price: '',
})

const adjustStockForm = useForm({
    ingredientid: '',
    branchid: isBranchUser.value ? userBranchId.value : '',
    physical_qty: '',
})

const availableIngredients = computed(() => {
    if (!addStockForm.branchid) return props.ingredients
    const takenIds = (props.existingStockIds || [])
        .filter(s => s.branchid == addStockForm.branchid)
        .map(s => s.ingredientid)
    return props.ingredients.filter(i => !takenIds.includes(i.ingredientid))
})

const openAddStockModal = (stock = null) => {
    addStockForm.reset()
    if (stock) {
        addStockForm.ingredientid = stock.ingredientid
        addStockForm.branchid = stock.branchid
    } else {
        addStockForm.branchid = userBranchId.value || (props.branches?.length > 0 ? props.branches[0].branchid : '')
        addStockForm.ingredientid = ''
    }
    showAddStockModal.value = true
}

const openAdjustStockModal = (stock = null) => {
    adjustStockForm.reset()
    if (stock) {
        adjustStockForm.ingredientid = stock.ingredientid
        adjustStockForm.branchid = stock.branchid
        adjustStockForm.physical_qty = stock.qty
    } else {
        adjustStockForm.branchid = userBranchId.value || (props.branches?.length > 0 ? props.branches[0].branchid : '')
        adjustStockForm.ingredientid = ''
    }
    showAdjustStockModal.value = true
}

const submitAddStock = () => {
    addStockForm.post(route('ingredient.ingredient-stocks.add'), {
        onSuccess: () => {
            showAddStockModal.value = false
            addStockForm.reset()
        }
    })
}

const submitAdjustStock = () => {
    adjustStockForm.post(route('ingredient.ingredient-stocks.adjust'), {
        onSuccess: () => {
            showAdjustStockModal.value = false
            adjustStockForm.reset()
        }
    })
}

const confirmDelete = (stock) => {
    stockToDelete.value = stock
    showDeleteModal.value = true
}

const deleteStock = () => {
    if (stockToDelete.value) {
        router.delete(route('ingredient.ingredient-stocks.destroy', stockToDelete.value.stockid), {
            onSuccess: () => {
                showDeleteModal.value = false
                stockToDelete.value = null
            }
        })
    }
}

const applyFilters = () => {
    router.get(route('ingredient.ingredient-stocks.index'), {
        filter: {
            search: activeFilters.value.search,
            branchid: activeFilters.value.branchid,
        }
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    })
}

const approveRequest = (id) => {
    if (confirm('Approve this request? Stock will be increased.')) {
        router.post(route('ingredient.ingredient-stocks.request.approve', id))
    }
}

const rejectRequest = (id) => {
    if (confirm('Reject this request?')) {
        router.post(route('ingredient.ingredient-stocks.request.reject', id))
    }
}

const approveDisposal = (id) => {
    if (confirm('Approve this disposal? Stock will be decreased.')) {
        router.post(route('ingredient.ingredient-stocks.dispose.approve', id))
    }
}

const rejectDisposal = (id) => {
    if (confirm('Reject this disposal?')) {
        router.post(route('ingredient.ingredient-stocks.dispose.reject', id))
    }
}

// Debounce search
let timeout = null
watch(activeFilters, () => {
    clearTimeout(timeout)
    timeout = setTimeout(() => {
        applyFilters()
    }, 300)
}, { deep: true })

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
    <Head :title="ui.inventory_stock || 'Ingredient Stocks Management'" />

    <div class="max-w-7xl mx-auto space-y-6 lg:space-y-8 pb-20">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-2 lg:px-0">
            <div>
                <h1 class="text-2xl lg:text-3xl font-black tracking-tight text-primary uppercase">{{ ui.inventory_stock || 'Ingredient Stock' }}</h1>
                <p class="text-muted-foreground text-[10px] lg:text-sm font-medium uppercase tracking-widest mt-1">{{ ui.manage_current_inventory || 'Manage Branch Inventory' }}</p>
            </div>

            <Button @click="openAddStockModal()" class="gap-2 h-12 w-full sm:w-auto px-6 rounded-2xl shadow-lg shadow-primary/20">
                <Plus class="w-5 h-5" />
                {{ ui.add_ingredient || 'Add New Ingredient Stock' }}
            </Button>
        </div>

        <!-- Pending Actions Section -->
        <Card v-if="(pendingRequests?.length > 0 || pendingDisposals?.length > 0) && !isBranchUser" class="border-none shadow-sm bg-amber-50 mx-2 lg:mx-0 overflow-hidden">
            <div class="p-4 lg:p-6 space-y-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-amber-700">
                        <Clock class="w-5 h-5" />
                        <h2 class="text-xs font-black uppercase tracking-widest">{{ ui.pending_actions || 'Pending Actions' }}</h2>
                    </div>
                    <div class="flex bg-amber-100 rounded-xl p-1 gap-1">
                        <button 
                            @click="activePendingTab = 'requests'"
                            :class="['px-4 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all', activePendingTab === 'requests' ? 'bg-white text-amber-700 shadow-sm' : 'text-amber-600/60 hover:text-amber-700']"
                        >
                            Requests ({{ pendingRequests?.length || 0 }})
                        </button>
                        <button 
                            @click="activePendingTab = 'disposals'"
                            :class="['px-4 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all', activePendingTab === 'disposals' ? 'bg-white text-amber-700 shadow-sm' : 'text-amber-600/60 hover:text-amber-700']"
                        >
                            Disposals ({{ pendingDisposals?.length || 0 }})
                        </button>
                    </div>
                </div>

                <div v-if="activePendingTab === 'requests' && pendingRequests?.length > 0" class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[9px] font-black uppercase tracking-widest text-amber-700/50">
                                <th class="pb-3 pr-4">Ingredient</th>
                                <th class="pb-3 pr-4">Branch</th>
                                <th class="pb-3 pr-4">Qty</th>
                                <th class="pb-3 pr-4">Requester</th>
                                <th class="pb-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-amber-100">
                            <tr v-for="req in pendingRequests" :key="req.reqingredient_id" class="text-[11px] font-bold text-amber-900">
                                <td class="py-3 pr-4 uppercase">{{ req.ingredient?.name }}</td>
                                <td class="py-3 pr-4">{{ req.branch?.branchname }}</td>
                                <td class="py-3 pr-4">{{ req.qty }} {{ req.unit }}</td>
                                <td class="py-3 pr-4">{{ req.requester?.name }}</td>
                                <td class="py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="approveRequest(req.reqingredient_id)" class="p-1.5 rounded-lg bg-green-500 text-white shadow-sm hover:scale-110 transition-transform">
                                            <CheckCircle class="w-4 h-4" />
                                        </button>
                                        <button @click="rejectRequest(req.reqingredient_id)" class="p-1.5 rounded-lg bg-red-500 text-white shadow-sm hover:scale-110 transition-transform">
                                            <XCircle class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="activePendingTab === 'disposals' && pendingDisposals?.length > 0" class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[9px] font-black uppercase tracking-widest text-amber-700/50">
                                <th class="pb-3 pr-4">Ingredient</th>
                                <th class="pb-3 pr-4">Branch</th>
                                <th class="pb-3 pr-4">Qty</th>
                                <th class="pb-3 pr-4">Reason</th>
                                <th class="pb-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-amber-100">
                            <tr v-for="disp in pendingDisposals" :key="disp.disposeingredient_id" class="text-[11px] font-bold text-amber-900">
                                <td class="py-3 pr-4 uppercase">{{ disp.ingredient?.name }}</td>
                                <td class="py-3 pr-4">{{ disp.branch?.branchname }}</td>
                                <td class="py-3 pr-4">{{ disp.qty }} {{ disp.unit }}</td>
                                <td class="py-3 pr-4">
                                    <span class="px-2 py-0.5 rounded-full bg-amber-100 border border-amber-200 text-[9px] font-black uppercase">{{ disp.reason }}</span>
                                </td>
                                <td class="py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button v-if="disp.evidence" @click="window.open('/storage/' + disp.evidence, '_blank')" class="p-1.5 rounded-lg bg-blue-500 text-white shadow-sm hover:scale-110 transition-transform" title="View Evidence">
                                            <ImageIcon class="w-4 h-4" />
                                        </button>
                                        <button @click="approveDisposal(disp.disposeingredient_id)" class="p-1.5 rounded-lg bg-green-500 text-white shadow-sm hover:scale-110 transition-transform">
                                            <CheckCircle class="w-4 h-4" />
                                        </button>
                                        <button @click="rejectDisposal(disp.disposeingredient_id)" class="p-1.5 rounded-lg bg-red-500 text-white shadow-sm hover:scale-110 transition-transform">
                                            <XCircle class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="(activePendingTab === 'requests' && pendingRequests?.length === 0) || (activePendingTab === 'disposals' && pendingDisposals?.length === 0)" class="py-6 text-center text-amber-600/40 italic text-xs font-bold uppercase tracking-widest">
                    No pending {{ activePendingTab }} items.
                </div>
            </div>
        </Card>

        <Card class="border-none shadow-sm bg-card/50 overflow-hidden mx-2 lg:mx-0">
            <!-- Table Controls -->
            <div class="p-4 lg:p-6 border-b border-secondary/50 flex flex-col sm:flex-row items-center gap-3 lg:gap-4">
                <div class="relative w-full sm:max-w-xs">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                    <Input 
                        v-model="activeFilters.search" 
                        :placeholder="ui.search_menu || 'Search ingredients...'" 
                        class="pl-10 bg-background h-11 border-transparent focus:border-primary/20 text-sm"
                    />
                </div>
                
                <div class="relative w-full sm:max-w-[200px]" :class="{'opacity-60 cursor-not-allowed': isBranchUser}">
                    <Building class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                    <select 
                        v-model="activeFilters.branchid"
                        class="w-full pl-10 h-11 rounded-xl border-transparent bg-background text-sm focus:ring-1 focus:ring-ring"
                        :disabled="isBranchUser"
                    >
                        <option value="">{{ ui.all_branches || 'All Branches' }}</option>
                        <option v-for="branch in branches" :key="branch.branchid" :value="branch.branchid">
                            {{ branch.branchname }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Desktop Table -->
            <div v-if="!isMobileView" class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-secondary/20 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">
                            <th class="px-6 py-4">{{ ui.ingredient_name || 'Ingredient' }}</th>
                            <th class="px-6 py-4">{{ ui.branch || 'Branch' }}</th>
                            <th class="px-6 py-4 text-center">{{ ui.qty || 'Quantity' }}</th>
                            <th class="px-6 py-4 text-right">{{ ui.actions || 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-secondary/30">
                        <tr v-for="item in ingredientStocks?.data || []" :key="item.stockid" class="group hover:bg-white/50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="font-black text-primary text-sm uppercase tracking-wide">{{ item.ingredient_name }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-primary/5 border border-primary/10 text-xs font-bold text-primary">
                                    <Building class="w-3.5 h-3.5" />
                                    {{ item.branch_name }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-sm font-black text-primary">{{ item.display_qty }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="openAddStockModal(item)" class="p-2 hover:bg-green-100 text-green-600 rounded-lg transition-colors" title="Supplying / Add Stock">
                                        <ArrowUpCircle class="w-4 h-4" />
                                    </button>
                                    <button @click="openAdjustStockModal(item)" class="p-2 hover:bg-amber-100 text-amber-600 rounded-lg transition-colors" title="Stock Opname / Adjust">
                                        <Scale class="w-4 h-4" />
                                    </button>
                                    <button @click="confirmDelete(item)" class="p-2 hover:bg-destructive/10 text-destructive rounded-lg transition-colors" title="Delete">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!ingredientStocks?.data || ingredientStocks.data.length === 0">
                            <td colspan="4" class="px-6 py-12 text-center text-muted-foreground italic text-sm">
                                {{ ui.no_data_available || 'No ingredient stocks found.' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div v-else class="bg-secondary/5">
                <div class="divide-y divide-secondary/30 p-2 space-y-2">
                    <div v-for="item in ingredientStocks?.data || []" :key="item.stockid" class="bg-white border border-secondary/50 rounded-3xl p-4 shadow-sm relative overflow-hidden flex flex-col gap-3">
                        <div class="flex justify-between items-start gap-4">
                            <div class="space-y-1">
                                <h3 class="text-sm font-black text-primary uppercase tracking-tight leading-tight">{{ item.ingredient_name }}</h3>
                                <div class="inline-flex items-center gap-1 text-[9px] font-bold text-muted-foreground uppercase tracking-widest">
                                    <Building class="w-3 h-3" />
                                    {{ item.branch_name }}
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                <button @click="openAddStockModal(item)" class="p-2 bg-green-50 text-green-600 rounded-xl" title="Add Stock">
                                    <ArrowUpCircle class="w-4 h-4" />
                                </button>
                                <button @click="openAdjustStockModal(item)" class="p-2 bg-amber-50 text-amber-600 rounded-xl" title="Adjust Stock">
                                    <Scale class="w-4 h-4" />
                                </button>
                                <button @click="confirmDelete(item)" class="p-2 bg-destructive/5 text-destructive rounded-xl">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-secondary/10 rounded-2xl border border-secondary/20">
                            <span class="text-[9px] font-black uppercase tracking-widest text-muted-foreground flex items-center gap-1">
                                <Scale class="w-3 h-3" /> {{ ui.qty || 'Quantity' }}
                            </span>
                            <span class="text-sm font-black text-primary">{{ item.display_qty }}</span>
                        </div>
                    </div>

                    <div v-if="!ingredientStocks?.data || ingredientStocks.data.length === 0" class="py-12 text-center">
                        <div class="inline-flex p-4 rounded-full bg-secondary/30 text-muted-foreground mb-3">
                            <Box class="w-8 h-8 opacity-20" />
                        </div>
                        <h3 class="text-sm font-black text-primary uppercase">{{ ui.no_data_available || 'No ingredient stocks found.' }}</h3>
                    </div>
                </div>
            </div>

            <div class="p-4 lg:p-6 border-t border-secondary/50">
                <Pagination :links="ingredientStocks?.links || []" />
            </div>
        </Card>

        <!-- Add Stock Modal (Supplying) -->
        <Modal :show="showAddStockModal" title="Supplying / Add Ingredient Stock" @close="showAddStockModal = false">
            <form @submit.prevent="submitAddStock" class="p-6 space-y-6">
                <div class="space-y-4">
                    <div v-if="!addStockForm.ingredientid || !addStockForm.branchid" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">Select Ingredient</label>
                            <select v-model="addStockForm.ingredientid" class="w-full h-11 px-4 rounded-xl border border-secondary bg-background text-sm font-bold outline-none focus:ring-1 focus:ring-primary" required>
                                <option value="">-- Choose Ingredient --</option>
                                <option v-for="ing in availableIngredients" :key="ing.ingredientid" :value="ing.ingredientid">{{ ing.name }}</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">Select Branch</label>
                            <select v-model="addStockForm.branchid" class="w-full h-11 px-4 rounded-xl border border-secondary bg-background text-sm font-bold outline-none focus:ring-1 focus:ring-primary" :disabled="isBranchUser" required>
                                <option value="">-- Choose Branch --</option>
                                <option v-for="b in branches" :key="b.branchid" :value="b.branchid">{{ b.branchname }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">Quantity to Add</label>
                            <Input v-model.number="addStockForm.qty" type="number" step="0.01" min="0.01" class="h-11 font-black text-lg" required placeholder="0.00" />
                            <p class="text-[10px] text-muted-foreground italic mt-1">* Unit matches master definition.</p>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">Total Spending (Price)</label>
                            <div class="relative">
                                <div class="absolute left-0 top-0 bottom-0 w-10 flex items-center justify-center bg-secondary/30 border-r border-secondary rounded-l-xl text-primary font-black text-[10px]">Rp</div>
                                <Input v-model.number="addStockForm.total_price" type="number" class="pl-12 h-11 bg-background text-sm font-black" placeholder="0" required />
                            </div>
                            <p class="text-[10px] text-muted-foreground italic mt-1">* Mandatory for procurement logs.</p>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-6 border-t border-secondary/50">
                    <Button type="button" variant="ghost" @click="showAddStockModal = false">Cancel</Button>
                    <Button type="submit" class="gap-2 px-8 h-11 rounded-xl bg-green-600 hover:bg-green-700 shadow-lg shadow-green-200" :disabled="addStockForm.processing">
                        <ArrowUpCircle class="w-4 h-4" />
                        Confirm Add Stock
                    </Button>
                </div>
            </form>
        </Modal>

        <!-- Adjust Stock Modal (Stock Opname) -->
        <Modal :show="showAdjustStockModal" title="Stock Opname / Physical Adjustment" @close="showAdjustStockModal = false">
            <form @submit.prevent="submitAdjustStock" class="p-6 space-y-6">
                <div class="space-y-4">
                    <div class="p-4 bg-amber-50 rounded-2xl border border-amber-200 space-y-2">
                        <div class="flex items-center gap-2 text-amber-700 font-black text-xs uppercase tracking-widest">
                            <Info class="w-4 h-4" /> Stock Opname Rule
                        </div>
                        <p class="text-[10px] text-amber-600 font-medium leading-relaxed">
                            Input the ACTUAL PHYSICAL amount found in the branch. The system will automatically calculate the difference and update the current stock to match your input exactly.
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">Actual Physical Quantity</label>
                        <Input v-model.number="adjustStockForm.physical_qty" type="number" step="0.01" min="0" class="h-11 font-black text-lg" required placeholder="0.00" />
                        <p class="text-[10px] text-muted-foreground italic mt-1">* This value will become the NEW current stock.</p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-6 border-t border-secondary/50">
                    <Button type="button" variant="ghost" @click="showAdjustStockModal = false">Cancel</Button>
                    <Button type="submit" class="gap-2 px-8 h-11 rounded-xl bg-amber-600 hover:bg-amber-700 shadow-lg shadow-amber-200" :disabled="adjustStockForm.processing">
                        <Scale class="w-4 h-4" />
                        Sync with Physical Count
                    </Button>
                </div>
            </form>
        </Modal>

        <ConfirmationModal 
            :show="showDeleteModal" 
            title="Delete Stock Record"
            message="Are you sure you want to delete this stock record? This will remove the ingredient from this branch's inventory list. Movement logs will remain for audit purposes."
            type="danger"
            confirmText="Delete Record"
            @close="showDeleteModal = false"
            @confirm="deleteStock"
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
