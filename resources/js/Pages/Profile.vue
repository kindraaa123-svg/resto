<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, useForm, usePage, router } from '@inertiajs/vue3'
import Card from '@/Components/ui/Card.vue'
import Input from '@/Components/ui/Input.vue'
import Button from '@/Components/ui/Button.vue'
import { User, Mail, Phone, Lock, Save, ShieldCheck, Camera, CameraOff, CheckCircle, Smartphone } from '@lucide/vue'
import { computed, ref, onUnmounted, nextTick } from 'vue'
import * as faceapi from 'face-api.js'
import axios from 'axios'
import { cn } from '@/lib/utils'

defineOptions({ layout: AppLayout })

const page = usePage()
const user = page.props.auth.user
const ui = computed(() => page.props.translations?.ui || {})

const activeTab = ref('account') // 'account' or 'security'

const profileForm = useForm({
    name: user.name,
    email: user.email,
    phonenumber: user.phonenumber || '',
})

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
})

const updateProfile = () => {
    profileForm.put(route('user-profile-information.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Optional toast or success indicator
        }
    })
}

const updatePassword = () => {
    passwordForm.put(route('user-password.update'), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    })
}

// Face Enrollment Logic
const videoEl = ref(null)
const isCameraActive = ref(false)
const isModelsLoaded = ref(false)
const enrollmentStatus = ref('')
const hasFaceData = ref(!!user.face_descriptor)
let stream = null

const startCamera = async () => {
    enrollmentStatus.value = 'Loading AI Models...'
    try {
        if (!isModelsLoaded.value) {
            const modelPath = '/models' 
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(modelPath),
                faceapi.nets.faceLandmark68Net.loadFromUri(modelPath),
                faceapi.nets.faceRecognitionNet.loadFromUri(modelPath)
            ])
            isModelsLoaded.value = true
        }
        
        enrollmentStatus.value = 'Starting camera...'
        stream = await navigator.mediaDevices.getUserMedia({ video: true })
        isCameraActive.value = true
        
        await nextTick() 
        
        if (videoEl.value) {
            videoEl.value.srcObject = stream
            enrollmentStatus.value = 'Look at the camera and click Scan'
        } else {
            throw new Error('Video element not found in DOM')
        }
    } catch (err) {
        console.error(err)
        enrollmentStatus.value = 'Camera error: ' + err.message
    }
}

const stopCamera = () => {
    if (stream) {
        stream.getTracks().forEach(track => track.stop())
        stream = null
    }
    isCameraActive.value = false
    enrollmentStatus.value = ''
}

const scanFace = async () => {
    if (!videoEl.value) return
    
    enrollmentStatus.value = 'Detecting face...'
    
    const detection = await faceapi.detectSingleFace(videoEl.value, new faceapi.TinyFaceDetectorOptions())
                                .withFaceLandmarks()
                                .withFaceDescriptor()
                                
    if (detection) {
        enrollmentStatus.value = 'Face detected! Saving...'
        
        try {
            await axios.post(route('profile.face.update'), {
                face_descriptor: Array.from(detection.descriptor)
            })
            
            hasFaceData.value = true
            enrollmentStatus.value = 'Face data saved successfully!'
            stopCamera()
            router.reload({ only: ['auth'] })
        } catch (err) {
            enrollmentStatus.value = 'Failed to save face data.'
            console.error(err)
        }
    } else {
        enrollmentStatus.value = 'No face detected. Try moving closer or adjusting lighting.'
    }
}

onUnmounted(() => {
    stopCamera()
})

</script>

