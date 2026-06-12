<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import { Camera, CameraOff, Smartphone, Wifi, CheckCircle2 } from '@lucide/vue'
import { Html5QrcodeScanner } from 'html5-qrcode'
import axios from 'axios'

const deviceId = ref(localStorage.getItem('guest_device_id') || Math.random().toString(36).substring(2, 15))
const deviceName = ref(localStorage.getItem('guest_device_name') || 'Mobile Scanner')
const networkId = ref(null)
const isConnected = ref(false)
const showScanner = ref(false)
const incomingRequest = ref(null)
const scanSuccess = ref(false)

let html5QrScanner = null

onMounted(async () => {
    localStorage.setItem('guest_device_id', deviceId.value)
    
    // Get Network Info (IP Hash)
    try {
        const res = await axios.get('/api/remote-camera/network')
        networkId.value = res.data.networkId
        
        // Join Discovery Channel
        window.Echo.channel(`guest-discovery.${networkId.value}`)
            .listen('.guest.discovery', (e) => {
                // Cashier might be looking for us, or another guest joined
                sendPing()
            })

        // Listen for direct requests to this device
        window.Echo.channel(`guest-scan.${deviceId.value}`)
            .listen('.guest.request', (e) => {
                incomingRequest.value = e
                // Play sound or vibrate
                if (window.navigator.vibrate) window.navigator.vibrate([200, 100, 200])
            })

        sendPing()
        isConnected.value = true
        
        // Ping every 30 seconds to stay alive in cashier's list
        setInterval(sendPing, 30000)
    } catch (err) {
        console.error("Discovery failed", err)
    }
})

const sendPing = () => {
    if (!networkId.value) return
    axios.post('/api/remote-camera/ping', {
        networkId: networkId.value,
        deviceId: deviceId.value,
        deviceName: deviceName.value
    })
}

const acceptRequest = () => {
    incomingRequest.value = null
    showScanner.value = true
    startCamera()
}

const rejectRequest = () => {
    incomingRequest.value = null
}

const startCamera = () => {
    setTimeout(() => {
        html5QrScanner = new Html5QrcodeScanner(
            "guest-reader",
            { 
                fps: 15, 
                qrbox: { width: 250, height: 250 },
                supportedScanTypes: [0]
            },
            false
        );
        html5QrScanner.render(onScanSuccess, onScanFailure);
    }, 300);
}

const onScanSuccess = async (decodedText) => {
    try {
        const requesterId = incomingRequest.value?.requesterId || localStorage.getItem('last_requester_id')
        await axios.post('/api/remote-camera/guest-scanned', {
            requesterId: requesterId,
            barcode: decodedText
        })
        
        scanSuccess.value = true
        stopCamera()
        
        setTimeout(() => {
            scanSuccess.value = false
        }, 3000)
    } catch (err) {
        console.error("Scan result delivery failed", err)
    }
}

const onScanFailure = () => {}

const stopCamera = () => {
    if (html5QrScanner) {
        html5QrScanner.clear().then(() => {
            html5QrScanner = null
            showScanner.value = false
        }).catch(err => {
            showScanner.value = false
        })
    } else {
        showScanner.value = false
    }
}
</script>

