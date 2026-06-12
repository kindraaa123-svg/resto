<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Input from '@/Components/ui/Input.vue'
import Modal from '@/Components/ui/Modal.vue'
import ConfirmationModal from '@/Components/ui/ConfirmationModal.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import { Plus, Pencil, Trash2, Search, Tag, X, Save } from '@lucide/vue'
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash-es'

defineOptions({ layout: AppLayout })

const page = usePage()
const props = defineProps({
    categories: Object,
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
const categoryToDelete = ref(null)
const impactData = ref([])
const editingCategory = ref(null)

watch(searchQuery, debounce((value) => {
    router.get(route('master.categories.index'), { filter: { search: value } }, {
        preserveState: true,
        replace: true
    })
}, 500))

const form = useForm({
    categoryname: '',
})

const openAddModal = () => {
    editingCategory.value = null
    form.reset()
    showModal.value = true
}

const openEditModal = (category) => {
    editingCategory.value = category
    form.categoryname = category.categoryname
    showModal.value = true
}

const submit = () => {
    if (editingCategory.value) {
        form.put(route('master.categories.update', editingCategory.value.categoryid), {
            onSuccess: () => {
                showModal.value = false
                form.reset()
            }
        })
    } else {
        form.post(route('master.categories.store'), {
            onSuccess: () => {
                showModal.value = false
                form.reset()
            }
        })
    }
}

const confirmDelete = (category) => {
    categoryToDelete.value = category
    impactData.value = category.impact || []
    showConfirmModal.value = true
}

const deleteCategory = () => {
    if (categoryToDelete.value) {
        form.delete(route('master.categories.destroy', categoryToDelete.value.categoryid), {
            onFinish: () => {
                showConfirmModal.value = false
                categoryToDelete.value = null
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
    <Head :title="ui.menu_categories || 'Menu Categories'" />

    <div class="max-w-7xl mx-auto space-y-6 lg:space-y-8 pb-20">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-2 lg:px-0">
            <div>
                <h1 class="text-2xl lg:text-3xl font-black tracking-tight text-primary uppercase">{{ ui.categories || 'Categories' }}</h1>
                <p class="text-muted-foreground text-[10px] lg:text-sm font-medium uppercase tracking-widest mt-1">{{ ui.manage_menu_classifications || 'Manage Menu Classifications' }}</p>
            </div>
            
            <Button @click="openAddModal" class="gap-2 h-12 w-full sm:w-auto px-6 rounded-2xl shadow-lg shadow-primary/20">
                <Plus class="w-5 h-5" />
                {{ ui.add_category || 'Add Category' }}
            </Button>
        </div>

        <Card class="border-none shadow-sm bg-card/50 overflow-hidden mx-2 lg:mx-0">
            <!-- Table Controls -->
            <div class="p-4 lg:p-6 border-b border-secondary/50 flex flex-col sm:flex-row items-center gap-4">
                <div class="relative w-full sm:max-w-xs">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                    <Input 
                        v-model="searchQuery" 
                        :placeholder="ui.search_categories || 'Search categories...'" 
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
                            <th class="px-6 py-4">{{ ui.category_name || 'Category Name' }}</th>
                            <th class="px-6 py-4 text-right">{{ ui.actions || 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-secondary/30">
                        <tr v-for="category in categories.data" :key="category.categoryid" class="group hover:bg-white/50 transition-colors">
                            <td class="px-6 py-4 text-sm font-bold text-muted-foreground">
                                #{{ category.categoryid }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 rounded-lg bg-primary/5 text-primary">
                                        <Tag class="w-4 h-4" />
                                    </div>
                                    <span class="font-black text-primary uppercase tracking-tight text-sm">
                                        {{ category.categoryname }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <button 
                                        @click="openEditModal(category)"
                                        class="p-2 hover:bg-primary/10 text-primary rounded-lg transition-colors"
                                    >
                                        <Pencil class="w-4 h-4" />
                                    </button>
                                    <button 
                                        @click="confirmDelete(category)"
                                        class="p-2 hover:bg-destructive/10 text-destructive rounded-lg transition-colors"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="categories.data.length === 0">
                            <td colspan="3" class="px-6 py-12 text-center text-muted-foreground italic text-sm">
                                {{ ui.no_categories_found || 'No categories found.' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div v-else class="bg-secondary/5">
                <div class="divide-y divide-secondary/30 p-2 space-y-2">
                    <div v-for="category in categories.data" :key="category.categoryid" class="bg-white border border-secondary/50 rounded-3xl p-4 shadow-sm relative overflow-hidden flex flex-col gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary font-black shrink-0">
                                <Tag class="w-5 h-5" />
                            </div>
                            <div class="space-y-1">
                                <span class="text-[9px] font-black text-muted-foreground uppercase tracking-widest block">#{{ category.categoryid }}</span>
                                <h3 class="text-sm font-black text-primary uppercase tracking-tight leading-tight">{{ category.categoryname }}</h3>
                            </div>
                        </div>

                        <div class="flex gap-2 pt-2 border-t border-secondary/30">
                            <button @click="openEditModal(category)" class="flex-1 p-3 bg-primary/10 text-primary rounded-xl transition-all active:scale-95 border border-primary/20 flex items-center justify-center gap-2">
                                <Pencil class="w-4 h-4" />
                                <span class="text-[10px] font-black uppercase tracking-widest">Edit</span>
                            </button>
                            <button @click="confirmDelete(category)" class="p-3 bg-destructive/10 text-destructive rounded-xl transition-all active:scale-95 border border-destructive/20 flex items-center justify-center">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div v-if="categories.data.length === 0" class="py-12 text-center">
                        <div class="inline-flex p-4 rounded-full bg-secondary/30 text-muted-foreground mb-3">
                            <Tag class="w-8 h-8 opacity-20" />
                        </div>
                        <h3 class="text-sm font-black text-primary uppercase">{{ ui.no_categories_found || 'No categories found.' }}</h3>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div class="p-4 lg:p-6 border-t border-secondary/50">
                <Pagination :links="categories.links" />
            </div>
        </Card>

        <!-- CRUD Modal -->
        <Modal :show="showModal" :title="editingCategory ? (ui.edit_category || 'Edit Category') : (ui.add_new_category || 'Add New Category')" @close="showModal = false">
            <form @submit.prevent="submit" class="space-y-4 lg:space-y-6">
                <div class="space-y-1.5 lg:space-y-2">
                    <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.category_name || 'Category Name' }}</label>
                    <div class="relative">
                        <Tag class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                        <Input 
                            v-model="form.categoryname" 
                            class="pl-10 h-10 lg:h-11 bg-background text-[13px] lg:text-sm" 
                            placeholder="e.g. Drinks, Foods, etc."
                            required
                        />
                    </div>
                    <div v-if="form.errors.categoryname" class="text-xs text-destructive font-bold">{{ form.errors.categoryname }}</div>
                </div>

                <div class="flex flex-col sm:flex-row justify-end gap-3 pt-6 border-t border-secondary/50">
                    <Button type="button" variant="ghost" @click="showModal = false" class="rounded-xl font-bold order-2 sm:order-1">{{ ui.cancel || 'Cancel' }}</Button>
                    <Button type="submit" class="gap-2 px-8 h-12 lg:h-11 rounded-2xl lg:rounded-xl shadow-lg shadow-primary/20 order-1 sm:order-2" :disabled="form.processing">
                        <Save class="w-4 h-4" />
                        {{ editingCategory ? (ui.update_category || 'Update Category') : (ui.create_category || 'Create Category') }}
                    </Button>
                </div>
            </form>
        </Modal>

        <ConfirmationModal 
            :show="showConfirmModal" 
            :title="ui.delete_category || 'Delete Category'"
            :message="ui.confirm_delete_category || 'Are you sure you want to delete this category? This action cannot be undone.'"
            :impact="impactData"
            type="danger"
            :confirmText="ui.delete || 'Delete'"
            @close="showConfirmModal = false"
            @confirm="deleteCategory"
        />
    </div>
</template>
