<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm, router, usePage } from '@inertiajs/vue3'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Input from '@/Components/ui/Input.vue'
import Modal from '@/Components/ui/Modal.vue'
import ConfirmationModal from '@/Components/ui/ConfirmationModal.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import { Plus, Pencil, Trash2, Search, Save, Package } from '@lucide/vue'
import { ref, watch, computed } from 'vue'

defineOptions({ layout: AppLayout })

const page = usePage()
const props = defineProps({
    items: Object,
    filters: Object,
    errors: Object,
    auth: Object,
    ziggy: Object,
    system: Object,
    locale: String,
    translations: Object,
})

const ui = computed(() => page.props.translations?.ui || {})

const searchQuery = ref(props.filters?.filter?.search || '')
const showModal = ref(false)
const showConfirmModal = ref(false)
const itemToDelete = ref(null)
const impactData = ref([])
const editingItem = ref(null)

const form = useForm({
    itemname: '',
    unit: 'Pcs',
})

const applyFilters = () => {
    router.get(route('item.data-items.index'), {
        filter: {
            search: searchQuery.value,
        }
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    })
}

// Debounce search
let timeout = null
watch(searchQuery, (val) => {
    clearTimeout(timeout)
    timeout = setTimeout(() => {
        applyFilters()
    }, 500)
})

const openAddModal = () => {
    editingItem.value = null
    form.reset()
    showModal.value = true
}

const openEditModal = (item) => {
    editingItem.value = item
    form.itemname = item.itemname
    form.unit = item.unit || 'Pcs'
    showModal.value = true
}
// ... rest of submit and methods ...
</script>

<template>
    <Head :title="ui.items || 'Items Management'" />

    <div class="max-w-6xl mx-auto space-y-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-primary uppercase">{{ ui.items || 'Items' }}</h1>
                <p class="text-muted-foreground text-sm font-medium uppercase tracking-widest mt-1">{{ ui.manage_items_list || 'Manage basic items list' }}</p>
            </div>
            
            <Button @click="openAddModal" class="gap-2 h-12 px-6 rounded-2xl shadow-lg shadow-primary/20">
                <Plus class="w-5 h-5" />
                {{ ui.add_item || 'Add Item' }}
            </Button>
        </div>

        <Card class="border-none shadow-sm bg-card/50 overflow-hidden">
            <!-- Table Controls -->
            <div class="p-6 border-b border-secondary/50 flex flex-col sm:flex-row items-center gap-4">
                <div class="relative w-full sm:max-w-xs">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                    <Input 
                        v-model="searchQuery" 
                        :placeholder="ui.search_placeholder || 'Search items...'" 
                        class="pl-10 bg-background h-10 border-transparent focus:border-primary/20"
                    />
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-secondary/20 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">
                            <th class="px-6 py-4">{{ ui.item_name || 'Item Name' }}</th>
                            <th class="px-6 py-4">{{ ui.unit || 'Unit' }}</th>
                            <th class="px-6 py-4 text-right">{{ ui.actions || 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-secondary/30">
                        <tr v-for="item in items?.data || []" :key="item.itemid" class="group hover:bg-white/50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="font-black text-primary text-sm uppercase tracking-wide">{{ item.itemname }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs font-bold text-muted-foreground">{{ item.unit }}</span>
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
                        <tr v-if="!items?.data || items.data.length === 0">
                            <td colspan="3" class="px-6 py-12 text-center text-muted-foreground italic text-sm">
                                {{ ui.no_items_found || 'No items found.' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-6 border-t border-secondary/50">
                <Pagination :links="items?.links || []" />
            </div>
        </Card>

        <!-- CRUD Modal -->
        <Modal :show="showModal" :title="editingItem ? (ui.edit_item || 'Edit Item') : (ui.add_item || 'Add Item')" @close="showModal = false">
            <form @submit.prevent="submit" class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2 col-span-full md:col-span-1">
                        <label class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.item_name || 'Item Name' }}</label>
                        <div class="relative">
                            <Package class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                            <Input v-model="form.itemname" class="pl-10 h-11 bg-background font-bold" required placeholder="e.g. Napkins, Boxes" />
                        </div>
                        <div v-if="form.errors.itemname" class="text-xs text-destructive font-bold">{{ form.errors.itemname }}</div>
                    </div>

                    <div class="space-y-2 col-span-full md:col-span-1">
                        <label class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.unit || 'Unit' }}</label>
                        <Input v-model="form.unit" class="h-11 bg-background font-bold" required placeholder="Pcs, Box, Kg, etc." />
                        <div v-if="form.errors.unit" class="text-xs text-destructive font-bold">{{ form.errors.unit }}</div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-6 border-t border-secondary/50">
                    <Button type="button" variant="ghost" @click="showModal = false" class="rounded-xl font-bold">{{ ui.cancel || 'Cancel' }}</Button>
                    <Button type="submit" class="gap-2 px-8 h-11 rounded-xl shadow-lg shadow-primary/20" :disabled="form.processing">
                        <Save class="w-4 h-4" />
                        {{ editingItem ? (ui.update_item || 'Update Item') : (ui.create_item || 'Save Item') }}
                    </Button>
                </div>
            </form>
        </Modal>

        <ConfirmationModal 
            :show="showConfirmModal" 
            :title="ui.delete_item || 'Delete Item'"
            :message="ui.confirm_delete_item || 'Are you sure you want to delete this item? This action cannot be undone.'"
            :impact="impactData"
            type="danger"
            :confirmText="ui.delete || 'Delete'"
            @close="showConfirmModal = false"
            @confirm="deleteItem"
        />
    </div>
</template>
