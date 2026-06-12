<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm, usePage, router } from '@inertiajs/vue3'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Input from '@/Components/ui/Input.vue'
import Modal from '@/Components/ui/Modal.vue'
import ConfirmationModal from '@/Components/ui/ConfirmationModal.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import { Plus, Pencil, Trash2, Search, Package, X, Save, Barcode, CheckCircle2, XCircle } from '@lucide/vue'
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'

defineOptions({ layout: AppLayout })

const page = usePage()
const props = defineProps({
    products: Object,
    filters: Object,
    errors: Object,
    auth: Object,
    translations: Object,
})

const ui = computed(() => page.props.translations?.ui || {})

const searchQuery = ref(props.filters?.filter?.search || '')
let searchTimeout = null

watch(searchQuery, (value) => {
    if (searchTimeout) clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        router.get(
            route('product.data-products.index'),
            { filter: { search: value } },
            { preserveState: true, preserveScroll: true, replace: true }
        )
    }, 500)
})

const showModal = ref(false)
const showConfirmModal = ref(false)
const productToDelete = ref(null)
const impactData = ref([])
const editingProduct = ref(null)

const filteredProducts = computed(() => {
    return props.products?.data || []
})

const form = useForm({
    barcode: '',
    productname: '',
    price: '',
})

const openAddModal = () => {
    editingProduct.value = null
    form.reset()
    showModal.value = true
}

const openEditModal = (product) => {
    editingProduct.value = product
    form.barcode = product.barcode
    form.productname = product.productname
    form.price = product.price
    showModal.value = true
}

const submit = () => {
    if (editingProduct.value) {
        form.put(route('product.data-products.update', editingProduct.value.productid), {
            onSuccess: () => {
                showModal.value = false
                form.reset()
            }
        })
    } else {
        form.post(route('product.data-products.store'), {
            onSuccess: () => {
                showModal.value = false
                form.reset()
            }
        })
    }
}

const confirmDelete = (product) => {
    productToDelete.value = product
    impactData.value = product.impact || []
    showConfirmModal.value = true
}

