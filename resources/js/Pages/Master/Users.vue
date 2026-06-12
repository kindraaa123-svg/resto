<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm, usePage, router } from '@inertiajs/vue3'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Input from '@/Components/ui/Input.vue'
import Modal from '@/Components/ui/Modal.vue'
import ConfirmationModal from '@/Components/ui/ConfirmationModal.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import { 
    Plus, Pencil, Trash2, Search, User, Mail, Phone, 
    Save, ShieldCheck, RotateCcw, Building 
} from '@lucide/vue'
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'

defineOptions({ layout: AppLayout })

const page = usePage()
const props = defineProps({
    users: Object,
    roles: Array,
    branches: Array,
    errors: Object,
    auth: Object,
    ziggy: Object,
    system: Object,
    locale: String,
    translations: Object,
    filters: Object,
})

const ui = computed(() => page.props.translations?.ui || {})
const userBranchId = computed(() => page.props.auth.user.branchid)
const isBranchUser = computed(() => !!userBranchId.value)

const searchQuery = ref(props.filters?.filter?.search || '')
let searchTimeout = null

watch(searchQuery, (value) => {
    if (searchTimeout) clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        router.get(
            route('master.users.index'),
            { filter: { search: value } },
            { preserveState: true, preserveScroll: true, replace: true }
        )
    }, 500)
})

const showModal = ref(false)
const showConfirmModal = ref(false)
const confirmData = ref({
    title: '',
    message: '',
    type: 'warning',
    action: null
})
const editingUser = ref(null)

const filteredUsers = computed(() => {
    return props.users?.data || []
})

const form = useForm({
    name: '',
    email: '',
    phonenumber: '',
    roleid: '',
    branchid: userBranchId.value || '',
})

const openAddModal = () => {
    editingUser.value = null
    form.reset()
    if (props.roles.length > 0) {
        form.roleid = props.roles.find(r => r.id !== 1)?.id || props.roles[0].id
    }
    form.branchid = userBranchId.value || (props.branches.length > 0 ? props.branches[0].branchid : '')
    showModal.value = true
}

const openEditModal = (user) => {
    editingUser.value = user
    form.name = user.name
    form.email = user.email
    form.phonenumber = user.phonenumber || ''
    form.roleid = user.roleid || ''
    form.branchid = user.branchid || ''
    showModal.value = true
}

const submit = () => {
    if (editingUser.value) {
        form.put(route('master.users.update', editingUser.value.id), {
            onSuccess: () => {
                showModal.value = false
                form.reset()
            }
        })
    } else {
        form.post(route('master.users.store'), {
            onSuccess: () => {
                showModal.value = false
                form.reset()
            }
        })
    }
}

const confirmDelete = (user) => {
    confirmData.value = {
        title: ui.value.delete_user || 'Delete User',
        message: ui.value.confirm_delete_user || 'Are you sure you want to delete this user? This action cannot be undone.',
        type: 'danger',
        impact: user.impact || [],
        action: () => form.delete(route('master.users.destroy', user.id), {
            onFinish: () => showConfirmModal.value = false
        })
    }
    showConfirmModal.value = true
}

