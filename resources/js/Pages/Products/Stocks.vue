<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm, usePage, router } from '@inertiajs/vue3'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Input from '@/Components/ui/Input.vue'
import Modal from '@/Components/ui/Modal.vue'
import ConfirmationModal from '@/Components/ui/ConfirmationModal.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import { Plus, Search, Building, Box, Scale, Save, Minus, ArrowUpCircle, Info, Trash2 } from '@lucide/vue'
import { ref, computed, onMounted, onUnmounted } from 'vue'

defineOptions({ layout: AppLayout })

const page = usePage()
const props = defineProps({
    stocks: Object,
    products: Array,
    branches: Array,
    existingStockIds: Array,
    errors: Object,
    auth: Object,
    translations: Object,
})

const ui = computed(() => page.props.translations?.ui || {})
const userBranchId = computed(() => page.props.auth.user.branchid)
const isBranchUser = computed(() => !!userBranchId.value)

const searchQuery = ref('')
const branchFilter = ref(isBranchUser.value ? userBranchId.value : '')
const showAddStockModal = ref(false)
const showAdjustStockModal = ref(false)
const showDeleteModal = ref(false)
const stockToDelete = ref(null)

const availableProducts = computed(() => {
    if (!addStockForm.branchid) return props.products
    
    const takenProductIds = props.existingStockIds
        .filter(s => s.branchid == addStockForm.branchid)
        .map(s => s.productid)
        
    return props.products.filter(p => !takenProductIds.includes(p.productid))
})

const filteredStocks = computed(() => {
    return props.stocks.data.filter(s => {
        const matchesSearch = (s.product?.productname || '').toLowerCase().includes(searchQuery.value.toLowerCase())
        const matchesBranch = !branchFilter.value || s.branchid == branchFilter.value
        return matchesSearch && matchesBranch
    })
})

const addStockForm = useForm({
    productid: '',
    branchid: isBranchUser.value ? userBranchId.value : '',
    qty: '',
    price: '',
})

const adjustStockForm = useForm({
    productid: '',
    branchid: isBranchUser.value ? userBranchId.value : '',
    physical_qty: '',
})

const openAddStockModal = (stock = null) => {
    addStockForm.reset()
    if (stock) {
        addStockForm.productid = stock.productid
        addStockForm.branchid = stock.branchid
    } else {
        addStockForm.branchid = userBranchId.value || (props.branches?.length > 0 ? props.branches[0].branchid : '')
        addStockForm.productid = ''
    }
    showAddStockModal.value = true
}

const openAdjustStockModal = (stock = null) => {
    adjustStockForm.reset()
    if (stock) {
        adjustStockForm.productid = stock.productid
        adjustStockForm.branchid = stock.branchid
        adjustStockForm.physical_qty = stock.stock
    } else {
        adjustStockForm.branchid = userBranchId.value || (props.branches?.length > 0 ? props.branches[0].branchid : '')
        if (props.products?.length > 0) adjustStockForm.productid = props.products[0].productid
    }
    showAdjustStockModal.value = true
}

const submitAddStock = () => {
    addStockForm.post(route('product.product-stocks.add'), {
        onSuccess: () => {
            showAddStockModal.value = false
            addStockForm.reset()
        }
    })
}

