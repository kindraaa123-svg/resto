<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Input from '@/Components/ui/Input.vue'
import Modal from '@/Components/ui/Modal.vue'
import ConfirmationModal from '@/Components/ui/ConfirmationModal.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import { Plus, Pencil, Trash2, Search, MapPin, Save, Building, Navigation, Globe } from '@lucide/vue'
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash-es'

defineOptions({ layout: AppLayout })

const page = usePage()
const props = defineProps({
    branches: Object,
    provinces: Array,
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

watch(searchQuery, debounce((value) => {
    router.get(route('master.branches.index'), { 
        filter: { search: value } 
    }, { 
        preserveState: true, 
        replace: true 
    })
}, 500))

const showConfirmModal = ref(false)
const branchToDelete = ref(null)
const impactData = ref([])
const editingBranch = ref(null)

const cities = ref([])
const districts = ref([])
const villages = ref([])
const form = useForm({
    branchid: '',
    branchname: '',
    provincesid: '',
    citiesid: '',
    districtsid: '',
    villagesid: '',
    detail_address: '',
    longitude: '',
    latitude: '',
})

// Location loaders
const loadCities = async (provinceId) => {
    if (!provinceId) return
    const res = await axios.get(route('master.branches.cities', provinceId))
    cities.value = res.data
}

const loadDistricts = async (cityId) => {
    if (!cityId) return
    const res = await axios.get(route('master.branches.districts', cityId))
    districts.value = res.data
}

const loadVillages = async (districtId) => {
    if (!districtId) return
    const res = await axios.get(route('master.branches.villages', districtId))
    villages.value = res.data
}

watch(() => form.provincesid, (val) => {
    cities.value = []
    form.citiesid = ''
    loadCities(val)
})

watch(() => form.citiesid, (val) => {
    districts.value = []
    form.districtsid = ''
    loadDistricts(val)
})

watch(() => form.districtsid, (val) => {
    villages.value = []
    form.villagesid = ''
    loadVillages(val)
})

const openAddModal = () => {
    editingBranch.value = null
    form.reset()
    showModal.value = true
}

const openEditModal = async (branch) => {
    editingBranch.value = branch
    form.branchname = branch.branchname
    form.provincesid = branch.provincesid
    
    await loadCities(branch.provincesid)
    form.citiesid = branch.citiesid
    
    await loadDistricts(branch.citiesid)
    form.districtsid = branch.districtsid
    
    await loadVillages(branch.districtsid)
    form.villagesid = branch.villagesid
    
    form.detail_address = branch.detail_address
    form.longitude = branch.longitude
    form.latitude = branch.latitude
    showModal.value = true
}

const submit = () => {
    if (editingBranch.value) {
        form.put(route('master.branches.update', editingBranch.value.branchid), {
            onSuccess: () => {
                showModal.value = false
                form.reset()
            }
        })
    } else {
        form.post(route('master.branches.store'), {
            onSuccess: () => {
                showModal.value = false
                form.reset()
            }
        })
    }
}

const confirmDelete = (branch) => {
    branchToDelete.value = branch
    impactData.value = branch.impact || []
    showConfirmModal.value = true
}

const deleteBranch = () => {
    if (branchToDelete.value) {
        form.delete(route('master.branches.destroy', branchToDelete.value.branchid), {
            onFinish: () => {
                showConfirmModal.value = false
                branchToDelete.value = null
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
    <Head :title="ui.branch_management || 'Branch Management'" />

    <div class="max-w-7xl mx-auto space-y-6 lg:space-y-8 pb-20">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-2 lg:px-0">
            <div>
                <h1 class="text-2xl lg:text-3xl font-black tracking-tight text-primary uppercase">{{ ui.branches || 'Branches' }}</h1>
                <p class="text-muted-foreground text-[10px] lg:text-sm font-medium uppercase tracking-widest mt-1">{{ ui.manage_store_locations || 'Manage Store Locations' }}</p>
            </div>
            
            <Button @click="openAddModal" class="gap-2 h-12 w-full sm:w-auto px-6 rounded-2xl shadow-lg shadow-primary/20">
                <Plus class="w-5 h-5" />
                {{ ui.add_new_branch || 'Add New Branch' }}
            </Button>
        </div>

        <Card class="border-none shadow-sm bg-card/50 overflow-hidden mx-2 lg:mx-0">
            <div class="p-4 lg:p-6 border-b border-secondary/50 flex flex-col sm:flex-row items-center gap-4">
                <div class="relative w-full sm:max-w-xs">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                    <Input 
                        v-model="searchQuery" 
                        :placeholder="ui.search_branches || 'Search branches...'" 
                        class="pl-10 bg-background h-11 border-transparent focus:border-primary/20 text-sm"
                    />
                </div>
            </div>

            <div v-if="!isMobileView" class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-secondary/20 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">
                            <th class="px-6 py-4">{{ ui.branch || 'Branch' }}</th>
                            <th class="px-6 py-4">{{ ui.location || 'Location' }}</th>
                            <th class="px-6 py-4 text-right">{{ ui.actions || 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-secondary/30">
                        <tr v-for="branch in branches.data" :key="branch.branchid" class="group hover:bg-white/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary font-black">
                                        <Building class="w-5 h-5" />
                                    </div>
                                    <span class="font-black text-primary uppercase tracking-tight text-sm">
                                        {{ branch.branchname }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col text-xs font-bold text-muted-foreground">
                                    <span class="truncate max-w-[250px]">{{ branch.detail_address }}</span>
                                    <span class="text-[10px] uppercase opacity-70">
                                        {{ branch.village?.name }}, {{ branch.city?.name }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="openEditModal(branch)" class="p-2 hover:bg-primary/10 text-primary rounded-lg transition-colors" :title="ui.edit_branch || 'Edit Branch'">
                                        <Pencil class="w-4 h-4" />
                                    </button>
                                    <button @click="confirmDelete(branch)" class="p-2 hover:bg-destructive/10 text-destructive rounded-lg transition-colors" :title="ui.delete_branch || 'Delete Branch'">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div v-else class="bg-secondary/5">
                <div class="divide-y divide-secondary/30 p-2 space-y-2">
                    <div v-for="branch in branches.data" :key="branch.branchid" class="bg-white border border-secondary/50 rounded-3xl p-4 shadow-sm relative overflow-hidden flex flex-col gap-4">
                        <div class="flex items-start gap-3">
                            <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary font-black shrink-0">
                                <Building class="w-6 h-6" />
                            </div>
                            <div class="space-y-1">
                                <h3 class="text-sm font-black text-primary uppercase tracking-tight leading-tight">{{ branch.branchname }}</h3>
                            </div>
                        </div>

                        <div class="p-3 bg-secondary/10 rounded-2xl border border-secondary/20 flex gap-2">
                            <MapPin class="w-4 h-4 text-muted-foreground shrink-0 mt-0.5" />
                            <div class="flex flex-col text-[10px] font-bold text-muted-foreground">
                                <span class="leading-snug">{{ branch.detail_address }}</span>
                                <span class="text-[9px] uppercase opacity-70 mt-1">
                                    {{ branch.village?.name }}, {{ branch.city?.name }}
                                </span>
                            </div>
                        </div>

                        <div class="flex gap-2 pt-2 border-t border-secondary/30">
                            <button @click="openEditModal(branch)" class="flex-1 p-3 bg-primary/10 text-primary rounded-xl transition-all active:scale-95 border border-primary/20 flex items-center justify-center gap-2">
                                <Pencil class="w-4 h-4" />
                                <span class="text-[10px] font-black uppercase tracking-widest">Edit</span>
                            </button>
                            <button @click="confirmDelete(branch)" class="p-3 bg-destructive/10 text-destructive rounded-xl transition-all active:scale-95 border border-destructive/20 flex items-center justify-center">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div v-if="branches.data.length === 0" class="py-12 text-center">
                        <div class="inline-flex p-4 rounded-full bg-secondary/30 text-muted-foreground mb-3">
                            <Building class="w-8 h-8 opacity-20" />
                        </div>
                        <h3 class="text-sm font-black text-primary uppercase">{{ ui.no_branches_found || 'No branches found.' }}</h3>
                    </div>
                </div>
            </div>

            <div class="p-4 lg:p-6 border-t border-secondary/50">
                <Pagination :links="branches.links" />
            </div>
        </Card>

        <!-- CRUD Modal -->
        <Modal :show="showModal" :title="editingBranch ? (ui.edit_branch || 'Edit Branch') : (ui.add_new_branch || 'Add New Branch')" @close="showModal = false">
            <form @submit.prevent="submit" class="space-y-4 lg:space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6">
                    <div class="space-y-1.5 lg:space-y-2 col-span-full">
                        <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.branch_name || 'Branch Name' }}</label>
                        <Input v-model="form.branchname" required class="h-10 lg:h-11 text-[13px] lg:text-sm" placeholder="Main Store" />
                    </div>

                    <div class="space-y-1.5 lg:space-y-2">
                        <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.province || 'Province' }}</label>
                        <select v-model="form.provincesid" class="w-full h-10 lg:h-11 rounded-xl border border-input bg-background text-[13px] lg:text-sm focus:ring-1 focus:ring-ring px-3" required>
                            <option value="" disabled>{{ ui.select_province || 'Select Province' }}</option>
                            <option v-for="p in provinces" :key="p.provinceid" :value="p.provinceid">{{ p.name }}</option>
                        </select>
                    </div>

                    <div class="space-y-1.5 lg:space-y-2">
                        <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.city || 'City' }}</label>
                        <select v-model="form.citiesid" class="w-full h-10 lg:h-11 rounded-xl border border-input bg-background text-[13px] lg:text-sm focus:ring-1 focus:ring-ring px-3" required :disabled="!cities.length">
                            <option value="" disabled>{{ ui.select_city || 'Select City' }}</option>
                            <option v-for="c in cities" :key="c.cityid" :value="c.cityid">{{ c.name }}</option>
                        </select>
                    </div>

                    <div class="space-y-1.5 lg:space-y-2">
                        <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.district || 'District' }}</label>
                        <select v-model="form.districtsid" class="w-full h-10 lg:h-11 rounded-xl border border-input bg-background text-[13px] lg:text-sm focus:ring-1 focus:ring-ring px-3" required :disabled="!districts.length">
                            <option value="" disabled>{{ ui.select_district || 'Select District' }}</option>
                            <option v-for="d in districts" :key="d.districtid" :value="d.districtid">{{ d.name }}</option>
                        </select>
                    </div>

                    <div class="space-y-1.5 lg:space-y-2">
                        <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.village || 'Village' }}</label>
                        <select v-model="form.villagesid" class="w-full h-10 lg:h-11 rounded-xl border border-input bg-background text-[13px] lg:text-sm focus:ring-1 focus:ring-ring px-3" required :disabled="!villages.length">
                            <option value="" disabled>{{ ui.select_village || 'Select Village' }}</option>
                            <option v-for="v in villages" :key="v.villageid" :value="v.villageid">{{ v.name }}</option>
                        </select>
                    </div>

                    <div class="space-y-1.5 lg:space-y-2 col-span-full">
                        <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.detail_address || 'Detail Address' }}</label>
                        <textarea v-model="form.detail_address" rows="2" class="w-full p-3 rounded-xl border border-input bg-background text-[13px] lg:text-sm focus:ring-1 focus:ring-ring" required></textarea>
                    </div>

                    <div class="space-y-1.5 lg:space-y-2">
                        <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.longitude || 'Longitude' }}</label>
                        <Input v-model="form.longitude" required class="h-10 lg:h-11 text-[13px] lg:text-sm" placeholder="101.44..." />
                    </div>

                    <div class="space-y-1.5 lg:space-y-2">
                        <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.latitude || 'Latitude' }}</label>
                        <Input v-model="form.latitude" required class="h-10 lg:h-11 text-[13px] lg:text-sm" placeholder="0.50..." />
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-end gap-3 pt-6 border-t border-secondary/50">
                    <Button type="button" variant="ghost" @click="showModal = false" class="rounded-xl font-bold order-2 sm:order-1">{{ ui.cancel || 'Cancel' }}</Button>
                    <Button type="submit" class="gap-2 px-8 h-12 lg:h-11 rounded-2xl lg:rounded-xl shadow-lg shadow-primary/20 order-1 sm:order-2" :disabled="form.processing">
                        <Save class="w-4 h-4" />
                        {{ editingBranch ? (ui.update_branch || 'Update Branch') : (ui.create_branch || 'Create Branch') }}
                    </Button>
                </div>
            </form>
        </Modal>

        <ConfirmationModal 
            :show="showConfirmModal" 
            :title="ui.delete_branch || 'Delete Branch'"
            :message="ui.confirm_delete_branch || 'Are you sure you want to delete this branch? This action cannot be undone.'"
            :impact="impactData"
            type="danger"
            :confirmText="ui.delete || 'Delete'"
            @close="showConfirmModal = false"
            @confirm="deleteBranch"
        />
    </div>
</template>
ate>
