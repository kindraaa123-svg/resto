<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { 
    AlertTriangle, 
    FileQuestion, 
    Timer, 
    ShieldAlert, 
    Zap,
    Home,
    RotateCcw
} from '@lucide/vue'

const props = defineProps({
  status: Number,
})

const title = computed(() => {
  return {
    503: '503: Service Unavailable',
    500: '500: Server Error',
    429: '429: Too Many Requests',
    419: '419: Page Expired',
    404: '404: Page Not Found',
    403: '403: Forbidden',
    401: '401: Unauthorized',
  }[props.status] || 'Error'
})

const description = computed(() => {
  return {
    503: 'Sorry, we are doing some maintenance. Please check back soon.',
    500: 'Whoops, something went wrong on our servers.',
    429: 'Too many requests. Please slow down and try again later.',
    419: 'Sorry, your session has expired. Please refresh and try again.',
    404: 'Sorry, the page you are looking for could not be found.',
    403: 'Sorry, you are forbidden from accessing this page.',
    401: 'Please log in to access this page.',
  }[props.status] || 'An unexpected error has occurred.'
})

const icon = computed(() => {
  return {
    503: Zap,
    500: ShieldAlert,
    429: Zap,
    419: Timer,
    404: FileQuestion,
    403: ShieldAlert,
    401: ShieldAlert,
  }[props.status] || AlertTriangle
})

const reloadPage = () => {
    window.location.reload()
}
</script>

<template>
  <Head :title="title" />

  <div class="min-h-screen bg-background flex flex-col justify-center items-center px-6 py-12 relative overflow-hidden">
    <!-- Background patterns -->
    <div class="absolute inset-0 z-0 opacity-[0.03] pointer-events-none">
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-primary rounded-full blur-[120px]"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-primary rounded-full blur-[120px]"></div>
    </div>

    <div class="max-w-md w-full text-center space-y-10 z-10">
      <!-- Icon Container -->
      <div class="relative mx-auto w-32 h-32">
          <div class="absolute inset-0 bg-primary/10 rounded-[2.5rem] rotate-6 transform scale-110"></div>
          <div class="absolute inset-0 bg-secondary rounded-[2.5rem] -rotate-3 transform"></div>
          <div class="relative w-full h-full bg-primary rounded-[2.5rem] flex items-center justify-center text-primary-foreground shadow-2xl shadow-primary/20">
              <component :is="icon" class="w-14 h-14" stroke-width="2.5" />
          </div>
      </div>

      <div class="space-y-4">
        <h1 class="text-5xl font-black tracking-tighter text-primary uppercase leading-tight sm:text-6xl">
          {{ title.split(':')[0] }}
        </h1>
        <h2 class="text-xl font-bold text-foreground/80 uppercase tracking-widest">
            {{ title.split(':')[1]?.trim() || 'Oops!' }}
        </h2>
        <div class="h-1 w-20 bg-primary/20 mx-auto rounded-full"></div>
        <p class="text-sm font-bold text-muted-foreground uppercase tracking-widest leading-relaxed max-w-[280px] mx-auto">
          {{ description }}
        </p>
      </div>

      <div class="pt-6 flex flex-col sm:flex-row items-center justify-center gap-4">
        <Link
          href="/"
          class="w-full sm:w-auto flex items-center justify-center gap-3 rounded-2xl bg-primary px-8 py-4 text-xs font-black uppercase tracking-[0.2em] text-primary-foreground shadow-lg shadow-primary/20 hover:scale-105 active:scale-95 transition-all duration-300"
        >
          <Home class="w-4 h-4" />
          Go to Home
        </Link>
        
        <button
          v-if="status === 419 || status >= 500"
          @click="reloadPage"
          class="w-full sm:w-auto flex items-center justify-center gap-3 rounded-2xl bg-card border border-secondary px-8 py-4 text-xs font-black uppercase tracking-[0.2em] text-primary hover:bg-secondary transition-all duration-300"
        >
          <RotateCcw class="w-4 h-4" />
          Refresh Page
        </button>
      </div>
    </div>
    
    <!-- Global Footer -->
    <footer class="absolute bottom-0 w-full py-8 px-8 border-t border-secondary/50 flex flex-col items-center justify-center gap-4 bg-white/20 backdrop-blur-sm z-10">
        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground text-center">
            &copy; {{ new Date().getFullYear() }} {{ $page.props.system?.name || 'POS System' }} &bull; All Rights Reserved
        </p>
    </footer>
  </div>
</template>