const deleteProduct = () => {
    if (productToDelete.value) {
        form.delete(route('product.data-products.destroy', productToDelete.value.productid), {
            onFinish: () => {
                showConfirmModal.value = false
                productToDelete.value = null
                impactData.value = []
            }
        })
    }
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat(page.props.locale === 'id' ? 'id-ID' : 'en-US', {
        style: 'currency',
        currency: 'IDR',
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
    <Head :title="ui.products || 'Products'" />

    <div class="max-w-7xl mx-auto space-y-6 lg:space-y-8 pb-20">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-2 lg:px-0">
            <div>
                <h1 class="text-2xl lg:text-3xl font-black tracking-tight text-primary uppercase">{{ ui.products || 'Products' }}</h1>
                <p class="text-muted-foreground text-[10px] lg:text-sm font-medium uppercase tracking-widest mt-1">{{ ui.manage_products || 'Manage Master Products' }}</p>
            </div>
            
            <Button @click="openAddModal" class="gap-2 h-12 w-full sm:w-auto px-6 rounded-2xl shadow-lg shadow-primary/20">
                <Plus class="w-5 h-5" />
                {{ ui.add_product || 'Add Product' }}
            </Button>
        </div>

        <Card class="border-none shadow-sm bg-card/50 overflow-hidden mx-2 lg:mx-0">
            <!-- Table Controls -->
            <div class="p-4 lg:p-6 border-b border-secondary/50 flex flex-col sm:flex-row items-center gap-4">
                <div class="relative w-full sm:max-w-xs">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                    <Input 
                        v-model="searchQuery" 
                        :placeholder="ui.search_products || 'Search products...'" 
                        class="pl-10 bg-background h-11 border-transparent focus:border-primary/20 text-sm"
                    />
                </div>
            </div>

            <!-- Desktop Table -->
            <div v-if="!isMobileView" class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-secondary/20 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">
                            <th class="px-6 py-4 w-20">ID</th>
                            <th class="px-6 py-4">{{ ui.barcode || 'Barcode' }}</th>
                            <th class="px-6 py-4">{{ ui.product_name || 'Product Name' }}</th>
                            <th class="px-6 py-4">{{ ui.price || 'Price' }}</th>
                            <th class="px-6 py-4 text-right">{{ ui.actions || 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-secondary/30">
                        <tr v-for="product in filteredProducts" :key="product.productid" class="group hover:bg-white/50 transition-colors">
                            <td class="px-6 py-4 text-sm font-bold text-muted-foreground">
                                #{{ product.productid }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2 text-primary font-mono text-xs font-black">
                                    <Barcode class="w-3.5 h-3.5" />
                                    {{ product.barcode }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-black text-primary uppercase tracking-tight text-sm">
                                    {{ product.productname }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-black text-primary text-sm">{{ formatCurrency(product.price) }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <button 
                                        @click="openEditModal(product)"
                                        class="p-2 hover:bg-primary/10 text-primary rounded-lg transition-colors"
                                    >
                                        <Pencil class="w-4 h-4" />
                                    </button>
                                    <button 
                                        @click="confirmDelete(product)"
                                        class="p-2 hover:bg-destructive/10 text-destructive rounded-lg transition-colors"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="filteredProducts.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-muted-foreground italic text-sm">
                                {{ ui.no_products_found || 'No products found.' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div v-else class="bg-secondary/5">
                <div class="divide-y divide-secondary/30 p-2 space-y-2">
                    <div v-for="product in filteredProducts" :key="product.productid" class="bg-white border border-secondary/50 rounded-3xl p-4 shadow-sm relative overflow-hidden flex flex-col gap-4">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary font-black shrink-0">
                                    <Package class="w-5 h-5" />
                                </div>
                                <div class="space-y-1">
                                    <span class="text-[9px] font-black text-muted-foreground uppercase tracking-widest block">#{{ product.productid }}</span>
                                    <h3 class="text-sm font-black text-primary uppercase tracking-tight leading-tight">{{ product.productname }}</h3>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-[10px] font-bold">
                            <div class="flex items-center gap-1.5 text-muted-foreground">
                                <Barcode class="w-3.5 h-3.5" />
                                {{ product.barcode }}
                            </div>
                            <span class="font-black text-primary">{{ formatCurrency(product.price) }}</span>
                        </div>

                        <div class="flex gap-2 pt-2 border-t border-secondary/30">
                            <button @click="openEditModal(product)" class="flex-1 p-3 bg-primary/10 text-primary rounded-xl transition-all active:scale-95 border border-primary/20 flex items-center justify-center gap-2">
                                <Pencil class="w-4 h-4" />
                                <span class="text-[10px] font-black uppercase tracking-widest">Edit</span>
                            </button>
                            <button @click="confirmDelete(product)" class="p-3 bg-destructive/10 text-destructive rounded-xl transition-all active:scale-95 border border-destructive/20 flex items-center justify-center">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div v-if="filteredProducts.length === 0" class="py-12 text-center">
                        <div class="inline-flex p-4 rounded-full bg-secondary/30 text-muted-foreground mb-3">
                            <Package class="w-8 h-8 opacity-20" />
                        </div>
                        <h3 class="text-sm font-black text-primary uppercase">{{ ui.no_products_found || 'No products found.' }}</h3>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div class="p-4 lg:p-6 border-t border-secondary/50">
                <Pagination :links="products.links" />
            </div>
        </Card>

        <!-- CRUD Modal -->
        <Modal :show="showModal" :title="editingProduct ? (ui.edit_product || 'Edit Product') : (ui.add_new_product || 'Add New Product')" @close="showModal = false">
            <form @submit.prevent="submit" class="p-6 space-y-6">
                <div class="space-y-2">
                    <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.barcode || 'Barcode' }}</label>
                    <div class="relative">
                        <Barcode class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                        <Input 
                            v-model="form.barcode" 
                            class="pl-10 h-10 lg:h-11 bg-background text-[13px] lg:text-sm font-mono uppercase" 
                            placeholder="EAN-13, SKU, etc."
                            required
                        />
                    </div>
                    <div v-if="form.errors.barcode" class="text-xs text-destructive font-bold">{{ form.errors.barcode }}</div>
                </div>

                <div class="space-y-2">
                    <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.product_name || 'Product Name' }}</label>
                    <div class="relative">
                        <Package class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                        <Input 
                            v-model="form.productname" 
                            class="pl-10 h-10 lg:h-11 bg-background text-[13px] lg:text-sm" 
                            placeholder="e.g. Mineral Water 600ml"
                            required
                        />
                    </div>
                    <div v-if="form.errors.productname" class="text-xs text-destructive font-bold">{{ form.errors.productname }}</div>
                </div>

                <div class="space-y-2">
                    <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.price || 'Price' }}</label>
                    <div class="relative">
                        <div class="absolute left-0 top-0 bottom-0 w-12 flex items-center justify-center bg-secondary/30 border-r border-secondary rounded-l-xl text-primary font-black text-xs">Rp</div>
                        <Input 
                            v-model="form.price" 
                            type="number"
                            class="pl-14 h-10 lg:h-11 bg-background text-[13px] lg:text-sm font-black" 
                            placeholder="0"
                            required
                        />
                    </div>
                    <div v-if="form.errors.price" class="text-xs text-destructive font-bold">{{ form.errors.price }}</div>
                </div>

                <div class="flex flex-col sm:flex-row justify-end gap-3 pt-6 border-t border-secondary/50">
                    <Button type="button" variant="ghost" @click="showModal = false" class="rounded-xl font-bold order-2 sm:order-1">{{ ui.cancel || 'Cancel' }}</Button>
                    <Button type="submit" class="gap-2 px-8 h-12 lg:h-11 rounded-2xl lg:rounded-xl shadow-lg shadow-primary/20 order-1 sm:order-2" :disabled="form.processing">
                        <Save class="w-4 h-4" />
                        {{ editingProduct ? (ui.update_product || 'Update Product') : (ui.create_product || 'Create Product') }}
                    </Button>
                </div>
            </form>
        </Modal>

        <ConfirmationModal 
            :show="showConfirmModal" 
            :title="ui.delete_product || 'Delete Product'"
            :message="ui.confirm_delete_product || 'Are you sure you want to delete this product? This action cannot be undone.'"
            :impact="impactData"
            type="danger"
            :confirmText="ui.delete || 'Delete'"
            @close="showConfirmModal = false"
            @confirm="deleteProduct"
        />
    </div>
</template>
