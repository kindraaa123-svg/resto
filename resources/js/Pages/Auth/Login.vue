<script setup>
import { useForm, Head, Link, usePage } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import Button from '@/Components/ui/Button.vue'
import Input from '@/Components/ui/Input.vue'
import Card from '@/Components/ui/Card.vue'
import Footer from '@/Components/Footer.vue'
import LanguageToggle from '@/Components/shared/LanguageToggle.vue'
import LoginFaceModal from '@/Components/shared/LoginFaceModal.vue'
import { Lock, User, LogIn, Eye, EyeOff, Mail } from 'lucide-vue-next'
import axios from 'axios'

const page = usePage()
const props = defineProps({
    errors: Object,
    auth: Object,
    ziggy: Object,
    system: Object,
    translations: Object,
})

const ui = computed(() => page.props.translations?.ui || {})

const form = useForm({
    email: '',
    password: '',
    remember: false,
    latitude: null,
    longitude: null,
})

const showPassword = ref(false)
const showFaceModal = ref(false)
const isFaceVerified = ref(false)

const handleInitialLogin = async () => {
    if (form.processing) return
    
    // First, check if user needs face verification
    try {
        form.processing = true
        const res = await axios.post(route('api.login.check-face'), {
            email: form.email
        })
        
        if (res.data.needs_face && !isFaceVerified.value) {
            showFaceModal.value = true
            form.processing = false
            return
        }
        
        // If no face needed or already verified, proceed to location check
        getLocationAndSubmit()
    } catch (err) {
        console.error('Face check failed:', err)
        getLocationAndSubmit() // Fallback to normal login if API fails
    }
}

const onFaceApproved = () => {
    isFaceVerified.value = true
    showFaceModal.value = false
    getLocationAndSubmit()
}

const getLocationAndSubmit = () => {
    form.processing = true
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                form.latitude = position.coords.latitude
                form.longitude = position.coords.longitude
                // Save to localStorage so future Inertia requests can attach them via Axios headers
                localStorage.setItem('pos_user_lat', form.latitude)
                localStorage.setItem('pos_user_lng', form.longitude)
                submitForm()
            },
            (error) => {
                console.warn('Geolocation error:', error.message)
                submitForm()
            },
            { timeout: 5000, maximumAge: 60000 }
        )
    } else {
        submitForm()
    }
}

