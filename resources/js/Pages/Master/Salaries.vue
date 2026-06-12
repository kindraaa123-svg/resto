<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm, router, usePage } from '@inertiajs/vue3'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Input from '@/Components/ui/Input.vue'
import Modal from '@/Components/ui/Modal.vue'
import ConfirmationModal from '@/Components/ui/ConfirmationModal.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import { 
    Plus, Pencil, Trash2, Search, Save, User, 
    Building, DollarSign, Calendar, Filter, X, 
    TrendingUp, TrendingDown 
} from '@lucide/vue'
import { ref, watch, computed } from 'vue'

defineOptions({ layout: AppLayout })

const page = usePage()
const props = defineProps({
    payrolls: Object,
    users: Array,
    branches: Array,
    paidUserIds: Array,
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

const showModal = ref(false)
const showConfirmModal = ref(false)
const editingPayroll = ref(null)

const activeFilters = ref({
    userid: props.filters?.filter?.userid || '',
    branchid: isBranchUser.value ? userBranchId.value : (props.filters?.filter?.branchid || ''),
    month: props.filters?.filter?.month || '',
    search: props.filters?.filter?.search || '',
})

const availableUsers = computed(() => {
    const isEdit = !!editingPayroll.value
    const currentUserId = editingPayroll.value?.userid

    return props.users.filter(u => {
        if (isEdit && u.id === currentUserId) return true
        return !props.paidUserIds.includes(u.id)
    })
})

function formatCurrency(value) {
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
            month: 'long',
            year: 'numeric'
        })
    } catch (e) {
        return dateString
    }
}

const form = useForm({
    userid: '',
    branchid: userBranchId.value || '',
    month: new Date().toISOString().split('T')[0].substring(0, 7) + '-01',
    total_salary: 0,
    bonus: 0,
    deduction: 0,
})

const applyFilters = () => {
    router.get(route('finance.salaries'), {
        filter: {
            userid: activeFilters.value.userid,
            branchid: activeFilters.value.branchid,
            month: activeFilters.value.month,
            search: activeFilters.value.search,
        }
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    })
}

const resetFilters = () => {
    activeFilters.value = {
        userid: '',
        branchid: isBranchUser.value ? userBranchId.value : '',
        month: '',
        search: '',
    }
    applyFilters()
}

import { debounce } from 'lodash-es'
watch(activeFilters, debounce(() => {
    applyFilters()
}, 500), { deep: true })

const openAddModal = () => {
    editingPayroll.value = null
    form.reset()
    form.month = activeFilters.value.month ? (activeFilters.value.month + '-01') : (new Date().toISOString().split('T')[0].substring(0, 7) + '-01')
    form.branchid = userBranchId.value || (props.branches.length > 0 ? props.branches[0].branchid : '')
    
    if (availableUsers.value.length > 0) {
        form.userid = availableUsers.value[0].id
        handleUserChange()
    }
    showModal.value = true
}

const openEditModal = (item) => {
    editingPayroll.value = item
    form.userid = item.userid
    form.branchid = item.branchid
    form.month = item.month ? item.month.substring(0, 10) : ''
    form.total_salary = item.total_salary
    form.bonus = item.bonus || 0
    form.deduction = item.deduction || 0
    showModal.value = true
}

const handleUserChange = () => {
    form.total_salary = 0
}

const submit = () => {
    if (editingPayroll.value) {
        form.put(route('master.salaries.update', editingPayroll.value.payrollid), {
            onSuccess: () => {
                showModal.value = false
                form.reset()
            }
        })
    } else {
        form.post(route('master.salaries.store'), {
            onSuccess: () => {
                showModal.value = false
                form.reset()
            }
        })
    }
}

const payrollToDelete = ref(null)
const impactData = ref([])
const confirmData = ref({})

const confirmDelete = (item) => {
    confirmData.value = {
        title: ui.value.delete_payroll || 'Delete Payroll',
        message: ui.value.confirm_delete_payroll || 'Are you sure you want to delete this payroll record? This cannot be undone.',
        type: 'danger',
        impact: item.impact || [],
        action: () => form.delete(route('master.salaries.destroy', item.payrollid), {
            onFinish: () => showConfirmModal.value = false
        })
    }
    showConfirmModal.value = true
}

const deletePayroll = () => {
    if (payrollToDelete.value) {
        form.delete(route('master.salaries.destroy', payrollToDelete.value), {
            onFinish: () => {
                showConfirmModal.value = false
                payrollToDelete.value = null
            }
        })
    }
}

const isMobileView = ref(false)
const checkViewSize = () => {
    isMobileView.value = window.innerWidth < 768
}

