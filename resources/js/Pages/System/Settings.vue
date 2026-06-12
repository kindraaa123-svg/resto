<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import Card from '@/Components/ui/Card.vue'
import Input from '@/Components/ui/Input.vue'
import Button from '@/Components/ui/Button.vue'
import { 
    Save, 
    Image as ImageIcon, 
    MapPin, 
    Phone, 
    User, 
    Store,
    Upload,
    Crop
} from '@lucide/vue'
import { ref, computed } from 'vue'
import Modal from '@/Components/ui/Modal.vue'
import { Cropper } from 'vue-advanced-cropper'
import 'vue-advanced-cropper/dist/style.css'

defineOptions({ layout: AppLayout })

const page = usePage()
const props = defineProps({
    systemConfig: Object,
    logo_url: String,
    errors: Object,
    auth: Object,
    ziggy: Object,
    system: Object,
    locale: String,
    translations: Object,
})

const ui = computed(() => page.props.translations?.ui || {})

const form = useForm({
    systemname: props.systemConfig.systemname,
    systemaddress: props.systemConfig.systemaddress || '',
    systemmanager: props.systemConfig.systemmanager || '',
    systemcontact: props.systemConfig.systemcontact || '',
    logo: null,
})

const logoPreview = ref(props.logo_url)
const showCropModal = ref(false)
const rawImage = ref(null)
const cropperRef = ref(null)

const onFileChange = (e) => {
    const file = e.target.files[0]
    if (file) {
        const reader = new FileReader()
        reader.onload = (event) => {
            rawImage.value = event.target.result
            showCropModal.value = true
        }
        reader.readAsDataURL(file)
        // Reset input value to allow re-selecting same file
        e.target.value = ''
    }
}

const cropImage = () => {
    const { canvas } = cropperRef.value.getResult()
    // Use image/jpeg with 0.8 quality to significantly reduce file size
    canvas.toBlob((blob) => {
        const croppedFile = new File([blob], 'logo.jpg', { type: 'image/jpeg' })
        form.logo = croppedFile
        logoPreview.value = URL.createObjectURL(blob)
        showCropModal.value = false
    }, 'image/jpeg', 0.8)
}