<template>
    <div class="min-h-screen bg-background flex flex-col items-center justify-center p-6 text-center">
        <Head title="Mobile Scanner" />

        <!-- Status Header -->
        <div class="fixed top-0 left-0 right-0 p-6 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div :class="['w-2 h-2 rounded-full animate-pulse', isConnected ? 'bg-green-500' : 'bg-red-500']"></div>
                <span class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">
                    {{ isConnected ? 'Connected to POS' : 'Connecting...' }}
                </span>
            </div>
            <Wifi :class="['w-4 h-4', isConnected ? 'text-primary' : 'text-muted-foreground']" />
        </div>

        <!-- Main UI -->
        <div v-if="!showScanner && !incomingRequest" class="space-y-8 animate-in fade-in zoom-in duration-500">
            <div class="w-32 h-32 bg-primary/10 rounded-[3rem] flex items-center justify-center text-primary mx-auto shadow-2xl shadow-primary/20">
                <Smartphone class="w-16 h-16" />
            </div>
            
            <div class="space-y-2">
                <h1 class="text-2xl font-black uppercase text-primary tracking-tight">Guest Scanner</h1>
                <p class="text-xs font-bold text-muted-foreground max-w-xs mx-auto">
                    Keep this page open. The cashier will find this device automatically if you are on the same Wi-Fi.
                </p>
            </div>

            <div class="p-4 bg-secondary/50 rounded-2xl border border-secondary inline-block">
                <p class="text-[9px] font-black uppercase tracking-widest text-muted-foreground mb-1">Device Name</p>
                <input 
                    v-model="deviceName" 
                    @change="localStorage.setItem('guest_device_name', deviceName); sendPing()"
                    class="bg-transparent text-center font-black text-primary uppercase outline-none focus:ring-0"
                />
            </div>
        </div>

        <!-- Incoming Request Prompt -->
        <div v-if="incomingRequest" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-6">
            <div class="bg-card w-full max-w-sm rounded-[2.5rem] p-8 shadow-2xl border border-primary/20 space-y-8 animate-in slide-in-from-bottom-10">
                <div class="w-20 h-20 bg-primary/10 rounded-[2rem] flex items-center justify-center text-primary mx-auto animate-bounce">
                    <Camera class="w-10 h-10" />
                </div>
                
                <div class="space-y-2">
                    <h3 class="text-xl font-black uppercase text-primary tracking-tight">
                        {{ incomingRequest.requesterName }}
                    </h3>
                    <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest">
                        Requested your camera
                    </p>
                </div>

                <div class="flex flex-col gap-3">
                    <button 
                        @click="acceptRequest"
                        class="w-full py-4 rounded-2xl bg-primary text-primary-foreground font-black uppercase tracking-widest text-xs shadow-xl shadow-primary/30"
                    >
                        Allow Camera
                    </button>
                    <button 
                        @click="rejectRequest"
                        class="w-full py-4 rounded-2xl bg-secondary text-primary font-black uppercase tracking-widest text-xs"
                    >
                        Decline
                    </button>
                </div>
            </div>
        </div>

        <!-- Scanner Overlay -->
        <div v-if="showScanner" class="fixed inset-0 z-50 bg-black flex flex-col">
            <div id="guest-reader" class="flex-1"></div>
            
            <div class="absolute inset-0 pointer-events-none flex flex-col items-center justify-center">
                <div class="w-64 h-64 border-2 border-primary/50 rounded-[2.5rem] relative animate-pulse">
                    <div class="absolute -top-1 -left-1 w-8 h-8 border-t-4 border-l-4 border-primary rounded-tl-2xl"></div>
                    <div class="absolute -top-1 -right-1 w-8 h-8 border-t-4 border-r-4 border-primary rounded-tr-2xl"></div>
                    <div class="absolute -bottom-1 -left-1 w-8 h-8 border-b-4 border-l-4 border-primary rounded-bl-2xl"></div>
                    <div class="absolute -bottom-1 -right-1 w-8 h-8 border-b-4 border-r-4 border-primary rounded-br-2xl"></div>
                </div>
                <p class="mt-8 text-[10px] font-black text-white uppercase tracking-[0.4em] bg-black/60 px-6 py-2 rounded-full backdrop-blur-md">Scanning for POS...</p>
            </div>

            <button 
                @click="stopCamera"
                class="absolute bottom-12 left-1/2 -translate-x-1/2 p-4 rounded-full bg-white/10 text-white backdrop-blur-md border border-white/20"
            >
                <CameraOff class="w-6 h-6" />
            </button>
        </div>

        <!-- Success Toast -->
        <div v-if="scanSuccess" class="fixed bottom-12 left-1/2 -translate-x-1/2 flex items-center gap-3 px-6 py-3 rounded-full bg-green-500 text-white shadow-2xl animate-in fade-in slide-in-from-bottom-4">
            <CheckCircle2 class="w-5 h-5" />
            <span class="text-[10px] font-black uppercase tracking-widest">Barcode Sent!</span>
        </div>
    </div>
</template>

<style>
#guest-reader video {
    object-fit: cover !important;
    width: 100% !important;
    height: 100% !important;
}
#guest-reader {
    border: none !important;
}
#guest-reader__dashboard {
    display: none !important;
}
</style>