const submitForm = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-background p-6">
        <Head :title="ui.staff_login || 'Login Staff'" />

        <LoginFaceModal 
            :show="showFaceModal" 
            :email="form.email"
            @close="showFaceModal = false"
            @approved="onFaceApproved"
        />

        <div class="fixed top-8 right-8 z-50">
            <LanguageToggle />
        </div>

        <!-- Central Card -->
        <div class="w-full max-w-[400px] bg-white rounded-[24px] shadow-2xl shadow-primary/10 overflow-hidden flex flex-col">
            <!-- Gradient Header -->
            <div class="bg-gradient-to-r from-primary to-secondary py-8 flex flex-col items-center justify-center gap-3">
                <div class="w-20 h-20 bg-white rounded-2xl p-3 flex items-center justify-center shadow-lg transform hover:scale-105 transition-transform duration-300">
                    <img 
                        v-if="$page.props.system?.logo" 
                        :src="$page.props.system.logo" 
                        :alt="$page.props.system.name"
                        class="w-full h-full object-contain"
                    />
                    <LogIn v-else class="w-10 h-10 text-primary" />
                </div>
                <h1 class="text-xl font-black text-white tracking-tighter uppercase leading-none">
                    {{ $page.props.system?.name || 'POS System' }}
                </h1>
            </div>

            <!-- Form Content -->
            <div class="p-10 flex-1 flex flex-col">
                <form @submit.prevent="handleInitialLogin" class="space-y-6">
                    <!-- Email Input -->
                    <div class="relative">
                        <input 
                            v-model="form.email"
                            type="email"
                            :placeholder="ui.enter_email || 'Masukan Email'"
                            class="w-full h-[50px] px-6 rounded-full border border-border focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all text-sm outline-none"
                            required
                        />
                        <div v-if="form.errors.email" class="text-[10px] text-destructive mt-1 px-4 font-bold uppercase tracking-wider">{{ form.errors.email }}</div>
                    </div>

                    <!-- Password Input -->
                    <div class="relative">
                        <input 
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            :placeholder="ui.enter_password || 'Masukan Password'"
                            class="w-full h-[50px] px-6 rounded-full border border-border focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all text-sm outline-none"
                            required
                        />
                        <button 
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute right-5 top-[15px] text-muted-foreground hover:text-primary transition-colors"
                        >
                            <Eye v-if="!showPassword" class="w-5 h-5" />
                            <EyeOff v-else class="w-5 h-5" />
                        </button>
                        <div v-if="form.errors.password" class="text-[10px] text-destructive mt-1 px-4 font-bold uppercase tracking-wider">{{ form.errors.password }}</div>
                    </div>

                    <!-- Options -->
                    <div class="flex items-center justify-between px-2">
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input 
                                type="checkbox" 
                                v-model="form.remember"
                                class="rounded border-border text-primary focus:ring-primary/20 h-4 w-4 transition-all"
                            />
                            <span class="text-xs font-semibold text-muted-foreground group-hover:text-primary transition-colors">{{ ui.remember_me || 'Ingatkan saya' }}</span>
                        </label>

                        <Link 
                            :href="route('password.request')"
                            class="text-xs font-semibold text-primary/80 hover:text-primary transition-colors"
                        >
                            {{ ui.forgot_password || 'Lupa password?' }}
                        </Link>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        class="w-full h-[54px] bg-gradient-to-r from-primary to-secondary text-white font-bold rounded-full shadow-lg shadow-primary/20 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-2"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing" class="animate-spin h-5 w-5 border-2 border-white border-t-transparent rounded-full"></span>
                        <span>{{ ui.sign_in_now || 'Login' }}</span>
                    </button>
                </form>

                <!-- Footer Text -->
                <div class="mt-10 text-center space-y-1">
                    <p class="text-xs text-muted-foreground font-medium">
                        {{ ui.not_a_member || 'Belum menjadi anggota?' }} 
                        <span class="text-primary font-bold cursor-pointer hover:underline">Daftar Sekarang</span>
                    </p>
                </div>

                <!-- Alternative Methods (Icons only or subtle) -->
                <div class="mt-auto pt-8 flex items-center justify-center gap-6 border-t border-border/50">
                    <a :href="route('login.google')" title="Google Login" class="p-2 rounded-full hover:bg-muted transition-colors">
                        <svg class="w-5 h-5" viewBox="0 0 24 24">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.18 1-.78 1.85-1.63 2.51v2.08h2.64c1.55-1.42 2.43-3.52 2.43-5.6z" fill="#4285F4"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-2.64-2.08c-.5.33-1.14.53-2.14.53-1.85 0-3.41-1.25-3.97-2.93H7.82v2.1C8.96 20.48 10.37 23 12 23z" fill="#34A853"/>
                            <path d="M8.03 15.86c-.14-.42-.22-.87-.22-1.34s.08-.92.22-1.34V11.1H7.82C7.31 12.1 7 13.5 7 14.5s.31 2.4.82 3.4l.21-1.04z" fill="#FBBC05"/>
                            <path d="M12 5.07c1.62 0 3.07.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 10.37 1 8.96 3.52 7.82 5.58l2.53 1.96c.56-1.68 2.12-2.93 3.97-2.93z" fill="#EA4335"/>
                        </svg>
                    </a>
                    <Link :href="route('otp.login')" title="OTP Login" class="p-2 rounded-full hover:bg-muted text-primary transition-colors">
                        <Mail class="w-5 h-5" />
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
