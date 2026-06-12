<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm, router, usePage } from '@inertiajs/vue3'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Input from '@/Components/ui/Input.vue'
import Modal from '@/Components/ui/Modal.vue'
import ConfirmationModal from '@/Components/ui/ConfirmationModal.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import { Plus, Pencil, Trash2, Search, Save, Package, CheckCircle } from '@lucide/vue'
import { ref, watch, computed } from 'vue'

defineOptions({ layout: AppLayout })

const page = usePage()
const props = defineProps({
    ingredients: Object,
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
const ingredientToDelete = ref(null)
const impactData = ref([])
const editingIngredient = ref(null)

const form = useForm({
    name: '',
    unit: 'Pcs',
})

const applyFilters = () => {
    router.get(route('ingredient.data-ingredients.index'), {
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
    editingIngredient.value = null
    form.reset()
    showModal.value = true
}

const openEditModal = (item) => {
    editingIngredient.value = item
    form.name = item.name
    form.unit = item.unit || 'Pcs'
    showModal.value = true
}

const submit = () => {
    if (editingIngredient.value) {
        form.put(route('ingredient.data-ingredients.update', editingIngredient.value.ingredientid), {
            onSuccess: () => {
                showModal.value = false
                form.reset()
            }
        })
    } else {
        form.post(route('ingredient.data-ingredients.store'), {
            onSuccess: () => {
                showModal.value = false
                form.reset()
            }
        })
    }
}

const confirmDelete = (item) => {
    ingredientToDelete.value = item
    impactData.value = item.impact || []
    showConfirmModal.value = true
}

const deleteIngredient = () => {
    if (ingredientToDelete.value) {
        form.delete(route('ingredient.data-ingredients.destroy', ingredientToDelete.value.ingredientid), {
            onFinish: () => {
                showConfirmModal.value = false
                ingredientToDelete.value = null
                impactData.value = []
            }
        })
    }
}
</script>

<template>
    <Head :title="ui.raw_ingredients || 'Ingredients Management'" />

    <div class="max-w-6xl mx-auto space-y-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-primary uppercase">{{ ui.raw_ingredients || 'Ingredients' }}</h1>
                <p class="text-muted-foreground text-sm font-medium uppercase tracking-widest mt-1">{{ ui.manage_ingredients_list || 'Manage Ingredient Master Data' }}</p>
            </div>
            
            <Button @click="openAddModal" class="gap-2 h-12 px-6 rounded-2xl shadow-lg shadow-primary/20">
                <Plus class="w-5 h-5" />
                {{ ui.add_ingredient || 'Add Ingredient' }}
            </Button>
        </div>

        <Card class="border-none shadow-sm bg-card/50 overflow-hidden">
            <!-- Table Controls -->
            <div class="p-6 border-b border-secondary/50 flex flex-col sm:flex-row items-center gap-4">
                <div class="relative w-full sm:max-w-xs">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                    <Input 
                        v-model="searchQuery" 
                        :placeholder="ui.search_menu || 'Search ingredients...'" 
                        class="pl-10 bg-background h-10 border-transparent focus:border-primary/20"
                    />
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-secondary/20 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">
                            <th class="px-6 py-4">{{ ui.ingredient_name || 'Ingredient Name' }}</th>
                            <th class="px-6 py-4">{{ ui.unit || 'Base Unit' }}</th>
                            <th class="px-6 py-4 text-right">{{ ui.actions || 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-secondary/30">
                        <tr v-for="item in ingredients?.data || []" :key="item.ingredientid" class="group hover:bg-white/50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="font-black text-primary text-sm uppercase tracking-wide">{{ item.name }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-lg bg-secondary/50 text-[10px] font-black uppercase tracking-widest text-muted-foreground border border-secondary">{{ item.unit }}</span>
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
                        <tr v-if="!ingredients?.data || ingredients.data.length === 0">
                            <td colspan="2" class="px-6 py-12 text-center text-muted-foreground italic text-sm">
                                {{ ui.no_ingredients_found || 'No ingredients found.' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-6 border-t border-secondary/50">
                <Pagination :links="ingredients?.links || []" />
            </div>
        </Card>

        <!-- CRUD Modal -->
        <Modal :show="showModal" :title="editingIngredient ? (ui.edit_ingredient || 'Edit Ingredient') : (ui.add_ingredient || 'Add Ingredient')" @close="showModal = false">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.ingredient_name || 'Ingredient Name' }}</label>
                    <div class="relative">
                        <Package class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                        <Input v-model="form.name" class="pl-10 h-11 bg-background" required placeholder="e.g. Sugar, Flour" />
                    </div>
                    <div v-if="form.errors.name" class="text-xs text-destructive font-bold">{{ form.errors.name }}</div>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.unit || 'Standard Unit' }}</label>
                    <div class="grid grid-cols-3 gap-3">
                        <label v-for="u in ['Kg', 'Lt', 'Pcs']" :key="u" 
                            class="relative flex cursor-pointer rounded-xl border bg-background p-3 shadow-sm focus-within:ring-1 focus-within:ring-ring transition-all hover:bg-secondary/10" 
                            :class="{'border-primary bg-primary/5 ring-1 ring-primary': form.unit === u, 'border-input': form.unit !== u}"
                        >
                            <input type="radio" v-model="form.unit" :value="u" class="sr-only" />
                            <div class="flex w-full items-center justify-center">
                                <span class="text-xs font-black uppercase tracking-widest" :class="form.unit === u ? 'text-primary' : 'text-muted-foreground'">{{ u }}</span>
                            </div>
                            <CheckCircle v-if="form.unit === u" class="absolute top-1 right-1 w-3 h-3 text-primary" />
                        </label>
                    </div>
                    <div v-if="form.errors.unit" class="text-xs text-destructive font-bold">{{ form.errors.unit }}</div>
                </div>

                <div class="flex justify-end gap-3 pt-6 border-t border-secondary/50">
                    <Button type="button" variant="ghost" @click="showModal = false" class="rounded-xl font-bold">{{ ui.cancel || 'Cancel' }}</Button>
                    <Button type="submit" class="gap-2 px-8 h-11 rounded-xl shadow-lg shadow-primary/20" :disabled="form.processing">
                        <Save class="w-4 h-4" />
                        {{ editingIngredient ? (ui.update_ingredient || 'Update Ingredient') : (ui.create_ingredient || 'Save Ingredient') }}
                    </Button>
                </div>
            </form>
        </Modal>

        <ConfirmationModal 
            :show="showConfirmModal" 
            :title="ui.delete_ingredient || 'Delete Ingredient'"
            :message="ui.confirm_delete_ingredient || 'Are you sure you want to delete this ingredient? This action cannot be undone.'"
            :impact="impactData"
            type="danger"
            :confirmText="ui.delete || 'Delete'"
            @close="showConfirmModal = false"
            @confirm="deleteIngredient"
        />
    </div>
</template>