const submit = () => {
    // We use post and spoof PUT for file uploads
    form.transform((data) => ({
        ...data,
        _method: 'PUT',
    })).post(route('system.settings.update'), {
        onSuccess: () => {
            // Success notification
        },
        forceFormData: true,
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
    <div class="max-w-7xl mx-auto space-y-6 lg:space-y-8 pb-20 px-2 lg:px-0">
        <Head :title="ui.system_settings || 'System Settings'" />

        <div>
            <h1 class="text-2xl lg:text-3xl font-black tracking-tight text-primary uppercase">{{ ui.system_settings || 'System Settings' }}</h1>
            <p class="text-muted-foreground text-[10px] lg:text-sm font-medium uppercase tracking-widest mt-1">{{ ui.identity_config || 'Application Identity Configuration' }}</p>
        </div>

        <form @submit.prevent="submit" class="space-y-6 lg:space-y-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
                <!-- Logo Section -->
                <div class="lg:col-span-1 space-y-4 lg:space-y-6">
                    <div class="space-y-1 lg:space-y-2">
                        <h2 class="text-sm lg:text-lg font-black text-primary uppercase tracking-tight">{{ ui.system_logo || 'System Logo' }}</h2>
                        <p class="text-[9px] lg:text-xs text-muted-foreground font-medium leading-relaxed">
                            {{ ui.logo_instruction || 'This logo will appear on the sidebar, login page, and printed reports. Use transparent PNG or JPG format.' }}
                        </p>
                    </div>
                    
                    <div class="flex flex-col items-center gap-4 p-4 lg:p-6 bg-card/50 rounded-[2rem] lg:rounded-2xl border-2 border-dashed border-secondary shadow-sm">
                        <div v-if="logoPreview" class="w-28 h-28 lg:w-32 lg:h-32 rounded-[1.5rem] lg:rounded-xl overflow-hidden bg-white flex items-center justify-center border border-secondary shadow-inner p-2">
                            <img :src="logoPreview" class="max-w-full max-h-full object-contain" />
                        </div>
                        <div v-else class="w-28 h-28 lg:w-32 lg:h-32 rounded-[1.5rem] lg:rounded-xl bg-secondary flex items-center justify-center text-muted-foreground">
                            <ImageIcon class="w-10 h-10 lg:w-12 lg:h-12" />
                        </div>
                        
                        <label class="cursor-pointer w-full sm:w-auto">
                            <input type="file" @change="onFileChange" class="hidden" accept="image/*" />
                            <div class="flex items-center justify-center gap-2 px-4 py-3 lg:py-2 w-full bg-primary text-primary-foreground text-[10px] lg:text-xs font-black uppercase tracking-widest rounded-xl lg:rounded-lg hover:scale-[1.02] transition-transform shadow-md shadow-primary/20">
                                <Upload class="w-3.5 h-3.5 lg:w-3 lg:h-3" />
                                {{ ui.choose_file || 'Choose File' }}
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Form Section -->
                <div class="lg:col-span-2 space-y-6">
                    <Card class="border-none shadow-sm bg-card/50">
                        <div class="grid grid-cols-1 gap-4 lg:gap-6 p-4 lg:p-6">
                            <div class="space-y-1.5 lg:space-y-2">
                                <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.business_name || 'Application / Business Name' }}</label>
                                <div class="relative">
                                    <Store class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                                    <Input v-model="form.systemname" class="pl-10 h-11 bg-background text-[13px] lg:text-sm" placeholder="e.g. Joyi Cafe" />
                                </div>
                                <div v-if="form.errors.systemname" class="text-xs text-destructive font-bold">{{ form.errors.systemname }}</div>
                            </div>

                            <div class="space-y-1.5 lg:space-y-2">
                                <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.full_address || 'Full Address' }}</label>
                                <div class="relative">
                                    <MapPin class="absolute left-3 top-3.5 w-4 h-4 text-muted-foreground" />
                                    <textarea 
                                        v-model="form.systemaddress" 
                                        rows="3"
                                        class="w-full pl-10 pt-3 pb-2 rounded-xl border border-input bg-background text-[13px] lg:text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                        :placeholder="ui.address_placeholder || 'Enter physical address or maps link...'"
                                    ></textarea>
                                </div>
                                <div v-if="form.errors.systemaddress" class="text-xs text-destructive font-bold">{{ form.errors.systemaddress }}</div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6">
                                <div class="space-y-1.5 lg:space-y-2">
                                    <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.manager_owner || 'Manager / Owner' }}</label>
                                    <div class="relative">
                                        <User class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                                        <Input v-model="form.systemmanager" class="pl-10 h-11 bg-background text-[13px] lg:text-sm" />
                                    </div>
                                    <div v-if="form.errors.systemmanager" class="text-xs text-destructive font-bold">{{ form.errors.systemmanager }}</div>
                                </div>

                                <div class="space-y-1.5 lg:space-y-2">
                                    <label class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">{{ ui.contact_person || 'Contact Person' }}</label>
                                    <div class="relative">
                                        <Phone class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                                        <Input v-model="form.systemcontact" class="pl-10 h-11 bg-background text-[13px] lg:text-sm" placeholder="08..." />
                                    </div>
                                    <div v-if="form.errors.systemcontact" class="text-xs text-destructive font-bold">{{ form.errors.systemcontact }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end p-4 lg:p-6 border-t border-secondary/50">
                            <Button type="submit" class="w-full sm:w-auto gap-2 px-8 lg:px-12 h-12 lg:h-12 rounded-2xl text-[11px] lg:text-sm font-black uppercase tracking-widest shadow-lg shadow-primary/20" :disabled="form.processing">
                                <Save class="w-4 h-4 lg:w-5 lg:h-5" />
                                {{ ui.save_settings || 'Save Settings' }}
                            </Button>
                        </div>
                    </Card>
                </div>
            </div>
        </form>

        <!-- Crop Modal -->
        <Modal :show="showCropModal" :title="ui.crop_system_logo || 'Crop System Logo'" @close="showCropModal = false">
            <div class="space-y-4 lg:space-y-6">
                <div class="overflow-hidden rounded-2xl lg:rounded-xl border border-secondary bg-black/5">
                    <Cropper
                        ref="cropperRef"
                        class="h-[300px] lg:h-[400px]"
                        :src="rawImage"
                        :stencil-props="{
                            aspectRatio: 1/1
                        }"
                    />
                </div>
                <p class="text-[9px] lg:text-xs text-muted-foreground text-center italic px-2">
                    {{ ui.crop_logo_instruction || 'Adjust the frame to crop your logo. Square aspect ratio (1:1) is recommended.' }}
                </p>
            </div>
            
            <template #footer>
                <div class="flex flex-col sm:flex-row w-full gap-3">
                    <Button variant="ghost" @click="showCropModal = false" class="w-full sm:w-auto rounded-xl font-bold order-2 sm:order-1">{{ ui.cancel || 'Cancel' }}</Button>
                    <Button @click="cropImage" class="w-full sm:w-auto gap-2 px-8 h-11 lg:h-10 rounded-xl font-black uppercase tracking-widest text-[10px] lg:text-xs order-1 sm:order-2 shadow-lg shadow-primary/20 bg-primary text-primary-foreground">
                        <Crop class="w-4 h-4" />
                        {{ ui.crop_apply || 'Crop & Apply' }}
                    </Button>
                </div>
            </template>
        </Modal>
    </div>
</template>