const confirmReset = (id) => {
    confirmData.value = {
        title: ui.value.reset_password || 'Reset Password',
        message: (ui.value.confirm_reset_password || 'Are you sure you want to reset this user\'s password to :password?').replace(':password', '12345'),
        type: 'warning',
        impact: [],
        action: () => form.post(route('master.users.reset-password', id), {
            onFinish: () => showConfirmModal.value = false
        })
    }
    showConfirmModal.value = true
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
    <div class="max-w-7xl mx-auto space-y-6 lg:space-y-8 pb-20">
        <Head :title="ui.user_management || 'User Management'" />

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-2 lg:px-0">
            <div>
                <h1 class="text-2xl lg:text-3xl font-black tracking-tight text-primary uppercase">{{ ui.users || 'Users' }}</h1>
                <p class="text-muted-foreground text-[10px] lg:text-sm font-medium uppercase tracking-widest mt-1">{{ ui.manage_staff_accounts || 'Manage Staff & Admin Accounts' }}</p>
            </div>
            
            <Button @click="openAddModal" class="gap-2 h-12 w-full sm:w-auto px-6 rounded-2xl shadow-lg shadow-primary/20">
                <Plus class="w-5 h-5" />
                {{ ui.add_user || 'Add New User' }}
            </Button>
        </div>

        <Card class="border-none shadow-sm bg-card/50 overflow-hidden mx-2 lg:mx-0">
            <!-- Table Controls -->
            <div class="p-4 lg:p-6 border-b border-secondary/50 flex flex-col sm:flex-row items-center gap-4">
                <div class="relative w-full sm:max-w-xs">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                    <Input 
                        v-model="searchQuery" 
                        :placeholder="ui.search_users || 'Search users...'" 
                        class="pl-10 bg-background h-11 border-transparent focus:border-primary/20 text-[13px] lg:text-sm"
                    />
                </div>
            </div>

            <!-- Desktop Table -->
            <div v-if="!isMobileView" class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-secondary/20 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">
                            <th class="px-6 py-4">{{ ui.employee || 'User' }}</th>
                            <th class="px-6 py-4">{{ ui.contact || 'Contact' }}</th>
                            <th class="px-6 py-4">{{ ui.role || 'Role' }}</th>
                            <th class="px-6 py-4 text-right">{{ ui.actions || 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-secondary/30">
                        <tr v-for="user in filteredUsers" :key="user.id" class="group hover:bg-white/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary font-black uppercase text-xs">
                                        {{ user.name.charAt(0) }}
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <span class="font-black text-primary uppercase tracking-tight text-sm truncate max-w-[200px]">
                                            {{ user.name }}
                                        </span>
                                        <span class="text-xs text-muted-foreground truncate max-w-[200px]">{{ user.email }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-muted-foreground">{{ user.phonenumber || '—' }}</span>
                                    <div v-if="user.branch_name && user.branch_name !== 'N/A'" class="flex items-center gap-1 text-[9px] font-black text-primary/60 uppercase tracking-tighter mt-0.5">
                                        <Building class="w-2.5 h-2.5" />
                                        {{ user.branch_name }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div v-if="user.role && user.role !== 'N/A'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[9px] font-black bg-primary/10 text-primary uppercase tracking-widest border border-primary/20">
                                    {{ user.role }}
                                </div>
                                <span v-else class="text-[9px] font-black text-muted-foreground uppercase tracking-widest bg-secondary/50 px-2 py-0.5 rounded-full border border-secondary">{{ ui.no_role || 'No Role' }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button 
                                        @click="confirmReset(user.id)"
                                        :title="ui.reset_password || 'Reset Password'"
                                        class="p-2 hover:bg-amber-100 text-amber-600 rounded-lg transition-colors"
                                    >
                                        <RotateCcw class="w-4 h-4" />
                                    </button>
                                    <button 
                                        @click="openEditModal(user)"
                                        :title="ui.edit_user || 'Edit User'"
                                        class="p-2 hover:bg-primary/10 text-primary rounded-lg transition-colors"
                                    >
                                        <Pencil class="w-4 h-4" />
                                    </button>
                                    <button 
                                        v-if="user.id !== $page.props.auth.user.id"
                                        @click="confirmDelete(user)"
                                        :title="ui.delete_user || 'Delete User'"
                                        class="p-2 hover:bg-destructive/10 text-destructive rounded-lg transition-colors"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="filteredUsers.length === 0">
                            <td colspan="4" class="px-6 py-12 text-center text-muted-foreground italic text-sm">
                                {{ ui.no_users_found || 'No users found.' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div v-else class="bg-secondary/5">
                <div class="divide-y divide-secondary/30 p-2 space-y-2">
                    <div v-for="user in filteredUsers" :key="user.id" class="bg-white border border-secondary/50 rounded-3xl p-4 shadow-sm relative overflow-hidden flex flex-col gap-4">
                        <div class="flex items-start gap-3">
                            <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary font-black uppercase text-sm shrink-0">
                                {{ user.name.charAt(0) }}
                            </div>
                            <div class="space-y-1 w-full min-w-0">
                                <div class="flex justify-between items-start">
                                    <h3 class="text-sm font-black text-primary uppercase tracking-tight leading-tight truncate">{{ user.name }}</h3>
                                    <div v-if="user.role && user.role !== 'N/A'" class="inline-flex items-center px-2 py-0.5 rounded-full text-[8px] font-black bg-primary/10 text-primary uppercase tracking-widest border border-primary/20 shrink-0 ml-2">
                                        {{ user.role }}
                                    </div>
                                </div>
                                <span class="text-[10px] font-bold text-muted-foreground block truncate">{{ user.email }}</span>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <div class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg bg-secondary/20 border border-secondary/50 text-[10px] font-bold text-muted-foreground">
                                <Phone class="w-3.5 h-3.5" />
                                {{ user.phonenumber || '—' }}
                            </div>
                            <div v-if="user.branch_name && user.branch_name !== 'N/A'" class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg bg-primary/5 border border-primary/10 text-[10px] font-bold text-primary">
                                <Building class="w-3.5 h-3.5" />
                                {{ user.branch_name }}
                            </div>
                        </div>

                        <div class="flex gap-2 pt-2 border-t border-secondary/30">
                            <button @click="confirmReset(user.id)" class="p-3 bg-amber-50 text-amber-600 rounded-xl transition-all active:scale-95 border border-amber-200 flex items-center justify-center">
                                <RotateCcw class="w-4 h-4" />
                            </button>
                            <button @click="openEditModal(user)" class="flex-1 p-3 bg-primary/10 text-primary rounded-xl transition-all active:scale-95 border border-primary/20 flex items-center justify-center gap-2">
                                <Pencil class="w-4 h-4" />
                                <span class="text-[10px] font-black uppercase tracking-widest">Edit</span>
                            </button>
                            <button v-if="user.id !== $page.props.auth.user.id" @click="confirmDelete(user)" class="p-3 bg-destructive/10 text-destructive rounded-xl transition-all active:scale-95 border border-destructive/20 flex items-center justify-center">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div v-if="filteredUsers.length === 0" class="py-12 text-center">
                        <div class="inline-flex p-4 rounded-full bg-secondary/30 text-muted-foreground mb-3">
                            <User class="w-8 h-8 opacity-20" />
                        </div>
                        <h3 class="text-sm font-black text-primary uppercase">{{ ui.no_users_found || 'No users found.' }}</h3>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div class="p-4 lg:p-6 border-t border-secondary/50">
                <Pagination :links="users.links" />
            </div>
        </Card>

        <!-- CRUD Modal -->
        <Modal :show="showModal" :title="editingUser ? (ui.edit_user || 'Edit User') : (ui.add_new_user || 'Add New User')" @close="showModal = false">
            <form @submit.prevent="submit" class="space-y-4 lg:space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6">
                    <div class="space-y-1.5 lg:space-y-2">
                        <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.full_name || 'Full Name' }}</label>
                        <div class="relative">
                            <User class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                            <Input v-model="form.name" class="pl-10 h-10 lg:h-11 bg-background text-[13px] lg:text-sm" required />
                        </div>
                        <div v-if="form.errors.name" class="text-xs text-destructive font-bold">{{ form.errors.name }}</div>
                    </div>

                    <div class="space-y-1.5 lg:space-y-2">
                        <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.email_address || 'Email Address' }}</label>
                        <div class="relative">
                            <Mail class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                            <Input v-model="form.email" type="email" class="pl-10 h-10 lg:h-11 bg-background text-[13px] lg:text-sm" required :disabled="editingUser" />
                        </div>
                        <div v-if="form.errors.email" class="text-xs text-destructive font-bold">{{ form.errors.email }}</div>
                    </div>

                    <div class="space-y-1.5 lg:space-y-2">
                        <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.phone_number || 'Phone Number' }}</label>
                        <div class="relative">
                            <Phone class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                            <Input v-model="form.phonenumber" type="tel" class="pl-10 h-10 lg:h-11 bg-background text-[13px] lg:text-sm" />
                        </div>
                        <div v-if="form.errors.phonenumber" class="text-xs text-destructive font-bold">{{ form.errors.phonenumber }}</div>
                    </div>

                    <div class="space-y-1.5 lg:space-y-2">
                        <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.role || 'Role' }}</label>
                        <div class="relative">
                            <ShieldCheck class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                            <select 
                                v-model="form.roleid"
                                class="w-full pl-10 h-10 lg:h-11 rounded-xl border border-input bg-background text-[13px] lg:text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                :class="{'opacity-60 cursor-not-allowed bg-secondary/50': editingUser && (editingUser.role === 'Superadmin' || editingUser.roleid === 1)}"
                                :disabled="editingUser && (editingUser.role === 'Superadmin' || editingUser.roleid === 1)"
                                required
                            >
                                <option value="" disabled>{{ ui.select_role || 'Select Role' }}</option>
                                <option v-if="editingUser && (editingUser.role === 'Superadmin' || editingUser.roleid === 1)" :value="editingUser.roleid">
                                    {{ editingUser.role }}
                                </option>
                                <option v-for="role in roles" :key="role.id" :value="role.id" :disabled="role.id === 1 && $page.props.auth.user.roleid !== 1">
                                    {{ role.name }}
                                </option>
                            </select>
                        </div>
                        <div v-if="form.errors.roleid" class="text-xs text-destructive font-bold">{{ form.errors.roleid }}</div>
                    </div>

                    <div class="space-y-1.5 lg:space-y-2 col-span-full">
                        <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.branch_assignment || 'Branch Assignment' }}</label>
                        <div class="relative" :class="{'opacity-60 cursor-not-allowed': isBranchUser || form.roleid == 1}">
                            <Building class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" v-if="isBranchUser || form.roleid == 1" />
                            <select 
                                v-model="form.branchid"
                                class="w-full h-10 lg:h-11 rounded-xl border border-input bg-background text-[13px] lg:text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                :class="{'pl-10 bg-secondary/50': isBranchUser || form.roleid == 1, 'px-3': !isBranchUser && form.roleid != 1}"
                                :disabled="isBranchUser || form.roleid == 1"
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
                </div>

                <div v-if="!editingUser" class="p-3 lg:p-4 bg-amber-50 border border-amber-100 rounded-xl">
                    <p class="text-[9px] lg:text-[10px] font-bold text-amber-800 uppercase tracking-widest leading-relaxed">
                        {{ ui.default_password_note || '* The default password for new users is' }} <span class="font-black text-amber-900 bg-amber-200/50 px-2 py-0.5 rounded">password</span>
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row justify-end gap-3 pt-6 border-t border-secondary/50">
                    <Button type="button" variant="ghost" @click="showModal = false" class="rounded-xl font-bold order-2 sm:order-1">{{ ui.cancel || 'Cancel' }}</Button>
                    <Button type="submit" class="gap-2 px-8 h-12 lg:h-11 rounded-2xl lg:rounded-xl shadow-lg shadow-primary/20 order-1 sm:order-2" :disabled="form.processing">
                        <Save class="w-4 h-4" />
                        {{ editingUser ? (ui.update_user || 'Update User') : (ui.create_user || 'Create User') }}
                    </Button>
                </div>
            </form>
        </Modal>

        <ConfirmationModal 
            :show="showConfirmModal" 
            :title="confirmData.title"
            :message="confirmData.message"
            :impact="confirmData.impact"
            :type="confirmData.type"
            :confirmText="confirmData.type === 'danger' ? (ui.delete || 'Delete') : (ui.reset || 'Reset')"
            @close="showConfirmModal = false"
            @confirm="confirmData.action"
        />
    </div>
</template>