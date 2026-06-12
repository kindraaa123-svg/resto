<script setup>
import { useForm, Head, Link, usePage } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import Button from '@/Components/ui/Button.vue'
import Input from '@/Components/ui/Input.vue'
import Footer from '@/Components/Footer.vue'
import LanguageToggle from '@/Components/shared/LanguageToggle.vue'
import { Mail, ChevronLeft, Send, ShieldCheck, ArrowRight } from 'lucide-vue-next'

const page = usePage()
const ui = computed(() => page.props.translations?.ui || {})

const step = ref('request') // 'request' or 'verify'

const requestForm = useForm({
    email: '',
})

const verifyForm = useForm({
    email: '',
    otp: '',
})

const requestOtp = () => {
    requestForm.post(route('otp.send'), {
        onSuccess: () => {
            verifyForm.email = requestForm.email
            step.value = 'verify'
        }
    })
}

const verifyOtp = () => {
    verifyForm.post(route('otp.verify'), {
        onFinish: () => verifyForm.reset('otp'),
    })
}
</script>

<template>
    <div class="min-h-screen flex flex-col bg-background relative overflow-hidden">
        <Head :title="ui.otp_login_title || 'OTP Login'" />

        <!-- Floating Language Toggle -->
        <div class="fixed top-6 right-6 z-50">
            <LanguageToggle />
        </div>

        <div class="flex-1 flex items-center justify-center p-4 sm:p-8">
            <div class="w-full max-w-sm space-y-8 relative z-10">
                
                <!-- Request OTP Step -->
                <div v-if="step === 'request'" class="space-y-8">
                    <div class="text-center space-y-2">
                        <div class="inline-flex items-center justify-center w-24 h-24 rounded-3xl bg-white border-2 border-primary/5 shadow-xl shadow-primary/5 mb-4 overflow-hidden transition-all duration-300 mx-auto">
                            <img 
                                v-if="$page.props.system?.logo" 
                                :src="$page.props.system.logo" 
                                :alt="$page.props.system.name"
                                class="w-full h-full object-cover"
                            />
                            <div v-else class="w-full h-full bg-primary/10 flex items-center justify-center text-primary">
                                <Mail class="w-10 h-10" />
                            </div>
                        </div>
                        <h2 class="text-3xl font-black tracking-tighter text-primary uppercase">{{ ui.otp_login_title || 'OTP Login' }}</h2>
                        <p class="text-xs font-medium text-muted-foreground uppercase tracking-widest leading-relaxed px-4">
                            {{ ui.otp_login_msg || 'Sign in using a temporary code sent to your email.' }}
                        </p>
                    </div>

                    <form @submit.prevent="requestOtp" class="space-y-5">
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">
                                {{ ui.email_address || 'Email Address' }}
                            </label>
                            <Input 
                                v-model="requestForm.email" 
                                type="email" 
                                placeholder="staff@pos.com"
                                class="h-12 bg-secondary/50 border-transparent focus:bg-white transition-all text-lg rounded-xl shadow-none"
                                required
                                autofocus
                            />
                            <div v-if="requestForm.errors.email" class="text-xs text-destructive mt-1 font-semibold">{{ requestForm.errors.email }}</div>
                        </div>

                        <Button 
                            type="submit" 
                            class="w-full h-14 text-base font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-primary/20 transition-all hover:scale-[1.02] active:scale-[0.98] gap-3"
                            :disabled="requestForm.processing"
                        >
                            <Send class="w-5 h-5" />
                            <span v-if="requestForm.processing">{{ ui.sending_code || 'Sending Code...' }}</span>
                            <span v-else>{{ ui.get_access_code || 'Get Access Code' }}</span>
                        </Button>
                    </form>
                </div>

                <!-- Verify OTP Step -->
                <div v-else class="space-y-8 animate-in fade-in slide-in-from-right-4 duration-500">
                    <div class="text-center space-y-2">
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-primary/10 text-primary mb-2 mx-auto">
                            <ShieldCheck class="w-10 h-10" />
                        </div>
                        <h2 class="text-3xl font-black tracking-tighter text-primary uppercase">{{ ui.verify_code || 'Verify Code' }}</h2>
                        <p class="text-xs font-medium text-muted-foreground uppercase tracking-widest leading-relaxed px-6">
                            {{ ui.verify_msg || 'Enter the 6-digit code we sent to' }} <span class="text-primary font-black lowercase">{{ verifyForm.email }}</span>
                        </p>
                    </div>

                    <form @submit.prevent="verifyOtp" class="space-y-5">
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1 text-center block w-full">
                                {{ ui.security_code_label || '6-Digit Security Code' }}
                            </label>
                            <Input 
                                v-model="verifyForm.otp" 
                                type="text" 
                                placeholder="000000"
                                maxlength="6"
                                class="h-16 bg-secondary/50 border-transparent focus:bg-white transition-all text-4xl text-center font-black tracking-[0.5em] rounded-2xl shadow-none"
                                required
                                autofocus
                            />
                            <div v-if="verifyForm.errors.otp" class="text-xs text-destructive mt-1 font-semibold text-center">{{ verifyForm.errors.otp }}</div>
                        </div>

                        <Button 
                            type="submit" 
                            class="w-full h-14 text-base font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-primary/20 transition-all hover:scale-[1.02] active:scale-[0.98] gap-3"
                            :disabled="verifyForm.processing"
                        >
                            <ArrowRight class="w-5 h-5" />
                            <span v-if="verifyForm.processing">{{ ui.verifying || 'Verifying...' }}</span>
                            <span v-else>{{ ui.verify_signin || 'Verify & Sign In' }}</span>
                        </Button>

                        <div class="text-center">
                            <button 
                                type="button" 
                                @click="step = 'request'"
                                class="text-[10px] font-black uppercase tracking-widest text-muted-foreground hover:text-primary transition-colors"
                            >
                                {{ ui.change_email || 'Change Email Address' }}
                            </button>
                        </div>
                    </form>
                </div>

                <div class="text-center pt-4 border-t border-secondary/50">
                    <Link 
                        :href="route('login')"
                        class="inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground hover:text-primary transition-colors"
                    >
                        <ChevronLeft class="w-4 h-4" />
                        {{ ui.back_to_password_login || 'Back to Password Login' }}
                    </Link>
                </div>

            </div>
        </div>

        <!-- Decorative Background -->
        <div class="absolute top-[-10%] -left-[10%] w-[40%] h-[40%] bg-primary/5 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-[-10%] -right-[10%] w-[40%] h-[40%] bg-secondary rounded-full blur-3xl pointer-events-none"></div>

        <Footer />
    </div>
</template>
