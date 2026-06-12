<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm, router, usePage } from '@inertiajs/vue3'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Input from '@/Components/ui/Input.vue'
import Modal from '@/Components/ui/Modal.vue'
import ConfirmationModal from '@/Components/ui/ConfirmationModal.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import { Plus, Pencil, Trash2, Search, Save, Building, Receipt, DollarSign, Filter, X, Tag, Calendar } from '@lucide/vue'
import { ref, watch, computed, onMounted, onUnmounted } from 'vue'

defineOptions({ layout: AppLayout })

const page = usePage()
const props = defineProps({
    operationals: Object,
    branches: Array,
    filters: Object,
    errors: Object,
    auth: Object,
    ziggy: Object,
    system: Object,
    locale: String,
    translations: Object,
})

const ui = computed(() => page.props.translations?.ui || {})
const userBranchId = computed(() => page.props.auth.user.branchid)
const isBranchUser = computed(() => !!userBranchId.value)

const activeFilters = ref({
    search: props.filters?.search || '',
    branchid: isBranchUser.value ? userBranchId.value : (props.filters?.branchid || ''),
    category: props.filters?.category || '',
})

const showModal = ref(false)
const showConfirmModal = ref(false)
const editingOperational = ref(null)

const form = useForm({
    branchid: userBranchId.value || '',
    name: '',
    amount: '',
    category: 'Others',
    created_at: '',
})

function formatPrice(value) {
    if (value === null || value === undefined || value === '') return '—'
    try {
        return new Intl.NumberFormat(page.props.locale === 'id' ? 'id-ID' : 'en-US', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(value)
    } catch (e) {
        return value
    }
}

function formatDate(dateString) {
    if (!dateString) return '—'
    try {
        return new Date(dateString).toLocaleDateString(page.props.locale === 'id' ? 'id-ID' : 'en-US', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        })
    } catch (e) {
        return dateString
    }
}

const applyFilters = () => {
    router.get(route('finance.operationals'), {
        filter: {
            search: activeFilters.value.search,
            branchid: activeFilters.value.branchid,
            category: activeFilters.value.category,
        }
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    })
}

const resetFilters = () => {
    activeFilters.value = {
        search: '',
        branchid: isBranchUser.value ? userBranchId.value : '',
        category: '',
    }
    applyFilters()
}

// Debounce search
let timeout = null
watch(activeFilters, () => {
    clearTimeout(timeout)
    timeout = setTimeout(() => {
        applyFilters()
    }, 300)
}, { deep: true })

const openAddModal = () => {
    editingOperational.value = null
    form.reset()
    form.branchid = userBranchId.value || (props.branches.length > 0 ? props.branches[0].branchid : '')
    form.category = 'Others'
    form.created_at = new Date().toISOString().split('T')[0]
    showModal.value = true
}

const openEditModal = (item) => {
    editingOperational.value = item
    form.branchid = item.branchid
    form.name = item.name
    form.amount = item.amount
    form.category = item.category || 'Others'
    form.created_at = item.created_at ? new Date(item.created_at).toISOString().split('T')[0] : ''
    showModal.value = true
}

const submit = () => {
    if (editingOperational.value) {
        form.put(route('finance.operationals.update', editingOperational.value.operationalid), {
            onSuccess: () => {
                showModal.value = false
                form.reset()
            }
        })
    } else {
        form.post(route('finance.operationals.store'), {
            onSuccess: () => {
                showModal.value = false
                form.reset()
            }
        })
    }
}

const operationalToDelete = ref(null)
const impactData = ref([])
const confirmDelete = (item) => {
    operationalToDelete.value = item
    impactData.value = item.impact || []
    showConfirmModal.value = true
}

