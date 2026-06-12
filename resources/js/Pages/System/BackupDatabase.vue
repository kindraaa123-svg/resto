<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import Card from '@/Components/ui/Card.vue'
import Button from '@/Components/ui/Button.vue'
import ConfirmationModal from '@/Components/ui/ConfirmationModal.vue'
import { 
    Download, 
    Upload, 
    RefreshCcw, 
    FileCode
} from '@lucide/vue'
import { ref, computed } from 'vue'

defineOptions({ layout: AppLayout })

const page = usePage()
const props = defineProps({
    errors: Object,
    auth: Object,
    ziggy: Object,
    system: Object,
    locale: String,
    translations: Object,
})

const ui = computed(() => page.props.translations?.ui || {})

const importForm = useForm({
    file: null,
})

const showConfirmModal = ref(false)
const confirmData = ref({
    title: '',
    message: '',
    type: 'warning',
    action: null
})

const onFileChange = (e) => {
    importForm.file = e.target.files[0]
}

const confirmImport = () => {
    if (!importForm.file) return
    confirmData.value = {
        title: ui.value.confirm_restore || 'Restore Database',
        message: ui.value.confirm_restore_message || 'Warning: This will overwrite current data. All current changes since the backup will be lost. Continue?',
        type: 'warning',
        action: () => importForm.post(route('system.backup.database.import'), {
            preserveScroll: true,
            onFinish: () => {
                showConfirmModal.value = false
                importForm.reset()
            }
        })
    }
    showConfirmModal.value = true
}
</script>

<template>
    <Head :title="ui.database_management || 'Database Management'" />

    <div class="max-w-7xl mx-auto space-y-6 lg:space-y-8 pb-20">
        <div class="px-2 lg:px-0">
            <h1 class="text-2xl lg:text-3xl font-black tracking-tight text-primary uppercase">{{ ui.database_management || 'Database Management' }}</h1>
            <p class="text-muted-foreground text-[10px] lg:text-sm font-medium uppercase tracking-widest mt-1">{{ ui.backup_restore_maintenance || 'Backup, Restore & Maintenance' }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-8 px-2 lg:px-0">
            <!-- Export Section -->
            <Card class="border-none shadow-sm bg-card/50 flex flex-col justify-between">
                <template #header>
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-primary/10 rounded-lg text-primary">
                            <Download class="w-5 h-5" />
                        </div>
                        <h2 class="text-base lg:text-lg font-black text-primary uppercase">{{ ui.export_database || 'Export Database' }}</h2>
                    </div>
                </template>
                <div class="space-y-4 p-4 lg:p-6">
                    <p class="text-[10px] lg:text-xs text-muted-foreground font-medium leading-relaxed">
                        {{ ui.export_instruction || 'Download a full SQL dump of your current database. Recommended before performing any major updates or resets.' }}
                    </p>
                </div>
                <template #footer>
                    <div class="p-4 lg:p-6 pt-0">
                        <a 
                            :href="route('system.backup.database.export')" 
                            class="w-full inline-flex items-center justify-center gap-2 h-11 lg:h-12 bg-primary text-primary-foreground font-black uppercase tracking-widest rounded-xl hover:scale-[1.02] transition-all text-[10px] lg:text-xs"
                        >
                            <FileCode class="w-4 h-4" />
                            {{ ui.download_sql || 'Download SQL' }}
                        </a>
                    </div>
                </template>
            </Card>

            <!-- Import Section -->
            <Card class="border-none shadow-sm bg-card/50 flex flex-col justify-between">
                <template #header>
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-primary/10 rounded-lg text-primary">
                            <Upload class="w-5 h-5" />
                        </div>
                        <h2 class="text-base lg:text-lg font-black text-primary uppercase">{{ ui.import_database || 'Import Database' }}</h2>
                    </div>
                </template>
                <div class="space-y-4 p-4 lg:p-6">
                    <p class="text-[10px] lg:text-xs text-muted-foreground font-medium leading-relaxed">
                        {{ ui.import_instruction || 'Restore data from a previously exported SQL file. Note that this will overwrite existing records.' }}
                    </p>
                    <div class="relative">
                        <input 
                            type="file" 
                            @change="onFileChange" 
                            class="w-full text-xs text-muted-foreground file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:lg:text-xs file:font-black file:bg-primary file:text-primary-foreground hover:file:bg-primary/90 cursor-pointer"
                            accept=".sql"
                        />
                    </div>
                </div>
                <template #footer>
                    <div class="p-4 lg:p-6 pt-0">
                        <Button 
                            @click="confirmImport"
                            class="w-full h-11 lg:h-12 rounded-xl uppercase font-black tracking-widest text-[10px] lg:text-xs"
                            :disabled="!importForm.file || importForm.processing"
                        >
                            <RefreshCcw v-if="importForm.processing" class="w-4 h-4 animate-spin" />
                            <span v-else>{{ ui.restore_data || 'Restore Data' }}</span>
                        </Button>
                    </div>
                </template>
            </Card>
        </div>

        <!-- Confirmation Modal -->
        <ConfirmationModal 
            :show="showConfirmModal" 
            :title="confirmData.title"
            :message="confirmData.message"
            :type="confirmData.type"
            @close="showConfirmModal = false"
            @confirm="confirmData.action"
        />
    </div>
</template>