<template>
    <Head :title="ui.my_profile || 'My Profile'" />

    <div class="max-w-7xl mx-auto pb-20 px-4 lg:px-0">
        <!-- Header -->
        <div class="mb-12">
            <h1 class="text-3xl font-black tracking-tight text-primary uppercase">{{ ui.my_profile || 'My Profile' }}</h1>
            <p class="text-muted-foreground text-sm font-medium uppercase tracking-widest mt-1">{{ ui.manage_account_info || 'Manage your account information and security' }}</p>
        </div>

        <!-- Tab Switcher -->
        <div class="flex bg-secondary/30 p-1.5 rounded-2xl w-fit mb-10 border border-secondary/50">
            <button 
                @click="activeTab = 'account'"
                :class="cn(
                    'px-8 py-3 rounded-xl text-xs font-black uppercase tracking-[0.2em] transition-all flex items-center gap-3',
                    activeTab === 'account' ? 'bg-white text-primary shadow-xl shadow-primary/5' : 'text-muted-foreground hover:text-primary'
                )"
            >
                <User class="w-4 h-4" />
                Account
            </button>
            <button 
                @click="activeTab = 'security'"
                :class="cn(
                    'px-8 py-3 rounded-xl text-xs font-black uppercase tracking-[0.2em] transition-all flex items-center gap-3',
                    activeTab === 'security' ? 'bg-white text-primary shadow-xl shadow-primary/5' : 'text-muted-foreground hover:text-primary'
                )"
            >
                <ShieldCheck class="w-4 h-4" />
                Security
            </button>
        </div>

        <!-- Account Tab -->
        <div v-if="activeTab === 'account'" class="animate-in fade-in slide-in-from-bottom-4 duration-500">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-1 space-y-4">
                    <div class="p-6 bg-primary/5 rounded-3xl border-2 border-dashed border-primary/20">
                        <h2 class="text-lg font-black text-primary uppercase tracking-tight">{{ ui.profile_information || 'Personal Details' }}</h2>
                        <p class="text-xs text-muted-foreground font-medium leading-relaxed mt-2 uppercase tracking-wide">
                            {{ ui.profile_instruction || 'Update your profile information and contact details.' }}
                        </p>
                    </div>
                </div>

                <Card class="lg:col-span-2 border-none shadow-2xl shadow-primary/5 bg-card overflow-hidden">
                    <form @submit.prevent="updateProfile" class="space-y-8 p-8">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground ml-1">{{ ui.full_name || 'Full Name' }}</label>
                            <div class="relative group">
                                <User class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground transition-colors group-focus-within:text-primary" />
                                <Input v-model="profileForm.name" class="pl-12 h-14 bg-secondary/10 border-none rounded-2xl font-bold text-primary focus:ring-4 focus:ring-primary/10" />
                            </div>
                            <div v-if="profileForm.errors.name" class="text-[10px] text-destructive font-black uppercase tracking-widest ml-1">{{ profileForm.errors.name }}</div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground ml-1">{{ ui.email_address || 'Email Address' }}</label>
                                <div class="relative group">
                                    <Mail class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground transition-colors group-focus-within:text-primary" />
                                    <Input v-model="profileForm.email" type="email" class="pl-12 h-14 bg-secondary/10 border-none rounded-2xl font-bold text-primary focus:ring-4 focus:ring-primary/10" />
                                </div>
                                <div v-if="profileForm.errors.email" class="text-[10px] text-destructive font-black uppercase tracking-widest ml-1">{{ profileForm.errors.email }}</div>
                            </div>

                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground ml-1">{{ ui.phone_number || 'Phone Number' }}</label>
                                <div class="relative group">
                                    <Smartphone class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground transition-colors group-focus-within:text-primary" />
                                    <Input v-model="profileForm.phonenumber" type="tel" placeholder="08..." class="pl-12 h-14 bg-secondary/10 border-none rounded-2xl font-bold text-primary focus:ring-4 focus:ring-primary/10" />
                                </div>
                                <div v-if="profileForm.errors.phonenumber" class="text-[10px] text-destructive font-black uppercase tracking-widest ml-1">{{ profileForm.errors.phonenumber }}</div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-8 border-t border-secondary/50">
                            <Button type="submit" class="w-full sm:w-auto gap-3 px-10 h-14 rounded-2xl shadow-xl shadow-primary/20 font-black tracking-widest text-xs uppercase" :disabled="profileForm.processing">
                                <Save class="w-5 h-5" />
                                {{ ui.save_changes || 'Update Account' }}
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>
        </div>

        <!-- Security Tab -->
        <div v-else class="animate-in fade-in slide-in-from-bottom-4 duration-500 space-y-12">
            <!-- Password Change Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-1 space-y-4">
                    <div class="p-6 bg-amber-500/5 rounded-3xl border-2 border-dashed border-amber-500/20">
                        <h2 class="text-lg font-black text-amber-600 uppercase tracking-tight">{{ ui.security || 'Password Security' }}</h2>
                        <p class="text-xs text-muted-foreground font-medium leading-relaxed mt-2 uppercase tracking-wide">
                            {{ ui.security_instruction || 'Ensure your account stays secure by using a strong, unique password.' }}
                        </p>
                    </div>
                </div>

                <Card class="lg:col-span-2 border-none shadow-2xl shadow-amber-500/5 bg-card overflow-hidden">
                    <form @submit.prevent="updatePassword" class="space-y-8 p-8">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground ml-1">{{ ui.current_password || 'Current Password' }}</label>
                            <Input v-model="passwordForm.current_password" type="password" class="h-14 bg-secondary/10 border-none rounded-2xl font-bold text-primary focus:ring-4 focus:ring-primary/10" autocomplete="current-password" />
                            <div v-if="passwordForm.errors.current_password" class="text-[10px] text-destructive font-black uppercase tracking-widest ml-1">{{ passwordForm.errors.current_password }}</div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground ml-1">{{ ui.new_password || 'New Password' }}</label>
                                <Input v-model="passwordForm.password" type="password" class="h-14 bg-secondary/10 border-none rounded-2xl font-bold text-primary focus:ring-4 focus:ring-primary/10" autocomplete="new-password" />
                                <div v-if="passwordForm.errors.password" class="text-[10px] text-destructive font-black uppercase tracking-widest ml-1">{{ passwordForm.errors.password }}</div>
                            </div>

                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground ml-1">{{ ui.confirm_password || 'Confirm Password' }}</label>
                                <Input v-model="passwordForm.password_confirmation" type="password" class="h-14 bg-secondary/10 border-none rounded-2xl font-bold text-primary focus:ring-4 focus:ring-primary/10" autocomplete="new-password" />
                                <div v-if="passwordForm.errors.password_confirmation" class="text-[10px] text-destructive font-black uppercase tracking-widest ml-1">{{ passwordForm.errors.password_confirmation }}</div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-8 border-t border-secondary/50">
                            <Button type="submit" variant="secondary" class="w-full sm:w-auto gap-3 px-10 h-14 rounded-2xl text-primary font-black uppercase tracking-widest text-xs shadow-lg shadow-primary/5" :disabled="passwordForm.processing">
                                <ShieldCheck class="w-5 h-5" />
                                {{ ui.update_password || 'Update Password' }}
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>

            <!-- Face Enrollment Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-1 space-y-4">
                    <div class="p-6 bg-blue-500/5 rounded-3xl border-2 border-dashed border-blue-500/20">
                        <h2 class="text-lg font-black text-blue-600 uppercase tracking-tight">Biometric Face ID</h2>
                        <p class="text-xs text-muted-foreground font-medium leading-relaxed mt-2 uppercase tracking-wide">
                            Register your face for lightning-fast biometric authentication across the system.
                        </p>
                    </div>
                </div>

                <Card class="lg:col-span-2 border-none shadow-2xl shadow-blue-500/5 bg-card overflow-hidden relative">
                    <div class="p-8 space-y-8">
                        <div class="flex items-center gap-6">
                            <div class="w-16 h-16 rounded-[1.5rem] flex items-center justify-center shadow-inner" :class="hasFaceData ? 'bg-green-500 text-white shadow-green-500/20' : 'bg-secondary/20 text-muted-foreground'">
                                <CheckCircle v-if="hasFaceData" class="w-8 h-8" />
                                <Camera v-else class="w-8 h-8" />
                            </div>
                            <div>
                                <h3 class="font-black text-xl uppercase tracking-tight" :class="hasFaceData ? 'text-green-600' : 'text-primary'">
                                    {{ hasFaceData ? 'Biometrics Active' : 'No Face Data Found' }}
                                </h3>
                                <p class="text-[11px] font-bold text-muted-foreground uppercase tracking-widest mt-1 leading-relaxed">
                                    {{ hasFaceData ? 'Your biometric identity is securely stored and ready.' : 'Set up your face profile for secure hands-free login.' }}
                                </p>
                            </div>
                        </div>

                        <div v-if="isCameraActive" class="space-y-6">
                            <div class="relative max-w-sm mx-auto aspect-square bg-slate-900 rounded-[3.5rem] overflow-hidden shadow-2xl border-8 border-white group">
                                <video ref="videoEl" autoplay muted playsinline class="w-full h-full object-cover grayscale group-hover:grayscale-0 transition-all duration-700"></video>
                                
                                <!-- Scanner UI -->
                                <div class="absolute inset-0 pointer-events-none flex flex-col items-center justify-center">
                                    <div class="w-56 h-56 border-2 border-blue-400/30 rounded-full relative animate-pulse flex items-center justify-center">
                                        <div class="absolute -top-1 -left-1 w-10 h-10 border-t-8 border-l-8 border-blue-500 rounded-tl-[2rem]"></div>
                                        <div class="absolute -top-1 -right-1 w-10 h-10 border-t-8 border-r-8 border-blue-500 rounded-tr-[2rem]"></div>
                                        <div class="absolute -bottom-1 -left-1 w-10 h-10 border-b-8 border-l-8 border-blue-500 rounded-bl-[2rem]"></div>
                                        <div class="absolute -bottom-1 -right-1 w-10 h-10 border-b-8 border-r-8 border-blue-500 rounded-br-[2rem]"></div>
                                        
                                        <!-- Animated Scan Line -->
                                        <div class="w-48 h-1 bg-blue-500/80 shadow-[0_0_20px_rgba(59,130,246,0.8)] absolute top-0 animate-[scan-line_2s_ease-in-out_infinite]"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <p class="text-center text-[10px] font-black text-blue-600 uppercase tracking-[0.4em] animate-pulse">{{ enrollmentStatus }}</p>

                            <div class="flex justify-center gap-5">
                                <button @click="stopCamera" class="px-8 h-14 rounded-2xl bg-secondary/20 text-muted-foreground font-black uppercase tracking-widest text-[10px] hover:bg-secondary/40 transition-colors">
                                    Cancel
                                </button>
                                <button @click="scanFace" class="px-10 h-14 bg-blue-600 text-white rounded-2xl shadow-xl shadow-blue-500/20 font-black uppercase tracking-widest text-[10px] hover:bg-blue-500 transition-colors flex items-center gap-3">
                                    <Camera class="w-5 h-5" />
                                    Capture Face
                                </button>
                            </div>
                        </div>

                        <div v-show="!isCameraActive" class="flex justify-end pt-8 border-t border-secondary/50">
                            <button @click="startCamera" class="w-full sm:w-auto gap-3 px-10 h-14 rounded-2xl bg-[#043E54] text-white font-black uppercase tracking-widest text-[10px] shadow-2xl shadow-[#043E54]/20 hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center">
                                <Camera class="w-5 h-5" />
                                {{ hasFaceData ? 'Recalibrate Biometrics' : 'Start Enrollment' }}
                            </button>
                        </div>
                    </div>
                </Card>
            </div>
        </div>
    </div>
</template>

<style scoped>
video {
    transform: scaleX(-1);
    -webkit-transform: scaleX(-1);
}

@keyframes scan-line {
    0% { top: 10%; opacity: 0; }
    50% { opacity: 1; }
    100% { top: 90%; opacity: 0; }
}
</style>