const deleteOperational = () => {
    if (operationalToDelete.value) {
        form.delete(route('finance.operationals.destroy', operationalToDelete.value.operationalid), {
            onFinish: () => {
                showConfirmModal.value = false
                operationalToDelete.value = null
                impactData.value = []
            }
        })
    }
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
    <div class="max-w-7xl mx-auto space-y-6 lg:space-y-8">
        <Head :title="ui.operational_costs || 'Operationals Management'" />

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-2 lg:px-0">
            <div>
                <h1 class="text-2xl lg:text-3xl font-black tracking-tight text-primary uppercase">{{ ui.operationals || 'Operationals' }}</h1>
                <p class="text-muted-foreground text-[10px] lg:text-sm font-medium uppercase tracking-widest mt-1">{{ ui.manage_daily_expenses || 'Manage Operational Costs' }}</p>
            </div>
            
            <Button @click="openAddModal" class="gap-2 h-12 w-full sm:w-auto px-6 rounded-2xl shadow-lg shadow-primary/20">
                <Plus class="w-5 h-5" />
                {{ ui.add_operational || 'Add Operational' }}
            </Button>
        </div>

        <!-- Filters Section -->
        <Card class="border-none shadow-sm bg-card/50 mx-2 lg:mx-0">
            <div class="p-4 lg:p-6 space-y-4 lg:space-y-6">
                <div class="flex items-center gap-2 text-primary">
                    <Filter class="w-4 h-4" />
                    <h2 class="text-[10px] lg:text-xs font-black uppercase tracking-widest">{{ ui.filter_operationals || 'Filter Operationals' }}</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <!-- Search Filter -->
                    <div class="space-y-1.5">
                        <label class="text-[9px] lg:text-[10px] font-black uppercase text-muted-foreground ml-1">{{ ui.search_operational || 'Search' }}</label>
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                            <Input 
                                v-model="activeFilters.search" 
                                :placeholder="ui.search_placeholder || 'Search...'" 
                                class="pl-10 h-10 border-transparent bg-background text-[11px] lg:text-xs font-bold focus:ring-1 focus:ring-ring"
                            />
                        </div>
                    </div>

                    <!-- Branch Filter -->
                    <div class="space-y-1.5" v-if="!isBranchUser">
                        <label class="text-[9px] lg:text-[10px] font-black uppercase text-muted-foreground ml-1">{{ ui.by_branch || 'By Branch' }}</label>
                        <select 
                            v-model="activeFilters.branchid"
                            class="w-full h-10 rounded-xl border-transparent bg-background text-[11px] lg:text-xs font-bold focus:ring-1 focus:ring-ring px-3"
                        >
                            <option value="">{{ ui.all_branches || 'All Branches' }}</option>
                            <option v-for="b in branches" :key="b.branchid" :value="b.branchid">{{ b.branchname }}</option>
                        </select>
                    </div>

                    <!-- Category Filter -->
                    <div class="space-y-1.5" :class="{'sm:col-span-2': isBranchUser}">
                        <label class="text-[9px] lg:text-[10px] font-black uppercase text-muted-foreground ml-1">{{ ui.by_category || 'By Category' }}</label>
                        <div class="flex gap-2">
                            <select 
                                v-model="activeFilters.category"
                                class="flex-1 h-10 rounded-xl border-transparent bg-background text-[11px] lg:text-xs font-bold focus:ring-1 focus:ring-ring px-3 capitalize"
                            >
                                <option value="">{{ ui.all_categories || 'All Categories' }}</option>
                                <option value="Electricity">{{ ui.electricity || 'Electricity' }}</option>
                                <option value="Water">{{ ui.water || 'Water' }}</option>
                                <option value="Others">{{ ui.others || 'Others' }}</option>
                            </select>
                            <Button 
                                variant="ghost" 
                                size="icon"
                                @click="resetFilters"
                                class="h-10 w-10 rounded-xl hover:bg-destructive/10 hover:text-destructive shrink-0"
                                :title="ui.reset_filters || 'Reset Filters'"
                            >
                                <X class="w-4 h-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </Card>

        <!-- Data Display (Table for Desktop, Cards for Mobile) -->
        <div class="mx-2 lg:mx-0">
            <!-- Desktop Table -->
            <Card v-if="!isMobileView" class="border-none shadow-sm bg-card/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-secondary/20 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">
                                <th class="px-6 py-4">{{ ui.date || 'Date' }}</th>
                                <th class="px-6 py-4">{{ ui.expense_name || 'Operational Name' }}</th>
                                <th class="px-6 py-4">{{ ui.branch || 'Branch' }}</th>
                                <th class="px-6 py-4">{{ ui.category || 'Category' }}</th>
                                <th class="px-6 py-4">{{ ui.amount || 'Amount' }}</th>
                                <th class="px-6 py-4 text-right">{{ ui.actions || 'Actions' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-secondary/30">
                            <tr v-for="item in operationals?.data || []" :key="item.operationalid" class="group hover:bg-white/50 transition-colors">
                                <td class="px-6 py-4 text-[11px] font-bold text-muted-foreground">
                                    {{ formatDate(item.created_at) }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-black text-primary text-sm tracking-wide uppercase">{{ item.name }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-primary/5 border border-primary/10 text-[10px] font-black text-primary uppercase tracking-tight">
                                        <Building class="w-3.5 h-3.5" />
                                        {{ item.branch?.branchname }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest border"
                                        :class="{
                                            'bg-yellow-100 text-yellow-700 border-yellow-200': item.category === 'Electricity',
                                            'bg-blue-100 text-blue-700 border-blue-200': item.category === 'Water',
                                            'bg-slate-100 text-slate-700 border-slate-200': item.category === 'Others'
                                        }"
                                    >
                                        {{ ui[item.category.toLowerCase()] || item.category }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs font-black text-destructive">
                                    {{ formatPrice(item.amount) }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="openEditModal(item)" class="p-2 hover:bg-primary/10 text-primary rounded-lg transition-colors">
                                            <Pencil class="w-4 h-4" />
                                        </button>
                                        <button @click="confirmDelete(item)" class="p-2 hover:bg-destructive/10 text-destructive rounded-lg transition-colors">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>

            <!-- Mobile Cards -->
            <div v-else class="space-y-4 pb-20">
                <div v-for="item in operationals?.data || []" :key="item.operationalid" class="bg-card border border-secondary/50 rounded-[2rem] p-5 space-y-4 shadow-sm relative overflow-hidden">
                    <div class="flex justify-between items-start">
                        <div class="space-y-1">
                            <span class="text-[10px] font-bold text-muted-foreground uppercase tracking-[0.2em]">{{ formatDate(item.created_at) }}</span>
                            <h3 class="text-sm font-black text-primary uppercase tracking-tight leading-tight pr-10">{{ item.name }}</h3>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-widest border shrink-0"
                            :class="{
                                'bg-yellow-100 text-yellow-700 border-yellow-200': item.category === 'Electricity',
                                'bg-blue-100 text-blue-700 border-blue-200': item.category === 'Water',
                                'bg-slate-100 text-slate-700 border-slate-200': item.category === 'Others'
                            }"
                        >
                            {{ ui[item.category.toLowerCase()] || item.category }}
                        </span>
                    </div>

                    <div class="flex items-end justify-between border-t border-secondary/30 pt-4 mt-2">
                        <div class="space-y-1">
                            <p class="text-[9px] font-black text-muted-foreground uppercase tracking-widest">{{ ui.amount || 'Amount' }}</p>
                            <p class="text-lg font-black text-destructive tracking-tighter">{{ formatPrice(item.amount) }}</p>
                        </div>
                        <div class="flex gap-2">
                            <button @click="openEditModal(item)" class="p-3 bg-primary/10 text-primary rounded-2xl transition-all active:scale-95 border border-primary/20">
                                <Pencil class="w-5 h-5" />
                            </button>
                            <button @click="confirmDelete(item)" class="p-3 bg-destructive/10 text-destructive rounded-2xl transition-all active:scale-95 border border-destructive/20">
                                <Trash2 class="w-5 h-5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="!operationals?.data || operationals.data.length === 0" class="py-20 text-center">
                <div class="inline-flex p-6 rounded-full bg-secondary/30 text-muted-foreground mb-4">
                    <Receipt class="w-12 h-12 opacity-20" />
                </div>
                <h3 class="text-lg font-black text-primary uppercase">{{ ui.no_operational_found || 'No operational records' }}</h3>
                <p class="text-muted-foreground text-xs uppercase tracking-widest">{{ ui.adjust_search_or_add || 'Try adjusting your filters.' }}</p>
            </div>

            <div class="mt-8 lg:p-6 lg:border-t lg:border-secondary/50">
                <Pagination :links="operationals?.links || []" />
            </div>
        </div>

        <!-- CRUD Modal -->
        <Modal :show="showModal" :title="editingOperational ? (ui.edit_operational || 'Edit Operational') : (ui.add_operational || 'Add Operational')" @close="showModal = false">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2 md:col-span-2">
                        <label class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.date || 'Date' }}</label>
                        <div class="relative">
                            <Calendar class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                            <Input v-model="form.created_at" type="date" class="pl-10 h-11 bg-background" required />
                        </div>
                        <div v-if="form.errors.created_at" class="text-xs text-destructive font-bold">{{ form.errors.created_at }}</div>
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.expense_name || 'Operational Name' }}</label>
                        <div class="relative">
                            <Receipt class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                            <Input v-model="form.name" class="pl-10 h-11 bg-background" required placeholder="e.g. Listrik Bulan Mei" />
                        </div>
                        <div v-if="form.errors.name" class="text-xs text-destructive font-bold">{{ form.errors.name }}</div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.branch || 'Branch' }}</label>
                        <div class="relative" :class="{'opacity-60 cursor-not-allowed': isBranchUser}">
                            <Building class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" v-if="isBranchUser" />
                            <select 
                                v-model="form.branchid"
                                class="w-full h-11 rounded-xl border border-input bg-background text-base shadow-sm focus:ring-1 focus:ring-ring md:text-sm"
                                :class="{'pl-10 bg-secondary/50': isBranchUser}"
                                :disabled="isBranchUser"
                                required
                            >
                                <option value="" disabled>{{ ui.select_branch || 'Select Branch' }}</option>
                                <option v-for="branch in branches" :key="branch.branchid" :value="branch.branchid">
                                    {{ branch.branchname }}
                                </option>
                            </select>
                        </div>
                        <div v-if="form.errors.branchid" class="text-xs text-destructive font-bold">{{ form.errors.branchid }}</div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.category || 'Category' }}</label>
                        <div class="relative">
                            <Tag class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                            <select 
                                v-model="form.category"
                                class="w-full pl-10 h-11 rounded-xl border border-input bg-background text-base shadow-sm focus:ring-1 focus:ring-ring md:text-sm capitalize"
                                required
                            >
                                <option value="Electricity">{{ ui.electricity || 'Electricity' }}</option>
                                <option value="Water">{{ ui.water || 'Water' }}</option>
                                <option value="Others">{{ ui.others || 'Others' }}</option>
                            </select>
                        </div>
                        <div v-if="form.errors.category" class="text-xs text-destructive font-bold">{{ form.errors.category }}</div>
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.expense_amount || 'Amount' }} (IDR)</label>
                        <div class="relative">
                            <DollarSign class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                            <Input v-model.number="form.amount" type="number" min="0" class="pl-10 h-11 bg-background" required placeholder="0" />
                        </div>
                        <div v-if="form.errors.amount" class="text-xs text-destructive font-bold">{{ form.errors.amount }}</div>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3 pt-6 border-t border-secondary/50">
                    <Button type="button" variant="ghost" @click="showModal = false" class="rounded-xl font-bold">{{ ui.cancel || 'Cancel' }}</Button>
                    <Button type="submit" class="gap-2 px-8 h-11 rounded-xl shadow-lg shadow-primary/20" :disabled="form.processing">
                        <Save class="w-4 h-4" />
                        {{ editingOperational ? (ui.update_operational || 'Update Operational') : (ui.create_operational || 'Save Operational') }}
                    </Button>
                </div>
            </form>
        </Modal>

        <ConfirmationModal 
            :show="showConfirmModal" 
            :title="ui.delete_operational || 'Delete Operational'"
            :message="ui.confirm_delete_operational || 'Are you sure you want to delete this operational record? This action cannot be undone.'"
            :impact="impactData"
            type="danger"
            :confirmText="ui.delete || 'Delete'"
            @close="showConfirmModal = false"
            @confirm="deleteOperational"
        />
    </div>
</template>
