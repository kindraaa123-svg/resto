<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Input from '@/Components/ui/Input.vue'
import Modal from '@/Components/ui/Modal.vue'
import ConfirmationModal from '@/Components/ui/ConfirmationModal.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import { Plus, Pencil, Trash2, Search, PlusCircle, Save, Tag, Utensils } from '@lucide/vue'
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash-es'

defineOptions({ layout: AppLayout })

const page = usePage()
const props = defineProps({
    addons: Object,
    categories: Array,
    menus: Array,
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
const addonToDelete = ref(null)
const impactData = ref([])
const editingAddon = ref(null)

watch(searchQuery, debounce((value) => {
    router.get(route('master.addons.index'), { 
        filter: { search: value } 
    }, { 
        preserveState: true, 
        replace: true 
    })
}, 500))

const openAddModal = () => {
    editingAddon.value = null
    form.reset()
    showModal.value = true
}

const form = useForm({
    name: '',
    price: 0,
    categoryid: '',
    menuid: '',
})

const openEditModal = (addon) => {
    editingAddon.value = addon
    form.name = addon.name
    form.price = addon.price
    form.categoryid = addon.categoryid || ''
    form.menuid = addon.menuid || ''
    showModal.value = true
}

const submit = () => {
    if (editingAddon.value) {
        form.put(route('master.addons.update', editingAddon.value.addonid), {
            onSuccess: () => {
                showModal.value = false
                form.reset()
            }
        })
    } else {
        form.post(route('master.addons.store'), {
            onSuccess: () => {
                showModal.value = false
                form.reset()
            }
        })
    }
}

const confirmDelete = (addon) => {
    addonToDelete.value = addon
    impactData.value = addon.impact || []
    showConfirmModal.value = true
}

const deleteAddon = () => {
    if (addonToDelete.value) {
        form.delete(route('master.addons.destroy', addonToDelete.value.addonid), {
            onFinish: () => {
                showConfirmModal.value = false
                addonToDelete.value = null
                impactData.value = []
            }
        })
    }
}

const formatPrice = (value) => {
    return new Intl.NumberFormat(page.props.locale === 'id' ? 'id-ID' : 'en-US', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value)
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
    <Head :title="ui.menu_addons || 'Menu Add-ons'" />

    <div class="max-w-7xl mx-auto space-y-6 lg:space-y-8 pb-20">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-2 lg:px-0">
            <div>
                <h1 class="text-2xl lg:text-3xl font-black tracking-tight text-primary uppercase">{{ ui.addons || 'Add-ons' }}</h1>
                <p class="text-muted-foreground text-[10px] lg:text-sm font-medium uppercase tracking-widest mt-1">{{ ui.manage_extra_items || 'Manage Extra Items & Options' }}</p>
            </div>
            
            <Button @click="openAddModal" class="gap-2 h-12 w-full sm:w-auto px-6 rounded-2xl shadow-lg shadow-primary/20">
                <Plus class="w-5 h-5" />
                {{ ui.add_addon || 'Add Add-on' }}
            </Button>
        </div>

        <Card class="border-none shadow-sm bg-card/50 overflow-hidden mx-2 lg:mx-0">
            <!-- Table Controls -->
            <div class="p-4 lg:p-6 border-b border-secondary/50 flex flex-col sm:flex-row items-center gap-4">
                <div class="relative w-full sm:max-w-xs">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                    <Input 
                        v-model="searchQuery" 
                        :placeholder="ui.search_addons || 'Search add-ons...'" 
                        class="pl-10 bg-background h-11 border-transparent focus:border-primary/20 text-sm"
                    />
                </div>
            </div>

            <!-- Desktop Table -->
            <div v-if="!isMobileView" class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-secondary/20 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">
                            <th class="px-6 py-4 w-20">ID</th>
                            <th class="px-6 py-4">{{ ui.name || 'Name' }}</th>
                            <th class="px-6 py-4">{{ ui.category || 'Category' }}</th>
                            <th class="px-6 py-4">{{ ui.linked_menu || 'Linked Menu' }}</th>
                            <th class="px-6 py-4 text-right">{{ ui.price || 'Price' }}</th>
                            <th class="px-6 py-4 text-right w-32">{{ ui.actions || 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-secondary/30">
                        <tr v-for="addon in addons.data" :key="addon.addonid" class="group hover:bg-white/50 transition-colors">
                            <td class="px-6 py-4 text-sm font-bold text-muted-foreground">
                                #{{ addon.addonid }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-black text-primary uppercase tracking-tight text-sm">
                                    {{ addon.name }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div v-if="addon.category" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-primary/10 text-primary uppercase tracking-wider border border-primary/20">
                                    {{ addon.category.categoryname }}
                                </div>
                                <span v-else class="text-xs text-muted-foreground italic">{{ ui.none || 'None' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div v-if="addon.menu" class="flex items-center gap-2">
                                    <Utensils class="w-3 h-3 text-muted-foreground" />
                                    <span class="text-xs font-bold text-primary">{{ addon.menu.name }}</span>
                                </div>
                                <span v-else class="text-xs text-muted-foreground italic text-center">—</span>
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-primary">
                                {{ formatPrice(addon.price) }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <button 
                                        @click="openEditModal(addon)"
                                        class="p-2 hover:bg-primary/10 text-primary rounded-lg transition-colors"
                                    >
                                        <Pencil class="w-4 h-4" />
                                    </button>
                                    <button 
                                        @click="confirmDelete(addon)"
                                        class="p-2 hover:bg-destructive/10 text-destructive rounded-lg transition-colors"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="addons.data.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-muted-foreground italic text-sm">
                                {{ ui.no_addons_found || 'No add-ons found.' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div v-else class="bg-secondary/5">
                <div class="divide-y divide-secondary/30 p-2 space-y-2">
                    <div v-for="addon in addons.data" :key="addon.addonid" class="bg-white border border-secondary/50 rounded-3xl p-4 shadow-sm relative overflow-hidden flex flex-col gap-4">
                        <div class="flex justify-between items-start">
                            <div class="space-y-1">
                                <span class="text-[9px] font-black text-muted-foreground uppercase tracking-widest block">#{{ addon.addonid }}</span>
                                <h3 class="text-sm font-black text-primary uppercase tracking-tight leading-tight">{{ addon.name }}</h3>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-black text-primary block">{{ formatPrice(addon.price) }}</span>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <div v-if="addon.category" class="inline-flex items-center px-2 py-1 rounded-lg text-[9px] font-bold bg-primary/10 text-primary uppercase tracking-wider border border-primary/20">
                                {{ addon.category.categoryname }}
                            </div>
                            <div v-if="addon.menu" class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg text-[9px] font-bold bg-secondary/20 text-muted-foreground uppercase tracking-wider border border-secondary/50">
                                <Utensils class="w-3 h-3" />
                                {{ addon.menu.name }}
                            </div>
                        </div>

                        <div class="flex gap-2 pt-2 border-t border-secondary/30">
                            <button @click="openEditModal(addon)" class="flex-1 p-3 bg-primary/10 text-primary rounded-xl transition-all active:scale-95 border border-primary/20 flex items-center justify-center gap-2">
                                <Pencil class="w-4 h-4" />
                                <span class="text-[10px] font-black uppercase tracking-widest">Edit</span>
                            </button>
                            <button @click="confirmDelete(addon)" class="p-3 bg-destructive/10 text-destructive rounded-xl transition-all active:scale-95 border border-destructive/20 flex items-center justify-center">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div v-if="addons.data.length === 0" class="py-12 text-center">
                        <div class="inline-flex p-4 rounded-full bg-secondary/30 text-muted-foreground mb-3">
                            <PlusCircle class="w-8 h-8 opacity-20" />
                        </div>
                        <h3 class="text-sm font-black text-primary uppercase">{{ ui.no_addons_found || 'No add-ons found.' }}</h3>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div class="p-4 lg:p-6 border-t border-secondary/50">
                <Pagination :links="addons.links" />
            </div>
        </Card>

        <!-- CRUD Modal -->
        <Modal :show="showModal" :title="editingAddon ? (ui.edit_addon || 'Edit Add-on') : (ui.add_new_addon || 'Add New Add-on')" @close="showModal = false">
            <form @submit.prevent="submit" class="space-y-4 lg:space-y-6">
                <div class="grid grid-cols-1 gap-4 lg:gap-6">
                    <!-- Name -->
                    <div class="space-y-1.5 lg:space-y-2">
                        <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.addon_name || 'Add-on Name' }}</label>
                        <div class="relative">
                            <PlusCircle class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                            <Input 
                                v-model="form.name" 
                                class="pl-10 h-10 lg:h-11 bg-background text-[13px] lg:text-sm" 
                                placeholder="e.g. Extra Cheese, Large Size, etc."
                                required
                            />
                        </div>
                        <div v-if="form.errors.name" class="text-xs text-destructive font-bold">{{ form.errors.name }}</div>
                    </div>

                    <!-- Price -->
                    <div class="space-y-1.5 lg:space-y-2">
                        <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.price || 'Price' }} (IDR)</label>
                        <Input 
                            v-model="form.price" 
                            type="number"
                            class="h-10 lg:h-11 bg-background text-[13px] lg:text-sm" 
                            required
                            min="0"
                        />
                        <div v-if="form.errors.price" class="text-xs text-destructive font-bold">{{ form.errors.price }}</div>
                    </div>

                    <!-- Category and Menu (Optional) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6">
                        <div class="space-y-1.5 lg:space-y-2">
                            <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.category_optional || 'Category (Optional)' }}</label>
                            <div class="relative">
                                <Tag class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                                <select 
                                    v-model="form.categoryid"
                                    class="w-full pl-10 h-10 lg:h-11 rounded-xl border border-input bg-background text-[13px] lg:text-sm shadow-sm focus:ring-1 focus:ring-ring"
                                >
                                    <option value="">{{ ui.no_category || 'No Category' }}</option>
                                    <option v-for="cat in categories" :key="cat.categoryid" :value="cat.categoryid">
                                        {{ cat.categoryname }}
                                    </option>
                                </select>
                            </div>
                            <div v-if="form.errors.categoryid" class="text-xs text-destructive font-bold">{{ form.errors.categoryid }}</div>
                        </div>

                        <div class="space-y-1.5 lg:space-y-2">
                            <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.specific_menu_optional || 'Specific Menu (Optional)' }}</label>
                            <div class="relative">
                                <Utensils class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                                <select 
                                    v-model="form.menuid"
                                    class="w-full pl-10 h-10 lg:h-11 rounded-xl border border-input bg-background text-[13px] lg:text-sm shadow-sm focus:ring-1 focus:ring-ring"
                                >
                                    <option value="">{{ ui.all_menus || 'All Menus' }}</option>
                                    <option v-for="menu in menus" :key="menu.menuid" :value="menu.menuid">
                                        {{ menu.name }}
                                    </option>
                                </select>
                            </div>
                            <div v-if="form.errors.menuid" class="text-xs text-destructive font-bold">{{ form.errors.menuid }}</div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-end gap-3 pt-6 border-t border-secondary/50">
                    <Button type="button" variant="ghost" @click="showModal = false" class="rounded-xl font-bold order-2 sm:order-1">{{ ui.cancel || 'Cancel' }}</Button>
                    <Button type="submit" class="gap-2 px-8 h-12 lg:h-11 rounded-2xl lg:rounded-xl shadow-lg shadow-primary/20 order-1 sm:order-2" :disabled="form.processing">
                        <Save class="w-4 h-4" />
                        {{ editingAddon ? (ui.update_addon || 'Update Add-on') : (ui.create_addon || 'Create Add-on') }}
                    </Button>
                </div>
            </form>
        </Modal>

        <ConfirmationModal 
            :show="showConfirmModal" 
            :title="ui.delete_addon || 'Delete Add-on'"
            :message="ui.confirm_delete_addon || 'Are you sure you want to delete this add-on? This action cannot be undone.'"
            :impact="impactData"
            type="danger"
            :confirmText="ui.delete || 'Delete'"
            @close="showConfirmModal = false"
            @confirm="deleteAddon"
        />
    </div>
</template>
