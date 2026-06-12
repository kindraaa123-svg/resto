<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Input from '@/Components/ui/Input.vue'
import Modal from '@/Components/ui/Modal.vue'
import ConfirmationModal from '@/Components/ui/ConfirmationModal.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import { Plus, Pencil, Trash2, Search, Database, Save, Building, Barcode, CheckCircle2, Circle, QrCode, Printer } from '@lucide/vue'
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash-es'

defineOptions({ layout: AppLayout })

const page = usePage()
const props = defineProps({
    tables: Object,
    filters: Object,
    branches: Array,
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

const searchQuery = ref(props.filters?.filter?.search || '')
const showModal = ref(false)
const showConfirmModal = ref(false)
const showQRModal = ref(false)
const selectedTable = ref(null)
const tableToDelete = ref(null)
const impactData = ref([])
const editingTable = ref(null)

watch(searchQuery, debounce((value) => {
    router.get(route('master.tables.index'), { filter: { search: value } }, {
        preserveState: true,
        replace: true
    })
}, 500))

const form = useForm({
    name: '',
    barcode: '',
    branchid: userBranchId.value || '',
})

const openAddModal = () => {
    editingTable.value = null
    form.reset()
    form.branchid = userBranchId.value || (props.branches.length > 0 ? props.branches[0].branchid : '')
    showModal.value = true
}
const openEditModal = (table) => {
    editingTable.value = table
    form.name = table.name
    form.barcode = table.barcode
    form.branchid = table.branchid
    showModal.value = true
}

// Removed branch floor dependency watch

const openQRModal = (table) => {
    selectedTable.value = table
    showQRModal.value = true
}

const printQR = () => {
    const printContent = document.getElementById('qr-to-print').innerHTML;
    const windowUrl = 'about:blank';
    const uniqueName = new Date();
    const windowName = 'Print' + uniqueName.getTime();
    const printWindow = window.open(windowUrl, windowName, 'left=50000,top=50000,width=0,height=0');
    
    printWindow.document.write(`
        <html>
            <head>
                <title>Print QR - ${selectedTable.value.name}</title>
                <style>
                    body { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; font-family: sans-serif; }
                    .container { text-align: center; border: 2px solid #000; padding: 40px; border-radius: 20px; }
                    h1 { margin-top: 20px; font-size: 24px; text-transform: uppercase; }
                    p { font-size: 14px; color: #666; }
                </style>
            </head>
            <body>
                <div class="container">
                    ${printContent}
                    <h1>${selectedTable.value.name}</h1>
                    <p>${selectedTable.value.barcode}</p>
                </div>
                <script>
                    window.onload = function() {
                        setTimeout(() => {
                            window.print();
                        }, 500);
                    };
                <\/script>
            </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.focus();
}

const submit = () => {
    if (editingTable.value) {
        form.put(route('master.tables.update', editingTable.value.tableseatid), {
            onSuccess: () => {
                showModal.value = false
                form.reset()
            }
        })
    } else {
        form.post(route('master.tables.store'), {
            onSuccess: () => {
                showModal.value = false
                form.reset()
            }
        })
    }
}

const confirmDelete = (table) => {
    tableToDelete.value = table
    impactData.value = table.impact || []
    showConfirmModal.value = true
}

const deleteTable = () => {
    if (tableToDelete.value) {
        form.delete(route('master.tables.destroy', tableToDelete.value.tableseatid), {
            onFinish: () => {
                showConfirmModal.value = false
                tableToDelete.value = null
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
    <div class="max-w-7xl mx-auto space-y-6 lg:space-y-8 pb-20">
        <Head :title="ui.tables_seats || 'Table Management'" />

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-2 lg:px-0">
            <div>
                <h1 class="text-2xl lg:text-3xl font-black tracking-tight text-primary uppercase">{{ ui.tables_seats || 'Tables & Seats' }}</h1>
                <p class="text-muted-foreground text-[10px] lg:text-sm font-medium uppercase tracking-widest mt-1">{{ ui.manage_dine_in_locations || 'Manage Dine-in Locations' }}</p>
            </div>
            
            <Button @click="openAddModal" class="gap-2 h-12 w-full sm:w-auto px-6 rounded-2xl shadow-lg shadow-primary/20">
                <Plus class="w-5 h-5" />
                {{ ui.add_new_table || 'Add New Table' }}
            </Button>
        </div>

        <Card class="border-none shadow-sm bg-card/50 overflow-hidden mx-2 lg:mx-0">
            <div class="p-4 lg:p-6 border-b border-secondary/50 flex flex-col sm:flex-row items-center gap-4">
                <div class="relative w-full sm:max-w-xs">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                    <Input 
                        v-model="searchQuery" 
                        :placeholder="ui.search_tables || 'Search tables or barcodes...'" 
                        class="pl-10 bg-background h-11 border-transparent focus:border-primary/20 text-[13px] lg:text-sm"
                    />
                </div>
            </div>

            <!-- Desktop Table -->
            <div v-if="!isMobileView" class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-secondary/20 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">
                            <th class="px-6 py-4">{{ ui.table_name || 'Table Name' }}</th>
                            <th class="px-6 py-4">{{ ui.barcode || 'Barcode' }}</th>
                            <th class="px-6 py-4">{{ ui.branch || 'Branch' }}</th>
                            <th class="px-6 py-4 text-right">{{ ui.actions || 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-secondary/30">
                        <tr v-for="table in tables?.data || []" :key="table.tableseatid" class="group hover:bg-white/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary font-black uppercase text-xs">
                                        {{ table.name.charAt(0) }}
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-black text-primary uppercase tracking-tight text-sm">
                                            {{ table.name }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2 text-xs font-bold text-muted-foreground">
                                    <Barcode class="w-4 h-4 opacity-50" />
                                    {{ table.barcode }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <Building class="w-3 h-3 text-muted-foreground" />
                                    <span class="text-xs font-bold text-primary uppercase">{{ table.branch?.branchname || '—' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="openQRModal(table)" class="p-2 hover:bg-primary/10 text-primary rounded-lg transition-colors" :title="ui.table_qr || 'Show QR Code'">
                                        <QrCode class="w-4 h-4" />
                                    </button>
                                    <button @click="openEditModal(table)" class="p-2 hover:bg-primary/10 text-primary rounded-lg transition-colors" :title="ui.edit_table || 'Edit Table'">
                                        <Pencil class="w-4 h-4" />
                                    </button>
                                    <button @click="confirmDelete(table)" class="p-2 hover:bg-destructive/10 text-destructive rounded-lg transition-colors" :title="ui.delete_table || 'Delete Table'">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!tables?.data || tables.data.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-muted-foreground italic text-sm">
                                {{ ui.no_tables_found || 'No tables found.' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div v-else class="bg-secondary/5">
                <div class="divide-y divide-secondary/30 p-2 space-y-2">
                    <div v-for="table in tables?.data || []" :key="table.tableseatid" class="bg-white border border-secondary/50 rounded-3xl p-4 shadow-sm relative overflow-hidden flex flex-col gap-4">
                        <div class="flex items-start gap-3">
                            <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary font-black shrink-0">
                                <Database class="w-6 h-6" />
                            </div>
                            <div class="space-y-1 w-full">
                                <div class="flex justify-between items-start">
                                    <h3 class="text-sm font-black text-primary uppercase tracking-tight leading-tight">{{ table.name }}</h3>
                                </div>
                                <span class="text-[9px] font-bold text-muted-foreground font-mono bg-secondary/30 px-1.5 rounded w-fit block">{{ table.barcode }}</span>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <div class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg bg-primary/5 border border-primary/10 text-[10px] font-bold text-primary">
                                <Building class="w-3.5 h-3.5" />
                                {{ table.branch?.branchname }}
                            </div>
                        </div>

                        <div class="flex gap-2 pt-2 border-t border-secondary/30">
                            <button @click="openQRModal(table)" class="p-3 bg-secondary/50 text-primary rounded-xl transition-all active:scale-95 border border-secondary/20 flex items-center justify-center">
                                <QrCode class="w-4 h-4" />
                            </button>
                            <button @click="openEditModal(table)" class="flex-1 p-3 bg-primary/10 text-primary rounded-xl transition-all active:scale-95 border border-primary/20 flex items-center justify-center gap-2">
                                <Pencil class="w-4 h-4" />
                                <span class="text-[10px] font-black uppercase tracking-widest">Edit</span>
                            </button>
                            <button @click="confirmDelete(table)" class="p-3 bg-destructive/10 text-destructive rounded-xl transition-all active:scale-95 border border-destructive/20 flex items-center justify-center">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div v-if="!tables?.data || tables.data.length === 0" class="py-12 text-center">
                        <div class="inline-flex p-4 rounded-full bg-secondary/30 text-muted-foreground mb-3">
                            <Database class="w-8 h-8 opacity-20" />
                        </div>
                        <h3 class="text-sm font-black text-primary uppercase">{{ ui.no_tables_found || 'No tables found.' }}</h3>
                    </div>
                </div>
            </div>

            <div class="p-4 lg:p-6 border-t border-secondary/50">
                <Pagination :links="tables.links" />
            </div>
        </Card>

        <!-- CRUD Modal -->
        <Modal :show="showModal" :title="editingTable ? (ui.edit_table || 'Edit Table') : (ui.add_new_table || 'Add New Table')" @close="showModal = false">
            <form @submit.prevent="submit" class="space-y-4 lg:space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6">
                    <div class="space-y-1.5 lg:space-y-2">
                        <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.table_name || 'Table Name' }}</label>
                        <div class="relative">
                            <Database class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                            <Input v-model="form.name" required class="pl-10 h-10 lg:h-11 bg-background text-[13px] lg:text-sm" placeholder="e.g. Table 01, VIP-A" />
                        </div>
                        <div v-if="form.errors.name" class="text-xs text-destructive font-bold">{{ form.errors.name }}</div>
                    </div>

                    <div v-if="!editingTable" class="space-y-1.5 lg:space-y-2">
                        <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.barcode_identifier || 'Barcode / Identifier' }}</label>
                        <div class="relative">
                            <Barcode class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                            <Input v-model="form.barcode" class="pl-10 h-10 lg:h-11 bg-background text-[13px] lg:text-sm" placeholder="Leave empty to auto-generate" />
                        </div>
                        <div v-if="form.errors.barcode" class="text-xs text-destructive font-bold">{{ form.errors.barcode }}</div>
                    </div>

                    <div class="space-y-1.5 lg:space-y-2">
                        <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.branch || 'Branch' }}</label>
                        <div class="relative" :class="{'opacity-60 cursor-not-allowed': isBranchUser}">
                            <Building class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" v-if="isBranchUser" />
                            <select 
                                v-model="form.branchid"
                                class="w-full h-10 lg:h-11 rounded-xl border border-input bg-background text-[13px] lg:text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                :class="{'pl-10 bg-secondary/50': isBranchUser, 'px-3': !isBranchUser}"
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
                </div>

                <div class="flex flex-col sm:flex-row justify-end gap-3 pt-6 border-t border-secondary/50">
                    <Button type="button" variant="ghost" @click="showModal = false" class="rounded-xl font-bold order-2 sm:order-1">{{ ui.cancel || 'Cancel' }}</Button>
                    <Button type="submit" class="gap-2 px-8 h-12 lg:h-11 rounded-2xl lg:rounded-xl shadow-lg shadow-primary/20 order-1 sm:order-2" :disabled="form.processing">
                        <Save class="w-4 h-4" />
                        {{ editingTable ? (ui.update_table || 'Update Table') : (ui.create_table || 'Create Table') }}
                    </Button>
                </div>
            </form>
        </Modal>

        <!-- QR Code Modal -->
        <Modal :show="showQRModal" :title="(ui.table_qr || 'Table QR') + ': ' + selectedTable?.name" @close="showQRModal = false" maxWidth="md">
            <div class="flex flex-col items-center justify-center p-4 lg:p-6 space-y-4">
                <div id="qr-to-print" class="p-4 bg-white rounded-2xl border-2 border-secondary shadow-inner w-full max-w-[200px] lg:max-w-[250px] aspect-square flex items-center justify-center overflow-hidden [&>svg]:w-full [&>svg]:h-full" v-html="selectedTable?.qr_code">
                </div>
                
                <div class="text-center space-y-0.5">
                    <h3 class="text-xl font-black text-primary uppercase tracking-tighter">{{ selectedTable?.name }}</h3>
                    <p class="text-[10px] font-bold text-muted-foreground tracking-widest">{{ selectedTable?.barcode }}</p>
                </div>

                <div class="flex w-full gap-3 pt-3 border-t border-secondary/50">
                    <Button @click="printQR" class="flex-1 gap-2 h-11 rounded-xl uppercase font-black tracking-widest text-[10px] shadow-lg shadow-primary/20">
                        <Printer class="w-3.5 h-3.5" />
                        {{ ui.print_label || 'Print Label' }}
                    </Button>
                    <Button variant="outline" @click="showQRModal = false" class="flex-1 h-11 rounded-xl uppercase font-black tracking-widest text-[10px]">
                        {{ ui.cancel || 'Close' }}
                    </Button>
                </div>
            </div>
        </Modal>

        <ConfirmationModal 
            :show="showConfirmModal" 
            :title="ui.delete_table || 'Delete Table'"
            :message="ui.confirm_delete_table || 'Are you sure you want to delete this table? This will remove it from the system.'"
            :impact="impactData"
            type="danger"
            :confirmText="ui.delete || 'Delete'"
            @close="showConfirmModal = false"
            @confirm="deleteTable"
        />
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
</style>