import { onMounted, onUnmounted } from 'vue'
onMounted(() => {
    checkViewSize()
    window.addEventListener('resize', checkViewSize)
})
onUnmounted(() => {
    window.removeEventListener('resize', checkViewSize)
})
</script>

<template>
    <Head :title="ui.staff_payroll || 'Salaries Management'" />

    <div class="max-w-7xl mx-auto space-y-6 lg:space-y-8 pb-20">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-2 lg:px-0">
            <div>
                <h1 class="text-2xl lg:text-3xl font-black tracking-tight text-primary uppercase">{{ ui.salaries || 'Salaries' }}</h1>
                <p class="text-muted-foreground text-[10px] lg:text-sm font-medium uppercase tracking-widest mt-1">{{ ui.manage_employee_salaries || 'Manage Employee Payroll & Bonuses' }}</p>
            </div>
            
            <Button @click="openAddModal" class="gap-2 h-12 w-full sm:w-auto px-6 rounded-2xl shadow-lg shadow-primary/20">
                <Plus class="w-5 h-5" />
                {{ ui.add_payroll || 'Add Payroll Entry' }}
            </Button>
        </div>

        <!-- Filters Section -->
        <Card class="border-none shadow-sm bg-card/50 mx-2 lg:mx-0">
            <div class="p-4 lg:p-6 space-y-4 lg:space-y-6">
                <div class="flex items-center gap-2 text-primary">
                    <Filter class="w-4 h-4" />
                    <h2 class="text-[10px] lg:text-xs font-black uppercase tracking-widest">{{ ui.filter_payroll || 'Filter Payroll' }}</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <!-- Search Filter -->
                    <div class="space-y-1.5">
                        <label class="text-[9px] lg:text-[10px] font-black uppercase text-muted-foreground ml-1">{{ ui.search_employee || 'Search Employee' }}</label>
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                            <Input 
                                v-model="activeFilters.search" 
                                :placeholder="ui.search_placeholder || 'Search by name...'" 
                                class="pl-10 h-10 border-transparent bg-background text-[11px] lg:text-xs font-bold focus:ring-1 focus:ring-ring"
                            />
                        </div>
                    </div>

                    <!-- User Filter -->
                    <div class="space-y-1.5">
                        <label class="text-[9px] lg:text-[10px] font-black uppercase text-muted-foreground ml-1">{{ ui.by_employee || 'By Employee' }}</label>
                        <select 
                            v-model="activeFilters.userid"
                            class="w-full h-10 rounded-xl border-transparent bg-background text-[11px] lg:text-xs font-bold focus:ring-1 focus:ring-ring px-3"
                        >
                            <option value="">{{ ui.all_employees || 'All Employees' }}</option>
                            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
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

                    <!-- Month Filter -->
                    <div class="space-y-1.5" :class="{'sm:col-span-2 md:col-span-2': isBranchUser, 'sm:col-span-2': !isBranchUser}">
                        <label class="text-[9px] lg:text-[10px] font-black uppercase text-muted-foreground ml-1">{{ ui.by_month || 'By Month' }}</label>
                        <div class="flex gap-2">
                            <Input 
                                v-model="activeFilters.month" 
                                type="month"
                                class="flex-1 bg-background h-10 border-transparent focus:border-primary/20 text-[11px] lg:text-xs font-bold"
                            />
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

        <div class="mx-2 lg:mx-0">
            <!-- Desktop Table -->
            <Card v-if="!isMobileView" class="border-none shadow-sm bg-card/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-secondary/20 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">
                                <th class="px-6 py-4">{{ ui.employee || 'Employee' }}</th>
                                <th class="px-6 py-4">{{ ui.period || 'Period' }}</th>
                                <th class="px-6 py-4">{{ ui.total_salary || 'Base Salary' }}</th>
                                <th class="px-6 py-4 text-green-600">{{ ui.bonus || 'Bonus' }}</th>
                                <th class="px-6 py-4 text-destructive">{{ ui.deduction || 'Deduction' }}</th>
                                <th class="px-6 py-4">{{ ui.net_salary || 'Total Received' }}</th>
                                <th class="px-6 py-4 text-right">{{ ui.actions || 'Actions' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-secondary/30">
                            <tr v-for="item in payrolls?.data || []" :key="item.payrollid" class="group hover:bg-white/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary font-black uppercase text-[10px]">
                                            {{ item.user?.name ? item.user.name.charAt(0) : '?' }}
                                        </div>
                                        <div class="flex flex-col min-w-0">
                                            <span class="text-sm font-black text-primary uppercase leading-tight truncate">{{ item.user?.name || 'Unknown' }}</span>
                                            <span class="text-[9px] font-bold text-muted-foreground uppercase tracking-tighter">{{ item.user?.branch?.branchname }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-xs font-bold text-primary">
                                    {{ formatDate(item.month) }}
                                </td>
                                <td class="px-6 py-4 text-xs font-bold text-muted-foreground">
                                    {{ formatCurrency(item.total_salary) }}
                                </td>
                                <td class="px-6 py-4 text-xs font-black text-green-600">
                                    + {{ formatCurrency(item.bonus) }}
                                </td>
                                <td class="px-6 py-4 text-xs font-black text-destructive">
                                    - {{ formatCurrency(item.deduction) }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm font-black text-primary">
                                        {{ formatCurrency((item.total_salary || 0) + (item.bonus || 0) - (item.deduction || 0)) }}
                                    </span>
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
            <div v-else class="space-y-4">
                <div v-for="item in payrolls?.data || []" :key="item.payrollid" class="bg-card border border-secondary/50 rounded-[2rem] p-5 shadow-sm relative overflow-hidden">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary font-black uppercase text-xs shrink-0">
                                {{ item.user?.name ? item.user.name.charAt(0) : '?' }}
                            </div>
                            <div class="space-y-0.5">
                                <h3 class="text-sm font-black text-primary uppercase tracking-tight leading-tight">{{ item.user?.name || 'Unknown' }}</h3>
                                <p class="text-[9px] font-bold text-muted-foreground uppercase tracking-widest">{{ item.user?.branch?.branchname }}</p>
                            </div>
                        </div>
                        <div class="px-2.5 py-1 bg-secondary/20 rounded-lg text-[9px] font-black uppercase tracking-widest text-primary border border-secondary/50 shrink-0">
                            {{ formatDate(item.month) }}
                        </div>
                    </div>

                    <div class="bg-secondary/5 rounded-2xl p-4 border border-secondary/30 space-y-3 mb-4">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">{{ ui.total_salary || 'Base Salary' }}</span>
                            <span class="text-xs font-black text-primary">{{ formatCurrency(item.total_salary) }}</span>
                        </div>
                        <div v-if="item.bonus" class="flex justify-between items-center text-green-600">
                            <span class="text-[10px] font-bold uppercase tracking-widest">{{ ui.bonus || 'Bonus' }}</span>
                            <span class="text-xs font-black">+ {{ formatCurrency(item.bonus) }}</span>
                        </div>
                        <div v-if="item.deduction" class="flex justify-between items-center text-destructive">
                            <span class="text-[10px] font-bold uppercase tracking-widest">{{ ui.deduction || 'Deduction' }}</span>
                            <span class="text-xs font-black">- {{ formatCurrency(item.deduction) }}</span>
                        </div>
                        <div class="pt-3 border-t border-secondary/30 flex justify-between items-center">
                            <span class="text-[10px] font-black uppercase tracking-widest text-primary">{{ ui.net_salary || 'Total Received' }}</span>
                            <span class="text-base font-black text-primary">{{ formatCurrency((item.total_salary || 0) + (item.bonus || 0) - (item.deduction || 0)) }}</span>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button @click="openEditModal(item)" class="flex-1 p-3 bg-primary/10 text-primary rounded-2xl transition-all active:scale-95 border border-primary/20 flex items-center justify-center gap-2">
                            <Pencil class="w-4 h-4" />
                            <span class="text-[10px] font-black uppercase tracking-widest">Edit</span>
                        </button>
                        <button @click="confirmDelete(item)" class="p-3 bg-destructive/10 text-destructive rounded-2xl transition-all active:scale-95 border border-destructive/20 flex items-center justify-center">
                            <Trash2 class="w-5 h-5" />
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="!payrolls?.data || payrolls.data.length === 0" class="py-20 text-center">
                <div class="inline-flex p-6 rounded-full bg-secondary/30 text-muted-foreground mb-4">
                    <DollarSign class="w-12 h-12 opacity-20" />
                </div>
                <h3 class="text-lg font-black text-primary uppercase">{{ ui.no_payroll_found || 'No payroll records' }}</h3>
                <p class="text-muted-foreground text-xs uppercase tracking-widest">{{ ui.adjust_search_or_add || 'Try adjusting your filters.' }}</p>
            </div>

            <div class="mt-8 lg:p-6 lg:border-t lg:border-secondary/50">
                <Pagination :links="payrolls?.links || []" />
            </div>
        </div>

        <!-- CRUD Modal -->
        <Modal :show="showModal" :title="editingPayroll ? (ui.edit_payroll || 'Edit Payroll Record') : (ui.add_payroll || 'Add Payroll Entry')" @close="showModal = false">
            <form @submit.prevent="submit" class="space-y-4 lg:space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6">
                    <div class="space-y-1.5 lg:space-y-2">
                        <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.employee || 'Employee' }}</label>
                        <div class="relative">
                            <User class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                            <select 
                                v-model="form.userid"
                                @change="handleUserChange"
                                class="w-full pl-10 h-10 lg:h-11 rounded-xl border border-input bg-background text-[13px] lg:text-sm shadow-sm focus:ring-1 focus:ring-ring"
                                required
                                :disabled="editingPayroll"
                            >
                                <option value="" disabled>{{ ui.select_employee || 'Select Employee' }}</option>
                                <option v-for="u in availableUsers" :key="u.id" :value="u.id">{{ u.name }}</option>
                            </select>
                        </div>
                        <div v-if="form.errors.userid" class="text-xs text-destructive font-bold">{{ form.errors.userid }}</div>
                    </div>

                    <div class="space-y-1.5 lg:space-y-2">
                        <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.branch || 'Branch' }}</label>
                        <div class="relative" :class="{'opacity-60 cursor-not-allowed': isBranchUser}">
                            <Building class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" v-if="isBranchUser" />
                            <select 
                                v-model="form.branchid"
                                class="w-full h-10 lg:h-11 rounded-xl border border-input bg-background text-[13px] lg:text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
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

                    <div class="space-y-1.5 lg:space-y-2">
                        <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.payroll_month || 'Month Period' }}</label>
                        <div class="relative">
                            <Calendar class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                            <Input 
                                v-model="form.month" 
                                type="date"
                                class="pl-10 h-10 lg:h-11 bg-background" 
                                required
                            />
                        </div>
                        <div v-if="form.errors.month" class="text-xs text-destructive font-bold">{{ form.errors.month }}</div>
                    </div>

                    <div class="space-y-1.5 lg:space-y-2">
                        <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.salary_amount || 'Base Salary' }}</label>
                        <div class="relative">
                            <DollarSign class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                            <Input 
                                v-model.number="form.total_salary" 
                                type="number" 
                                class="pl-10 h-10 lg:h-11 bg-background" 
                                required
                            />
                        </div>
                        <div v-if="form.errors.total_salary" class="text-xs text-destructive font-bold">{{ form.errors.total_salary }}</div>
                    </div>

                    <div class="space-y-1.5 lg:space-y-2">
                        <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-green-600 ml-1">{{ ui.bonus_amount || 'Bonus' }} (+)</label>
                        <div class="relative">
                            <TrendingUp class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-green-600" />
                            <Input 
                                v-model.number="form.bonus" 
                                type="number" 
                                class="pl-10 h-10 lg:h-11 bg-background border-green-200 focus:border-green-400" 
                            />
                        </div>
                        <div v-if="form.errors.bonus" class="text-xs text-destructive font-bold">{{ form.errors.bonus }}</div>
                    </div>

                    <div class="space-y-1.5 lg:space-y-2">
                        <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-destructive ml-1">{{ ui.deduction_amount || 'Deduction' }} (-)</label>
                        <div class="relative">
                            <TrendingDown class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-destructive" />
                            <Input 
                                v-model.number="form.deduction" 
                                type="number" 
                                class="pl-10 h-10 lg:h-11 bg-background border-destructive/20 focus:border-destructive/40" 
                            />
                        </div>
                        <div v-if="form.errors.deduction" class="text-xs text-destructive font-bold">{{ form.errors.deduction }}</div>
                    </div>
                </div>

                <div class="p-3 lg:p-4 bg-primary/5 rounded-2xl border border-primary/10">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] lg:text-xs font-black uppercase text-primary tracking-widest">{{ ui.estimated_pay || 'Estimated Take Home Pay' }}</span>
                        <span class="text-base lg:text-lg font-black text-primary">
                            {{ formatCurrency(Number(form.total_salary || 0) + Number(form.bonus || 0) - Number(form.deduction || 0)) }}
                        </span>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-end gap-3 pt-6 border-t border-secondary/50">
                    <Button type="button" variant="ghost" @click="showModal = false" class="rounded-xl font-bold order-2 sm:order-1">{{ ui.cancel || 'Cancel' }}</Button>
                    <Button type="submit" class="gap-2 px-8 h-12 lg:h-11 rounded-2xl lg:rounded-xl shadow-lg shadow-primary/20 order-1 sm:order-2" :disabled="form.processing">
                        <component :is="editingPayroll ? Save : DollarSign" class="w-4 h-4" />
                        {{ editingPayroll ? (ui.update_payroll || 'Update Record') : (ui.create_payroll || 'Process Payroll') }}
                    </Button>
                </div>
            </form>
        </Modal>

        <ConfirmationModal 
            :show="showConfirmModal" 
            :title="confirmData.title"
            :message="confirmData.message"
            :type="confirmData.type"
            :impact="confirmData.impact"
            @close="showConfirmModal = false"
            @confirm="confirmData.action"
        />
    </div>
</template>
