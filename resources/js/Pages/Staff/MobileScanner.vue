<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { Head } from '@inertiajs/vue3'
import { Camera, CameraOff, Smartphone, Wifi, CheckCircle2, QrCode, X, Scan, Zap, Activity } from '@lucide/vue'
import { Html5Qrcode } from 'html5-qrcode'
import axios from 'axios'

const pairingCode = ref(localStorage.getItem('staff_pairing_code') || Math.random().toString(36).substring(2, 8).toUpperCase())
const isConnected = ref(true) 
const showScanner = ref(false)
const scanSuccess = ref(false)
const lastScanned = ref('')

let html5QrScanner = null

const savePairingCode = () => {
    localStorage.setItem('staff_pairing_code', pairingCode.value)
    isConnected.value = !!pairingCode.value
}

onMounted(() => {
    if (!localStorage.getItem('staff_pairing_code')) {
        savePairingCode()
    }
    if (pairingCode.value) {
        isConnected.value = true
    }
})

const startCamera = async () => {
    showScanner.value = true
    
    await nextTick()
    
    try {
        html5QrScanner = new Html5Qrcode("staff-reader");
        const config = { 
            fps: 25, 
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0
        };
        
        await html5QrScanner.start(
            { facingMode: "environment" }, 
            config, 
            onScanSuccess
        );
    } catch (err) {
        console.error("Camera start failed", err)
        alert("Could not access camera. Please check permissions.")
        showScanner.value = false
    }
}

const onScanSuccess = async (decodedText) => {
    if (decodedText === lastScanned.value) return 
    
    lastScanned.value = decodedText
    
    try {
        await axios.post('/api/staff-scan/broadcast', {
            pairing_code: pairingCode.value,
            barcode: decodedText
        })
        
        scanSuccess.value = true
        if (window.navigator.vibrate) window.navigator.vibrate([40, 30, 40])
        
        setTimeout(() => {
            scanSuccess.value = false
        }, 1500)
    } catch (err) {
        console.error("Scan broadcast failed", err)
    }
}

const stopCamera = async () => {
    if (html5QrScanner) {
        try {
            await html5QrScanner.stop()
            html5QrScanner = null
        } catch (err) {
            console.error("Stop failed", err)
        }
    }
    showScanner.value = false
}

onUnmounted(() => {
    if (html5QrScanner) {
        html5QrScanner.stop().catch(() => {})
    }
})
</script>

