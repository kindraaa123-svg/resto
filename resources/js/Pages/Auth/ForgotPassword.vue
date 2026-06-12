<script setup>
import { useForm, Head, Link, usePage } from '@inertiajs/vue3'
import Button from '@/Components/ui/Button.vue'
import Input from '@/Components/ui/Input.vue'
import Footer from '@/Components/Footer.vue'
import LanguageToggle from '@/Components/shared/LanguageToggle.vue'
import { Mail, ChevronLeft, Send } from 'lucide-vue-next'
import { computed } from 'vue'

const page = usePage()
const ui = computed(() => page.props.translations?.ui || {})

defineProps({
    status: String,
})

const form = useForm({
    email: '',
})

const submit = () => {
    form.post(route('password.email'))
}
</script>

<template>
    <div class="min-h-screen flex flex-col bg-background relative overflow-hidden">
        <Head :title="ui.forgot_password_title || 'Forgot Password'" />

        <!-- Floating Language Toggle -->
        <div class="fixed top-6 right-6 z-50">
            <LanguageToggle />
        </div>

        <div class="flex-1 flex items-center justify-center p-4 sm:p-8">
            <div class="w-full max-w-sm space-y-8 relative z-10">
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
                    <h2 class="text-3xl font-black tracking-tighter text-primary uppercase">{{ ui.forgot_password_title || 'Forgot Password' }}</h2>
                    <p class="text-xs font-medium text-muted-foreground uppercase tracking-widest leading-relaxed px-4">
                        {{ ui.forgot_password_msg || "Enter your email address and we'll send you a link to reset your password." }}
                    </p>
                </div>

                <div v-if="status" class="bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-xs font-bold text-center">
                    {{ status }}
                </div>

                <div class="space-y-6">
                    <form @submit.prevent="submit" class="space-y-5">
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground ml-1">
                                {{ ui.email_address || 'Email Address' }}
                            </label>
                            <Input 
                                v-model="form.email" 
                                type="email" 
                                placeholder="staff@pos.com"
                                class="h-12 bg-secondary/50 border-transparent focus:bg-white transition-all text-lg rounded-xl shadow-none"
                                required
                                autofocus
                                autocomplete="username"
                            />
                            <div v-if="form.errors.email" class="text-xs text-destructive mt-1 font-semibold">{{ form.errors.email }}</div>
                        </div>

                        <Button 
                            type="submit" 
                            class="w-full h-14 text-base font-black uppercase tracking-widest rounded-2xl shadow-lg shadow-primary/20 transition-all hover:scale-[1.02] active:scale-[0.98] gap-3"
                            :disabled="form.processing"
                        >
                            <Send class="w-5 h-5" />
                            <span v-if="form.processing">{{ ui.sending || 'Sending...' }}</span>
                            <span v-else>{{ ui.send_reset_link || 'Send Reset Link' }}</span>
                        </Button>
                    </form>

                    <div class="text-center">
                        <Link 
                            :href="route('login')"
                            class="inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground hover:text-primary transition-colors"
                        >
                            <ChevronLeft class="w-4 h-4" />
                            {{ ui.back_to_login || 'Back to Login' }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Decorative Background -->
        <div class="absolute top-[-10%] -left-[10%] w-[40%] h-[40%] bg-primary/5 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-[-10%] -right-[10%] w-[40%] h-[40%] bg-secondary rounded-full blur-3xl pointer-events-none"></div>

        <Footer />
    </div>
</template>
