<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Input from '@/Components/ui/Input.vue'
import Modal from '@/Components/ui/Modal.vue'
import ConfirmationModal from '@/Components/ui/ConfirmationModal.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import { Plus, Pencil, Trash2, Search, Save, Utensils, Coins } from '@lucide/vue'
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash-es'

defineOptions({ layout: AppLayout })

const props = defineProps({
    capitals: Object,
    menus: Array,
    addons: Array,
    filters: Object,
})

const searchQuery = ref(props.filters?.filter?.search || '')
const showModal = ref(false)
const showConfirmModal = ref(false)
const capitalToDelete = ref(null)
const editingCapital = ref(null)

watch(searchQuery, debounce((value) => {
    router.get(route('master.capitals.index'), { filter: { search: value } }, {
        preserveState: true,
        replace: true
    })
}, 500))

const form = useForm({
    menuid: '',
    addonid: '',
    price: '',
})

const openAddModal = () => {
    editingCapital.value = null
    form.reset()
    form.menuid = ''
    form.addonid = ''
    showModal.value = true
}

const openEditModal = (item) => {
    editingCapital.value = item
    form.menuid = item.menuid || ''
    form.addonid = item.addonid || ''
    form.price = item.price
    showModal.value = true
}

const submit = () => {
    if (editingCapital.value) {
        form.put(route('master.capitals.update', editingCapital.value.modalid), {
            onSuccess: () => {
                showModal.value = false
                form.reset()
            }
        })
    } else {
        form.post(route('master.capitals.store'), {
            onSuccess: () => {
                showModal.value = false
                form.reset()
            }
        })
    }
}

const confirmDelete = (id) => {
    capitalToDelete.value = id
    showConfirmModal.value = true
}

const deleteCapitalItem = () => {
    if (capitalToDelete.value) {
        form.delete(route('master.capitals.destroy', capitalToDelete.value), {
            onFinish: () => {
                showConfirmModal.value = false
                capitalToDelete.value = null
            }
        })
    }
}

const formatPrice = (value) => {
    const num = parseFloat(value)
    if (isNaN(num)) return value
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(num)
}
</script>

<template>
    <Head title="Capital Management" />

    <div class="max-w-6xl mx-auto space-y-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-primary uppercase">Capitals</h1>
                <p class="text-muted-foreground text-sm font-medium uppercase tracking-widest mt-1">Manage Menu Add-on Pricing</p>
            </div>
            
            <Button @click="openAddModal" class="gap-2 h-12 px-6 rounded-2xl shadow-lg shadow-primary/20">
                <Plus class="w-5 h-5" />
                Add New Capital Link
            </Button>
        </div>

        <Card class="border-none shadow-sm bg-card/50 overflow-hidden">
            <div class="p-6 border-b border-secondary/50 flex flex-col sm:flex-row items-center gap-4">
                <div class="relative w-full sm:max-w-xs">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                    <Input 
                        v-model="searchQuery" 
                        placeholder="Search menu or addon..." 
                        class="pl-10 bg-background h-10 border-transparent focus:border-primary/20"
                    />
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-secondary/20 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">
                            <th class="px-6 py-4">Menu</th>
                            <th class="px-6 py-4">Add-on</th>
                            <th class="px-6 py-4 text-right">Add-on Price</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-secondary/30">
                        <tr v-for="item in capitals.data" :key="item.modalid" class="group hover:bg-white/50 transition-colors">
                            <td class="px-6 py-4 text-sm font-black text-primary uppercase">
                                {{ item.menu?.name || 'All Menus' }}
                            </td>
                            <td class="px-6 py-4">
                                <div v-if="item.addon" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-primary/10 text-primary uppercase tracking-widest border border-primary/20">
                                    {{ item.addon.name }}
                                </div>
                                <span v-else class="text-xs text-muted-foreground italic">All Add-ons</span>
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-primary">
                                {{ formatPrice(item.price) }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="openEditModal(item)" class="p-2 hover:bg-primary/10 text-primary rounded-lg transition-colors">
                                        <Pencil class="w-4 h-4" />
                                    </button>
                                    <button @click="confirmDelete(item.modalid)" class="p-2 hover:bg-destructive/10 text-destructive rounded-lg transition-colors">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-6 border-t border-secondary/50">
                <Pagination :links="capitals.links" />
            </div>
        </Card>

        <!-- CRUD Modal -->
        <Modal :show="showModal" :title="editingCapital ? 'Edit Capital Link' : 'Add New Capital Link'" @close="showModal = false">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">Menu (Optional)</label>
                        <select v-model="form.menuid" class="w-full h-11 rounded-xl border border-input bg-background text-sm focus:ring-1 focus:ring-ring px-3">
                            <option value="">None / All</option>
                            <option v-for="m in menus" :key="m.menuid" :value="m.menuid">{{ m.name }}</option>
                        </select>
                        <div v-if="form.errors.menuid" class="text-xs text-destructive font-bold">{{ form.errors.menuid }}</div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">Add-on (Optional)</label>
                        <select v-model="form.addonid" class="w-full h-11 rounded-xl border border-input bg-background text-sm focus:ring-1 focus:ring-ring px-3">
                            <option value="">None / All</option>
                            <option v-for="a in addons" :key="a.addonid" :value="a.addonid">{{ a.name }}</option>
                        </select>
                        <div v-if="form.errors.addonid" class="text-xs text-destructive font-bold">{{ form.errors.addonid }}</div>
                    </div>

                    <div class="space-y-2 col-span-full">
                        <label class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">Capital in this Menu</label>
                        <div class="relative">
                            <Coins class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                            <Input v-model="form.price" required class="pl-10 h-11" placeholder="e.g. 5000" />
                        </div>
                        <div v-if="form.errors.price" class="text-xs text-destructive font-bold">{{ form.errors.price }}</div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-6 border-t border-secondary/50">
                    <Button type="button" variant="ghost" @click="showModal = false" class="rounded-xl font-bold">Cancel</Button>
                    <Button type="submit" class="gap-2 px-8 h-11 rounded-xl shadow-lg shadow-primary/20" :disabled="form.processing">
                        <Save class="w-4 h-4" />
                        {{ editingCapital ? 'Update Link' : 'Create Link' }}
                    </Button>
                </div>
            </form>
        </Modal>

        <ConfirmationModal 
            :show="showConfirmModal" 
            title="Delete Capital Link"
            message="Are you sure you want to delete this menu-addon capital link?"
            type="danger"
            confirmText="Delete"
            @close="showConfirmModal = false"
            @confirm="deleteCapitalItem"
        />
    </div>
</template>