<template>
    <div class="min-h-screen bg-[#020617] flex flex-col items-center justify-center p-6 text-center text-white overflow-hidden font-sans">
        <Head title="Staff Mobile Scanner" />

        <!-- Ambient Background -->
        <div class="fixed inset-0 pointer-events-none overflow-hidden">
            <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-blue-600/10 blur-[120px] rounded-full"></div>
            <div class="absolute -bottom-[10%] -right-[10%] w-[40%] h-[40%] bg-indigo-600/10 blur-[120px] rounded-full"></div>
        </div>

        <!-- Status Header -->
        <div class="fixed top-0 left-0 right-0 p-8 flex items-center justify-between z-20 backdrop-blur-md bg-black/5">
            <div class="flex items-center gap-4">
                <div class="relative">
                    <div :class="['w-2.5 h-2.5 rounded-full', isConnected ? 'bg-emerald-500 shadow-[0_0_15px_rgba(16,185,129,0.8)]' : 'bg-rose-500']"></div>
                    <div v-if="isConnected" class="absolute inset-0 rounded-full bg-emerald-500 animate-ping opacity-40"></div>
                </div>
                <span class="text-[9px] font-black uppercase tracking-[0.3em] text-slate-400">
                    {{ isConnected ? 'System Online' : 'Link Disconnected' }}
                </span>
            </div>
            <div class="flex items-center gap-4">
                <Activity class="w-4 h-4 text-slate-500" />
                <Wifi :class="['w-5 h-5 transition-colors duration-500', isConnected ? 'text-blue-500' : 'text-slate-700']" />
            </div>
        </div>

        <!-- Setup UI -->
        <div v-if="!showScanner" class="w-full max-w-md space-y-12 animate-in fade-in zoom-in duration-700 relative z-10">
            <div class="relative group">
                <div class="absolute inset-0 bg-blue-500/20 blur-[60px] rounded-full group-hover:bg-blue-500/30 transition-all duration-700"></div>
                <div class="w-36 h-36 bg-gradient-to-br from-blue-500/20 to-indigo-600/20 rounded-[3.5rem] flex items-center justify-center text-blue-400 mx-auto border border-blue-400/30 relative backdrop-blur-2xl">
                    <Scan class="w-16 h-16 group-hover:scale-110 transition-transform duration-500" />
                </div>
            </div>
            
            <div class="space-y-4">
                <h1 class="text-4xl font-black uppercase tracking-tight text-white leading-none">
                    Remote <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-indigo-400">Scanner</span>
                </h1>
                <p class="text-[11px] font-bold text-slate-500 max-w-[240px] mx-auto leading-relaxed uppercase tracking-[0.2em]">
                    Real-time data bridge to your POS terminal
                </p>
            </div>

            <div class="space-y-8">
                <div class="p-8 bg-slate-900/40 rounded-[2.5rem] border border-slate-800/50 backdrop-blur-2xl shadow-2xl">
                    <div class="flex items-center gap-2 mb-4 ml-2">
                        <Zap class="w-3 h-3 text-blue-400" />
                        <p class="text-[9px] font-black uppercase tracking-[0.3em] text-slate-500">Pairing Sequence</p>
                    </div>
                    <input 
                        v-model="pairingCode" 
                        @input="savePairingCode"
                        placeholder="ENTER CODE"
                        class="w-full bg-slate-950/50 border-2 border-slate-800 rounded-2xl py-5 text-center text-3xl font-black text-blue-400 uppercase tracking-[0.4em] focus:border-blue-500/50 focus:bg-slate-950 transition-all outline-none shadow-inner"
                    />
                </div>

                <button 
                    @click="startCamera"
                    class="group relative w-full overflow-hidden py-7 rounded-[2.5rem] transition-all active:scale-95"
                >
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-600 to-indigo-600 group-hover:from-blue-500 group-hover:to-indigo-500 transition-all"></div>
                    <div class="relative flex flex-col items-center justify-center gap-1">
                        <span class="text-[10px] font-black uppercase tracking-[0.3em] opacity-50 mb-1">Authorization Required</span>
                        <div class="flex items-center gap-4">
                            <Camera class="w-6 h-6 text-white" />
                            <span class="text-base font-black uppercase tracking-[0.2em]">Activate Camera</span>
                        </div>
                    </div>
                </button>
            </div>
        </div>

        <!-- Scanner Overlay -->
        <div v-if="showScanner" class="fixed inset-0 z-50 bg-black flex flex-col animate-in fade-in duration-500 overflow-hidden">
            <!-- Background Camera -->
            <div id="staff-reader" class="absolute inset-0 w-full h-full z-0"></div>
            
            <!-- Custom Scanning Overlay -->
            <div class="absolute inset-0 flex flex-col items-center justify-center z-10 pointer-events-none">
                <!-- High-Tech Viewfinder -->
                <div class="w-[75vw] h-[75vw] max-w-[300px] max-h-[300px] relative">
                    <!-- Glowing Corners -->
                    <div class="absolute -top-1 -left-1 w-16 h-16 border-t-[6px] border-l-[6px] border-blue-500 rounded-tl-[2.5rem] shadow-[-10px_-10px_30px_rgba(59,130,246,0.6)]"></div>
                    <div class="absolute -top-1 -right-1 w-16 h-16 border-t-[6px] border-r-[6px] border-blue-500 rounded-tr-[2.5rem] shadow-[10px_-10px_30px_rgba(59,130,246,0.6)]"></div>
                    <div class="absolute -bottom-1 -left-1 w-16 h-16 border-b-[6px] border-l-[6px] border-blue-500 rounded-bl-[2.5rem] shadow-[-10px_10px_30px_rgba(59,130,246,0.6)]"></div>
                    <div class="absolute -bottom-1 -right-1 w-16 h-16 border-b-[6px] border-r-[6px] border-blue-500 rounded-br-[2.5rem] shadow-[10px_10px_30px_rgba(59,130,246,0.6)]"></div>
                    
                    <!-- Animated Scanning Laser -->
                    <div class="absolute top-0 left-6 right-6 h-[3px] bg-gradient-to-r from-transparent via-blue-400 to-transparent shadow-[0_0_20px_rgba(96,165,250,1)] animate-scan-line"></div>
                </div>

                <!-- Status & Info (Precisely below frame) -->
                <div class="mt-20 flex flex-col items-center gap-6">
                    <div class="flex items-center gap-4 px-10 py-4 rounded-full bg-blue-600/20 backdrop-blur-3xl border border-blue-500/30 shadow-[0_0_50px_rgba(59,130,246,0.2)] animate-pulse">
                        <div class="w-2.5 h-2.5 rounded-full bg-blue-400 shadow-[0_0_15px_rgba(96,165,250,1)]"></div>
                        <span class="text-[10px] font-black text-white uppercase tracking-[0.4em]">Optical Processing...</span>
                    </div>
                    
                    <div v-if="lastScanned" class="px-8 py-3 bg-black/60 backdrop-blur-xl rounded-2xl border border-white/5 shadow-2xl">
                        <p class="text-[9px] font-black text-blue-400/60 tracking-[0.3em] uppercase mb-1 text-center">Last Detected</p>
                        <p class="text-sm font-black text-white tracking-widest uppercase">{{ lastScanned }}</p>
                    </div>
                </div>
            </div>

            <!-- Header Status -->
            <div class="absolute top-8 left-0 right-0 px-8 flex justify-between items-center pointer-events-none z-20">
                <div class="px-5 py-2.5 bg-black/60 backdrop-blur-2xl rounded-2xl border border-white/5 shadow-2xl">
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-400/80">Link: <span class="text-white">{{ pairingCode }}</span></span>
                </div>
            </div>

            <!-- Close Action (Absolute Bottom) -->
            <div class="absolute bottom-16 left-0 right-0 flex justify-center items-center px-12 z-20">
                 <button 
                    @click="stopCamera"
                    class="w-24 h-24 rounded-[3rem] bg-rose-600/10 text-rose-500 backdrop-blur-3xl border-2 border-rose-500/30 shadow-[0_0_60px_rgba(244,63,94,0.2)] flex items-center justify-center transition-all hover:bg-rose-600/20 active:scale-90 pointer-events-auto"
                >
                    <X class="w-10 h-10" />
                </button>
            </div>
        </div>

        <!-- Success Toast -->
        <div v-if="scanSuccess" class="fixed top-24 left-1/2 -translate-x-1/2 z-[60] flex items-center gap-4 px-10 py-5 rounded-[2rem] bg-emerald-600 text-white shadow-[0_30px_60px_rgba(16,185,129,0.4)] border-2 border-emerald-400/30 animate-in fade-in slide-in-from-top-6 duration-500">
            <CheckCircle2 class="w-7 h-7 text-emerald-200" />
            <div class="flex flex-col">
                <span class="text-[10px] font-black uppercase tracking-[0.2em] leading-tight">Sync Complete</span>
                <span class="text-xs font-black uppercase tracking-[0.1em] opacity-70">Data Transmitted</span>
            </div>
        </div>
    </div>
</template>

<style>
#staff-reader video {
    object-fit: cover !important;
    width: 100% !important;
    height: 100% !important;
    transform: scaleX(-1) !important; /* MIRROR EFFECT */
}
#staff-reader {
    border: none !important;
    background: black !important;
}
#staff-reader__dashboard {
    display: none !important;
}
#staff-reader img[alt="Camera based scan"] {
    display: none !important;
}

@keyframes scan-line {
    0% { top: 0%; opacity: 0; }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% { top: 100%; opacity: 0; }
}

.animate-scan-line {
    animation: scan-line 2.5s ease-in-out infinite;
}

/* Custom transitions */
.fade-in { animation: fadeIn 0.5s ease-out; }
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
</style>
