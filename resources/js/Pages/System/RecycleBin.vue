<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import Pagination from '@/Components/ui/Pagination.vue'
import ConfirmationModal from '@/Components/ui/ConfirmationModal.vue'
import { 
    Trash2, RotateCcw, Search, Filter, Info, 
    User, Utensils, Tag, Package, Building, 
    Database, ClipboardList, BookOpen, Plus,
    DollarSign, Wallet, Layers
} from '@lucide/vue'
import { ref, computed, watch } from 'vue'

defineOptions({ layout: AppLayout })

const page = usePage()
const props = defineProps({
    items: Object,
    currentType: String,
    types: Array,
})

const ui = computed(() => page.props.translations?.ui || {})

const showConfirmModal = ref(false)
const itemToDelete = ref(null)
const isPermanent = ref(false)

const getTypeIcon = (type) => {
    switch (type) {
        case 'user': return User
        case 'menu': return Utensils
        case 'category': return Tag
        case 'ingredient': return Package
        case 'package': return Package
        case 'promotion': return Tag
        case 'item': return Layers
        case 'branch': return Building
        case 'table': return Database
        case 'addon': return Plus
        case 'operational': return Wallet
        case 'payroll': return DollarSign
        default: return Info
    }
}

const formatType = (type) => {
    return type.charAt(0).toUpperCase() + type.slice(1)
}

const changeType = (type) => {
    router.get(route('system.recycle-bin.index'), { type }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    })
}

const restoreItem = (id) => {
    router.post(route('system.recycle-bin.restore', { type: props.currentType, id }), {}, {
        onSuccess: () => {
            // Success notification if needed
        }
    })
}

const confirmDelete = (id, permanent = false) => {
    itemToDelete.value = id
    isPermanent.value = permanent
    showConfirmModal.value = true
}

const deleteItem = () => {
    if (itemToDelete.value) {
        if (isPermanent.value) {
            router.post(route('system.recycle-bin.force-delete', { type: props.currentType, id: itemToDelete.value }), {}, {
                onFinish: () => {
                    showConfirmModal.value = false
                    itemToDelete.value = null
                }
            })
        }
    }
}