const submitAdjustStock = () => {
    adjustStockForm.post(route('product.product-stocks.adjust'), {
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
        router.delete(route('product.product-stocks.destroy', stockToDelete.value.stockid || stockToDelete.value.productstock_id), {
            onSuccess: () => {
                showDeleteModal.value = false
                stockToDelete.value = null
            }
        })
    }
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat(page.props.locale === 'id' ? 'id-ID' : 'en-US', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount || 0)
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
    <Head :title="ui.product_stocks || 'Product Stocks'" />

    <div class="max-w-7xl mx-auto space-y-6 lg:space-y-8 pb-20">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-2 lg:px-0">
            <div>
                <h1 class="text-2xl lg:text-3xl font-black tracking-tight text-primary uppercase">{{ ui.product_stocks || 'Product Stocks' }}</h1>
                <p class="text-muted-foreground text-[10px] lg:text-sm font-medium uppercase tracking-widest mt-1">{{ ui.manage_product_inventory || 'Manage Current Product Inventory' }}</p>
            </div>
            
            <Button @click="openAddStockModal()" class="gap-2 h-12 w-full sm:w-auto px-6 rounded-2xl shadow-lg shadow-primary/20">
                <Plus class="w-5 h-5" />
                {{ ui.add_product || 'Add New Product Stock' }}
            </Button>
        </div>

        <Card class="border-none shadow-sm bg-card/50 overflow-hidden mx-2 lg:mx-0">
            <!-- Table Controls -->
            <div class="p-4 lg:p-6 border-b border-secondary/50 flex flex-col sm:flex-row items-center gap-3 lg:gap-4">
                <div class="relative w-full sm:max-w-xs">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                    <Input 
                        v-model="searchQuery" 
                        :placeholder="ui.search_products || 'Search products...'" 
                        class="pl-10 bg-background h-11 border-transparent focus:border-primary/20 text-sm"
                    />
                </div>
                
                <div class="relative w-full sm:max-w-[200px]" :class="{'opacity-60 cursor-not-allowed': isBranchUser}">
                    <Building class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                    <select 
                        v-model="branchFilter"
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
                            <th class="px-6 py-4">{{ ui.product_name || 'Product' }}</th>
                            <th class="px-6 py-4">{{ ui.branch || 'Branch' }}</th>
                            <th class="px-6 py-4 text-center">{{ ui.stock || 'Current Stock' }}</th>
                            <th class="px-6 py-4 text-right">{{ ui.actions || 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-secondary/30">
                        <tr v-for="item in filteredStocks" :key="item.productstock_id" class="group hover:bg-white/50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="font-black text-primary text-sm uppercase tracking-wide">{{ item.product?.productname }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-primary/5 border border-primary/10 text-xs font-bold text-primary">
                                    <Building class="w-3.5 h-3.5" />
                                    {{ item.branch?.branchname }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span :class="['px-3 py-1 rounded-lg text-sm font-black', item.stock <= 5 ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700']">
                                    {{ Math.round(item.stock) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="openAddStockModal(item)" class="p-2 hover:bg-green-100 text-green-600 rounded-lg transition-colors" title="Supplying / Add Stock">
                                        <ArrowUpCircle class="w-4 h-4" />
                                    </button>
                                    <button @click="openAdjustStockModal(item)" class="p-2 hover:bg-amber-100 text-amber-600 rounded-lg transition-colors" title="Stock Opname / Adjust">
                                        <Scale class="w-4 h-4" />
                                    </button>
                                    <button @click="confirmDelete(item)" class="p-2 hover:bg-destructive/10 text-destructive rounded-lg transition-colors" title="Delete Stock Record">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="filteredStocks.length === 0">
                            <td colspan="4" class="px-6 py-12 text-center text-muted-foreground italic text-sm">
                                {{ ui.no_data_available || 'No product stocks found.' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div v-else class="bg-secondary/5">
                <div class="divide-y divide-secondary/30 p-2 space-y-2">
                    <div v-for="item in filteredStocks" :key="item.productstock_id" class="bg-white border border-secondary/50 rounded-3xl p-4 shadow-sm relative overflow-hidden flex flex-col gap-3">
                        <div class="flex justify-between items-start gap-4">
                            <div class="space-y-1">
                                <h3 class="text-sm font-black text-primary uppercase tracking-tight leading-tight">{{ item.product?.productname }}</h3>
                                <div class="inline-flex items-center gap-1 text-[9px] font-bold text-muted-foreground uppercase tracking-widest">
                                    <Building class="w-3 h-3" />
                                    {{ item.branch?.branchname }}
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                <button @click="openAddStockModal(item)" class="p-2 bg-green-50 text-green-600 rounded-xl" title="Add Stock">
                                    <ArrowUpCircle class="w-4 h-4" />
                                </button>
                                <button @click="openAdjustStockModal(item)" class="p-2 bg-amber-50 text-amber-600 rounded-xl" title="Adjust Stock">
                                    <Scale class="w-4 h-4" />
                                </button>
                                <button @click="confirmDelete(item)" class="p-2 bg-destructive/5 text-destructive rounded-xl" title="Delete">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-secondary/10 rounded-2xl border border-secondary/20">
                            <span class="text-[9px] font-black uppercase tracking-widest text-muted-foreground flex items-center gap-1">
                                <Scale class="w-3 h-3" /> {{ ui.stock || 'Current Stock' }}
                            </span>
                            <span :class="['text-sm font-black', item.stock <= 5 ? 'text-red-600' : 'text-primary']">{{ Math.round(item.stock) }}</span>
                        </div>
                    </div>

                    <div v-if="filteredStocks.length === 0" class="py-12 text-center">
                        <div class="inline-flex p-4 rounded-full bg-secondary/30 text-muted-foreground mb-3">
                            <Box class="w-8 h-8 opacity-20" />
                        </div>
                        <h3 class="text-sm font-black text-primary uppercase">{{ ui.no_data_available || 'No data found' }}</h3>
                    </div>
                </div>
            </div>

            <div class="p-4 lg:p-6 border-t border-secondary/50">
                <Pagination :links="stocks.links" />
            </div>
        </Card>

        <!-- Add Stock Modal (Supplying) -->
        <Modal :show="showAddStockModal" title="Supplying / Add Product Stock" @close="showAddStockModal = false">
            <form @submit.prevent="submitAddStock" class="p-6 space-y-6">
                <div class="space-y-4">
                    <div v-if="!addStockForm.productid || !addStockForm.branchid" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">Select Product</label>
                            <select v-model="addStockForm.productid" class="w-full h-11 px-4 rounded-xl border border-secondary bg-background text-sm font-bold outline-none focus:ring-1 focus:ring-primary" required>
                                <option value="">-- Choose Product --</option>
                                <option v-for="p in availableProducts" :key="p.productid" :value="p.productid">{{ p.productname }}</option>
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
                            <Input v-model.number="addStockForm.qty" type="number" step="1" min="1" class="h-11 font-black text-lg" required placeholder="0" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">Purchase Price</label>
                            <div class="relative">
                                <div class="absolute left-0 top-0 bottom-0 w-10 flex items-center justify-center bg-secondary/30 border-r border-secondary rounded-l-xl text-primary font-black text-[10px]">Rp</div>
                                <Input v-model.number="addStockForm.price" type="number" class="pl-12 h-11 bg-background text-sm font-black" placeholder="0" required />
                            </div>
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
                        <Input v-model.number="adjustStockForm.physical_qty" type="number" step="1" min="0" class="h-11 font-black text-lg" required placeholder="0" />
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
            message="Are you sure you want to delete this stock record? This will remove the product from this branch's inventory list. Movement logs will remain for audit purposes."
            type="danger"
            confirmText="Delete Record"
            @close="showDeleteModal = false"
            @confirm="deleteStock"
        />
    </div>
</template>
