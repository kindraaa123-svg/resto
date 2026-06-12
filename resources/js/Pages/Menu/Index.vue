<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Input from '@/Components/ui/Input.vue'
import Modal from '@/Components/ui/Modal.vue'
import ConfirmationModal from '@/Components/ui/ConfirmationModal.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import { Plus, Pencil, Trash2, Search, ImageIcon, Save, Utensils, Crop, Upload, Tag, MessageSquareText } from '@lucide/vue'
import { ref, computed } from 'vue'
import { Cropper } from 'vue-advanced-cropper'
import 'vue-advanced-cropper/dist/style.css'

defineOptions({ layout: AppLayout })

const page = usePage()
const props = defineProps({
    menus: Object,
    categories: Array,
    errors: Object,
    auth: Object,
    ziggy: Object,
    system: Object,
    locale: String,
    translations: Object,
})

const ui = computed(() => page.props.translations?.ui || {})

const searchQuery = ref('')
const showModal = ref(false)
const showCropModal = ref(false)
const showConfirmModal = ref(false)
const menuToDelete = ref(null)
const impactData = ref([])
const editingMenu = ref(null)
const rawImage = ref(null)
const cropperRef = ref(null)
const imagePreview = ref(null)

const filteredMenus = computed(() => {
    return props.menus.data.filter(menu => 
        menu.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
})

const form = useForm({
    name: '',
    price: 0,
    categoryid: '',
    description: '',
    image: null,
})

const openAddModal = () => {
    editingMenu.value = null
    form.reset()
    if (props.categories.length > 0) {
        form.categoryid = props.categories[0].categoryid
    }
    imagePreview.value = null
    showModal.value = true
}

const openEditModal = (menu) => {
    editingMenu.value = menu
    form.name = menu.name
    form.price = menu.price
    form.categoryid = menu.categoryid
    form.description = menu.description || ''
    form.image = null
    imagePreview.value = menu.image_url
    showModal.value = true
}

const onFileChange = (e) => {
    const file = e.target.files[0]
    if (file) {
        const reader = new FileReader()
        reader.onload = (event) => {
            rawImage.value = event.target.result
            showCropModal.value = true
        }
        reader.readAsDataURL(file)
        e.target.value = ''
    }
}

const cropImage = () => {
    const { canvas } = cropperRef.value.getResult()
    canvas.toBlob((blob) => {
        const croppedFile = new File([blob], 'menu-item.jpg', { type: 'image/jpeg' })
        form.image = croppedFile
        imagePreview.value = URL.createObjectURL(blob)
        showCropModal.value = false
    }, 'image/jpeg', 0.8)
}

const submit = () => {
    if (editingMenu.value) {
        form.transform((data) => ({
            ...data,
            _method: 'PUT',
        })).post(route('menu.update', editingMenu.value.menuid), {
            onSuccess: () => {
                showModal.value = false
                form.reset()
            },
            forceFormData: true,
        })
    } else {
        form.transform((data) => data)
            .post(route('menu.store'), {
                onSuccess: () => {
                    showModal.value = false
                    form.reset()
                }
            })
    }
}

const confirmDelete = (menu) => {
    menuToDelete.value = menu
    impactData.value = menu.impact || []
    showConfirmModal.value = true
}

const deleteMenu = () => {
    if (menuToDelete.value) {
        form.delete(route('menu.destroy', menuToDelete.value.menuid), {
            onFinish: () => {
                showConfirmModal.value = false
                menuToDelete.value = null
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
</script>

<template>
    <Head :title="ui.menu_management || 'Menu Management'" />

    <div class="max-w-7xl mx-auto space-y-6 lg:space-y-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-2 lg:px-0">
            <div>
                <h1 class="text-2xl lg:text-3xl font-black tracking-tight text-primary uppercase">{{ ui.menu_list || 'Menu List' }}</h1>
                <p class="text-muted-foreground text-[10px] lg:text-sm font-medium uppercase tracking-widest mt-1">{{ ui.manage_fnb || 'Manage your food and beverage items' }}</p>
            </div>
            
            <Button @click="openAddModal" class="gap-2 h-12 w-full sm:w-auto px-6 rounded-2xl shadow-lg shadow-primary/20">
                <Plus class="w-5 h-5" />
                {{ ui.add_new_menu || 'Add New Menu' }}
            </Button>
        </div>

        <!-- Filters & Search -->
        <div class="flex items-center gap-4 px-2 lg:px-0">
            <div class="relative w-full sm:max-w-sm">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                <Input 
                    v-model="searchQuery" 
                    :placeholder="ui.search_menu || 'Search menu name...'" 
                    class="pl-10 bg-card border-transparent shadow-sm h-11"
                />
            </div>
        </div>

        <!-- Menu Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 lg:gap-6 px-2 lg:px-0 pb-20">
            <template v-for="menu in filteredMenus" :key="menu.menuid">
                <Card class="group overflow-hidden border-none shadow-sm hover:shadow-xl transition-all duration-300 bg-card/50">
                    <div class="aspect-[4/3] overflow-hidden relative bg-secondary/30">
                        <img 
                            v-if="menu.image_url" 
                            :src="menu.image_url" 
                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center text-muted-foreground/30">
                            <Utensils class="w-12 h-12" />
                        </div>
                        
                        <!-- Floating Actions (Always visible on mobile, hover on desktop) -->
                        <div class="absolute top-3 right-3 flex flex-col gap-2 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity z-10">
                            <button 
                                @click="openEditModal(menu)"
                                class="p-2.5 bg-white/95 backdrop-blur text-primary rounded-xl shadow-xl hover:bg-primary hover:text-white transition-all border border-secondary/50"
                            >
                                <Pencil class="w-4 h-4 lg:w-3.5 lg:h-3.5" />
                            </button>
                            <button 
                                @click="confirmDelete(menu)"
                                class="p-2.5 bg-white/95 backdrop-blur text-destructive rounded-xl shadow-xl hover:bg-destructive hover:text-white transition-all border border-secondary/50"
                            >
                                <Trash2 class="w-4 h-4 lg:w-3.5 lg:h-3.5" />
                            </button>
                        </div>
                    </div>

                    <div class="p-4 lg:p-5 space-y-2 lg:space-y-3 min-w-0">
                        <div class="flex justify-between items-start gap-2">
                            <h3 class="font-black text-primary uppercase leading-tight tracking-tight text-sm lg:text-base whitespace-normal [overflow-wrap:anywhere]">
                                {{ menu.name }}
                            </h3>
                        </div>
                        <div v-if="menu.category" class="inline-flex items-center px-2 py-0.5 rounded-full text-[8px] lg:text-[9px] font-black bg-primary/10 text-primary uppercase tracking-widest border border-primary/20">
                            {{ menu.category.categoryname }}
                        </div>
                        <p class="text-[10px] lg:text-xs text-muted-foreground whitespace-normal [overflow-wrap:anywhere] line-clamp-2 min-h-[2.5rem]">
                            {{ menu.description || (ui.no_financial_data ? 'No description provided.' : 'Tidak ada deskripsi.') }}
                        </p>
                        <div class="pt-2 border-t border-secondary/50 flex items-center justify-between">
                            <span class="text-base lg:text-lg font-black text-primary">{{ formatPrice(menu.price) }}</span>
                        </div>
                    </div>
                </Card>
            </template>

            <div v-if="filteredMenus.length === 0" class="col-span-full py-20 text-center">
                <div class="inline-flex p-6 rounded-full bg-secondary/30 text-muted-foreground mb-4">
                    <Search class="w-12 h-12" />
                </div>
                <h3 class="text-xl font-bold text-primary uppercase">{{ ui.no_menu_found || 'No menu items found' }}</h3>
                <p class="text-muted-foreground text-sm">{{ ui.adjust_search_or_add || 'Try adjusting your search or add a new menu item.' }}</p>
            </div>
        </div>

        <!-- Pagination -->
        <div class="mt-12">
            <Pagination :links="menus.links" />
        </div>

        <!-- CRUD Modal -->
        <Modal :show="showModal" @close="showModal = false" maxWidth="3xl">
            <template #title>
                <div class="flex items-center gap-3">
                    <div :class="['w-10 h-10 rounded-xl flex items-center justify-center', editingMenu ? 'bg-amber-100 text-amber-600' : 'bg-primary/10 text-primary']">
                        <Plus v-if="!editingMenu" class="w-5 h-5" />
                        <Pencil v-else class="w-5 h-5" />
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-foreground uppercase tracking-tight leading-none">
                            {{ editingMenu ? (ui.edit_menu_item || 'Edit Menu Item') : (ui.add_new_menu_item || 'Add New Menu Item') }}
                        </h2>
                        <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest mt-1">
                            {{ editingMenu ? 'Update existing item details' : 'Create a new item for your menu' }}
                        </p>
                    </div>
                </div>
            </template>

            <form @submit.prevent="submit" class="p-6 lg:p-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                    <!-- Visual Section -->
                    <div class="lg:col-span-5 space-y-6">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <label class="text-[10px] font-black uppercase tracking-widest text-primary flex items-center gap-2">
                                    <ImageIcon class="w-3.5 h-3.5" />
                                    {{ ui.menu_picture || 'Menu Picture' }}
                                </label>
                            </div>
                            
                            <div class="relative group">
                                <div class="aspect-[4/3] rounded-[2rem] bg-secondary/20 border-2 border-dashed border-secondary/50 flex flex-col items-center justify-center overflow-hidden transition-all group-hover:border-primary/30 group-hover:bg-primary/[0.02]">
                                    <img v-if="imagePreview" :src="imagePreview" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" />
                                    <div v-else class="flex flex-col items-center gap-3 text-muted-foreground/40">
                                        <div class="w-16 h-16 rounded-3xl bg-white border border-secondary/50 flex items-center justify-center shadow-sm">
                                            <Upload class="w-8 h-8" />
                                        </div>
                                        <p class="text-[9px] font-black uppercase tracking-widest">{{ ui.upload_image || 'Upload Image' }}</p>
                                    </div>

                                    <label class="absolute inset-0 cursor-pointer flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all bg-primary/10 backdrop-blur-[2px]">
                                        <input type="file" @change="onFileChange" class="hidden" accept="image/*" />
                                        <div class="bg-white px-6 py-3 rounded-2xl shadow-2xl flex items-center gap-2 transform translate-y-4 group-hover:translate-y-0 transition-transform">
                                            <Upload class="w-4 h-4 text-primary" />
                                            <span class="text-[10px] font-black text-primary uppercase tracking-widest">Browse Files</span>
                                        </div>
                                    </label>
                                </div>
                                <div v-if="form.errors.image" class="mt-2 text-[10px] font-bold text-destructive text-center uppercase tracking-widest">{{ form.errors.image }}</div>
                            </div>
                            <p class="text-[9px] text-muted-foreground font-medium italic text-center px-4 leading-relaxed">
                                Recommended ratio 4:3. Max size 2MB. Supports JPG, PNG.
                            </p>
                        </div>
                    </div>

                    <!-- Information Section -->
                    <div class="lg:col-span-7 space-y-6">
                        <div class="grid grid-cols-1 gap-6">
                            <!-- Basic Info -->
                            <div class="space-y-4">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-primary flex items-center gap-2">
                                        <Tag class="w-3.5 h-3.5" />
                                        {{ ui.item_name || 'Item Name' }}
                                    </label>
                                    <Input 
                                        v-model="form.name" 
                                        placeholder="e.g. Avocado Toast" 
                                        required 
                                        class="h-12 rounded-2xl bg-secondary/20 border-transparent focus:bg-white focus:border-primary/20 transition-all font-bold"
                                    />
                                    <div v-if="form.errors.name" class="text-[10px] font-bold text-destructive uppercase tracking-widest ml-1">{{ form.errors.name }}</div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black uppercase tracking-widest text-primary flex items-center gap-2">
                                            <Utensils class="w-3.5 h-3.5" />
                                            {{ ui.category || 'Category' }}
                                        </label>
                                        <div class="relative">
                                            <select 
                                                v-model="form.categoryid"
                                                class="w-full h-12 px-4 rounded-2xl bg-secondary/20 border-transparent text-sm font-bold shadow-none transition-all focus:bg-white focus:border-primary/20 focus:ring-0 appearance-none"
                                                required
                                            >
                                                <option value="" disabled>{{ ui.select_category || 'Select Category' }}</option>
                                                <option v-for="cat in categories" :key="cat.categoryid" :value="cat.categoryid">
                                                    {{ cat.categoryname }}
                                                </option>
                                            </select>
                                            <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                                                <Plus class="w-4 h-4 text-muted-foreground rotate-45" />
                                            </div>
                                        </div>
                                        <div v-if="form.errors.categoryid" class="text-[10px] font-bold text-destructive uppercase tracking-widest ml-1">{{ form.errors.categoryid }}</div>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black uppercase tracking-widest text-primary flex items-center gap-2">
                                            <Tag class="w-3.5 h-3.5" />
                                            {{ ui.price || 'Price' }} (IDR)
                                        </label>
                                        <div class="relative">
                                            <div class="absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground font-black text-[10px] uppercase">Rp</div>
                                            <Input 
                                                v-model="form.price" 
                                                type="number" 
                                                required 
                                                min="0" 
                                                class="h-12 pl-12 rounded-2xl bg-secondary/20 border-transparent focus:bg-white focus:border-primary/20 transition-all font-bold"
                                            />
                                        </div>
                                        <div v-if="form.errors.price" class="text-[10px] font-bold text-destructive uppercase tracking-widest ml-1">{{ form.errors.price }}</div>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-primary flex items-center gap-2">
                                        <MessageSquareText class="w-3.5 h-3.5" />
                                        {{ ui.description || 'Description' }}
                                    </label>
                                    <textarea 
                                        v-model="form.description" 
                                        rows="4"
                                        class="w-full p-4 rounded-2xl bg-secondary/20 border-transparent text-sm font-medium shadow-none transition-all focus:bg-white focus:border-primary/20 focus:ring-0 resize-none"
                                        :placeholder="ui.short_desc_placeholder || 'Describe the flavors, ingredients, etc...'"
                                    ></textarea>
                                    <div v-if="form.errors.description" class="text-[10px] font-bold text-destructive uppercase tracking-widest ml-1">{{ form.errors.description }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="flex flex-col sm:flex-row justify-end gap-3 mt-10 pt-8 border-t border-secondary/30">
                    <Button type="button" variant="ghost" @click="showModal = false" class="rounded-2xl font-black uppercase tracking-widest text-[10px] h-12 px-8 order-2 sm:order-1">
                        {{ ui.cancel || 'Cancel' }}
                    </Button>
                    <Button type="submit" class="gap-2 px-10 h-12 rounded-2xl shadow-xl shadow-primary/20 order-1 sm:order-2 font-black uppercase tracking-widest text-[10px]" :disabled="form.processing">
                        <Save v-if="!form.processing" class="w-4 h-4" />
                        <div v-else class="w-4 h-4 rounded-full border-2 border-white/30 border-t-white animate-spin"></div>
                        {{ editingMenu ? (ui.update_item || 'Update Item') : (ui.create_item || 'Create Item') }}
                    </Button>
                </div>
            </form>
        </Modal>

        <!-- Crop Modal -->
        <Modal :show="showCropModal" :title="ui.crop_menu_picture || 'Crop Menu Picture'" @close="showCropModal = false">
            <div class="space-y-4">
                <div class="overflow-hidden rounded-2xl border border-secondary bg-black/5 max-h-[60vh]">
                    <Cropper
                        ref="cropperRef"
                        class="h-[300px] sm:h-[400px]"
                        :src="rawImage"
                        :stencil-props="{
                            aspectRatio: 4/3
                        }"
                    />
                </div>
                <p class="text-[10px] text-muted-foreground text-center italic leading-tight">
                    {{ ui.crop_instruction || 'Crop your image to 4:3 ratio for the best card display.' }}
                </p>
            </div>
            
            <template #footer>
                <div class="flex flex-col sm:flex-row gap-3 w-full">
                    <Button variant="ghost" @click="showCropModal = false" class="rounded-xl font-bold w-full sm:w-auto order-2 sm:order-1">{{ ui.cancel || 'Cancel' }}</Button>
                    <Button @click="cropImage" class="gap-2 px-8 rounded-xl font-black uppercase tracking-widest text-xs w-full sm:w-auto order-1 sm:order-2">
                        <Crop class="w-4 h-4" />
                        {{ ui.crop_apply || 'Crop & Apply' }}
                    </Button>
                </div>
            </template>
        </Modal>

        <!-- Confirmation Modal -->
        <ConfirmationModal 
            :show="showConfirmModal" 
            :title="ui.delete_menu_item || 'Delete Menu Item'"
            :message="ui.confirm_delete_menu || 'Are you sure you want to delete this menu item? This action cannot be undone.'"
            :impact="impactData"
            type="danger"
            :confirmText="ui.delete || 'Delete'"
            @close="showConfirmModal = false"
            @confirm="deleteMenu"
        />
    </div>
</template>