const formatDate = (dateString) => {
    if (!dateString) return '—'
    return new Date(dateString).toLocaleString(page.props.locale === 'id' ? 'id-ID' : 'en-US', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
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
    <div class="max-w-7xl mx-auto space-y-6 lg:space-y-8 pb-20">
        <Head :title="ui.recycle_bin || 'Recycle Bin'" />

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-2 lg:px-0">
            <div>
                <h1 class="text-2xl lg:text-3xl font-black tracking-tight text-primary uppercase">{{ ui.recycle_bin || 'Recycle Bin' }}</h1>
                <p class="text-muted-foreground text-[10px] lg:text-sm font-medium uppercase tracking-widest mt-1">{{ ui.manage_deleted_records || 'Restore or Permanently Delete Records' }}</p>
            </div>
        </div>

        <!-- Type Selector Tabs -->
        <div class="flex flex-wrap gap-2 overflow-x-auto pb-2 no-scrollbar px-2 lg:px-0">
            <button 
                v-for="type in types" 
                :key="type"
                @click="changeType(type)"
                :class="[
                    'flex items-center gap-2 px-3 py-2 lg:px-4 lg:py-2.5 rounded-xl text-[10px] lg:text-xs font-black uppercase tracking-widest transition-all whitespace-nowrap',
                    currentType === type 
                        ? 'bg-primary text-primary-foreground shadow-lg shadow-primary/20 scale-105' 
                        : 'bg-card border border-secondary text-muted-foreground hover:border-primary/20 hover:text-primary'
                ]"
            >
                <component :is="getTypeIcon(type)" class="w-3.5 h-3.5 lg:w-4 lg:h-4" />
                {{ ui[type] || formatType(type) }}
            </button>
        </div>

        <Card class="border-none shadow-sm bg-card/50 overflow-hidden mx-2 lg:mx-0">
            <!-- Desktop Table -->
            <div v-if="!isMobileView" class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-secondary/20 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground">
                            <th class="px-6 py-4">{{ ui.name || 'Name / Description' }}</th>
                            <th class="px-6 py-4">{{ ui.additional_info || 'Additional Info' }}</th>
                            <th class="px-6 py-4">{{ ui.deleted_at || 'Deleted At' }}</th>
                            <th class="px-6 py-4">{{ ui.deleted_by || 'Deleted By' }}</th>
                            <th class="px-6 py-4 text-right">{{ ui.actions || 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-secondary/30">
                        <tr v-for="item in items.data" :key="item.id" class="group hover:bg-white/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary font-black uppercase text-xs">
                                        <component :is="getTypeIcon(currentType)" class="w-5 h-5" />
                                    </div>
                                    <span class="font-black text-primary uppercase tracking-tight text-sm">
                                        {{ item.name }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs font-bold text-muted-foreground">{{ item.info }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-[11px] font-bold text-muted-foreground uppercase">{{ formatDate(item.deleted_at) }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-secondary flex items-center justify-center text-[8px] font-black text-primary">
                                        {{ item.deleted_by.charAt(0) }}
                                    </div>
                                    <span class="text-[10px] font-black text-primary uppercase tracking-tight">{{ item.deleted_by }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button 
                                        @click="restoreItem(item.id)" 
                                        class="p-2.5 bg-green-500/10 text-green-600 hover:bg-green-500 hover:text-white rounded-xl transition-all shadow-sm"
                                        :title="ui.restore || 'Restore'"
                                    >
                                        <RotateCcw class="w-4 h-4" />
                                    </button>
                                    <button 
                                        @click="confirmDelete(item.id, true)" 
                                        class="p-2.5 bg-destructive/10 text-destructive hover:bg-destructive hover:text-white rounded-xl transition-all shadow-sm"
                                        :title="ui.delete_permanent || 'Delete Permanently'"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="items.data.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-muted-foreground italic text-sm">
                                {{ ui.no_trashed_items || 'Recycle bin is empty for this category.' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div v-else class="bg-secondary/5">
                <div class="divide-y divide-secondary/30 p-2 space-y-2">
                    <div v-for="item in items.data" :key="item.id" class="bg-white border border-secondary/50 rounded-3xl p-4 shadow-sm relative overflow-hidden flex flex-col gap-4">
                        <div class="flex items-start gap-3">
                            <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary font-black shrink-0">
                                <component :is="getTypeIcon(currentType)" class="w-6 h-6" />
                            </div>
                            <div class="space-y-1 w-full min-w-0">
                                <h3 class="text-sm font-black text-primary uppercase tracking-tight leading-tight truncate">{{ item.name }}</h3>
                                <span class="text-[10px] font-bold text-muted-foreground block truncate">{{ item.info }}</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-secondary/10 rounded-2xl border border-secondary/20">
                            <div class="flex flex-col gap-1">
                                <span class="text-[8px] font-black uppercase tracking-widest text-muted-foreground">{{ ui.deleted_at || 'Deleted At' }}</span>
                                <span class="text-[9px] font-bold text-primary uppercase">{{ formatDate(item.deleted_at) }}</span>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                <span class="text-[8px] font-black uppercase tracking-widest text-muted-foreground">{{ ui.deleted_by || 'Deleted By' }}</span>
                                <div class="flex items-center gap-1.5">
                                    <div class="w-4 h-4 rounded-full bg-secondary flex items-center justify-center text-[7px] font-black text-primary">
                                        {{ item.deleted_by.charAt(0) }}
                                    </div>
                                    <span class="text-[9px] font-black text-primary uppercase tracking-tight">{{ item.deleted_by }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-2 pt-2 border-t border-secondary/30">
                            <button @click="restoreItem(item.id)" class="flex-1 p-3 bg-green-50 text-green-600 rounded-xl transition-all active:scale-95 border border-green-200 flex items-center justify-center gap-2">
                                <RotateCcw class="w-4 h-4" />
                                <span class="text-[10px] font-black uppercase tracking-widest">{{ ui.restore || 'Restore' }}</span>
                            </button>
                            <button @click="confirmDelete(item.id, true)" class="p-3 bg-destructive/10 text-destructive rounded-xl transition-all active:scale-95 border border-destructive/20 flex items-center justify-center">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div v-if="items.data.length === 0" class="py-12 text-center">
                        <div class="inline-flex p-4 rounded-full bg-secondary/30 text-muted-foreground mb-3">
                            <Trash2 class="w-8 h-8 opacity-20" />
                        </div>
                        <h3 class="text-sm font-black text-primary uppercase">{{ ui.no_trashed_items || 'Recycle bin is empty.' }}</h3>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div class="p-4 lg:p-6 border-t border-secondary/50">
                <Pagination :links="items.links" />
            </div>
        </Card>

        <ConfirmationModal 
            :show="showConfirmModal" 
            :title="ui.confirm_permanent_delete || 'Permanent Delete'"
            :message="ui.permanent_delete_warning || 'Are you sure you want to permanently delete this item? This action cannot be undone.'"
            type="danger"
            :confirmText="ui.delete_permanent || 'Delete Permanently'"
            @close="showConfirmModal = false"
            @confirm="deleteItem"
        />
    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
