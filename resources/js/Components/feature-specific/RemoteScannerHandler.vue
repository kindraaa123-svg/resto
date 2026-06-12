<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import Modal from '@/Components/ui/Modal.vue'
import Button from '@/Components/ui/Button.vue'
import { Camera, CameraOff, X } from '@lucide/vue'
import { Html5QrcodeScanner } from 'html5-qrcode'
import axios from 'axios'

const page = usePage()
const ui = computed(() => page.props.translations?.ui || {})
const authUser = computed(() => page.props.auth?.user)

const incomingRequest = ref(null)
const showRequestModal = ref(false)
const showScannerModal = ref(false)
let html5QrScanner = null

onMounted(() => {
    if (authUser.value) {
        window.Echo.private(`App.Models.User.${authUser.value.id}`)
            .listen('.remote.camera.request', (e) => {
                incomingRequest.value = e
                showRequestModal.value = true
                
                // Play a subtle sound if possible
                try {
                    const audio = new Audio('/sounds/notification.mp3')
                    audio.play()
                } catch (err) {}
            })
    }
})

onUnmounted(() => {
    if (authUser.value) {
        window.Echo.leave(`App.Models.User.${authUser.value.id}`)
    }
    stopCamera()
})

const acceptRequest = () => {
    showRequestModal.value = false
    showScannerModal.value = true
    startCamera()
}

const rejectRequest = () => {
    showRequestModal.value = false
    incomingRequest.value = null
}

const startCamera = () => {
    setTimeout(() => {
        html5QrScanner = new Html5QrcodeScanner(
            "remote-reader",
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

const stopCamera = () => {
    if (html5QrScanner) {
        html5QrScanner.clear().then(() => {
            html5QrScanner = null
            showScannerModal.value = false
        }).catch(err => {
            console.error(err)
            showScannerModal.value = false
        })
    } else {
        showScannerModal.value = false
    }
}

const onScanSuccess = async (decodedText) => {
    try {
        await axios.post('/api/remote-camera/scanned', {
            toUserId: incomingRequest.value.fromUserId,
            barcode: decodedText
        })
        stopCamera()
        incomingRequest.value = null
    } catch (err) {
        console.error("Failed to send scan result", err)
    }
}

const onScanFailure = () => {}
</script>

<template>
    <div>
        <!-- Incoming Request Modal -->
        <Modal :show="showRequestModal" :title="ui.camera_request || 'Camera Request'" @close="rejectRequest">
            <div class="p-6 text-center space-y-6">
                <div class="w-20 h-20 bg-primary/10 rounded-[2rem] flex items-center justify-center text-primary mx-auto animate-bounce">
                    <Camera class="w-10 h-10" />
                </div>
                <div>
                    <h3 class="text-lg font-black uppercase text-primary tracking-tight">
                        {{ incomingRequest?.fromUserName }} {{ ui.requested_camera || 'requested camera' }}
                    </h3>
                    <p class="text-xs font-bold text-muted-foreground mt-2">
                        {{ ui.camera_request_desc || 'They want to use your device as a barcode scanner.' }}
                    </p>
                </div>
                <div class="flex gap-3">
                    <Button variant="ghost" @click="rejectRequest" class="flex-1 rounded-xl font-black uppercase tracking-widest text-[10px]">
                        {{ ui.reject || 'Reject' }}
                    </Button>
                    <Button @click="acceptRequest" class="flex-1 rounded-xl shadow-xl shadow-primary/30 font-black uppercase tracking-widest text-[10px]">
                        {{ ui.allow || 'Allow' }}
                    </Button>
                </div>
            </div>
        </Modal>

        <!-- Remote Scanner Modal -->
        <Modal :show="showScannerModal" :title="ui.remote_scanner || 'Remote Scanner'" @close="stopCamera">
            <div class="p-6 space-y-6">
                <div class="relative aspect-square w-full max-w-sm mx-auto overflow-hidden rounded-[2rem] bg-black border-4 border-primary/20 shadow-2xl">
                    <div id="remote-reader" class="w-full h-full"></div>
                    
                    <!-- Scanner Overlay -->
                    <div class="absolute inset-0 pointer-events-none flex flex-col items-center justify-center">
                        <div class="w-48 h-48 border-2 border-primary/50 rounded-3xl relative animate-pulse">
                            <div class="absolute -top-1 -left-1 w-6 h-6 border-t-4 border-l-4 border-primary rounded-tl-lg"></div>
                            <div class="absolute -top-1 -right-1 w-6 h-6 border-t-4 border-r-4 border-primary rounded-tr-lg"></div>
                            <div class="absolute -bottom-1 -left-1 w-6 h-6 border-b-4 border-l-4 border-primary rounded-bl-lg"></div>
                            <div class="absolute -bottom-1 -right-1 w-6 h-6 border-b-4 border-r-4 border-primary rounded-br-lg"></div>
                        </div>
                        <p class="mt-6 text-[10px] font-black text-white uppercase tracking-[0.3em] bg-black/40 px-4 py-1.5 rounded-full backdrop-blur-sm">Remote Scanning Mode</p>
                    </div>
                </div>

                <div class="flex justify-center">
                    <Button variant="ghost" @click="stopCamera" class="gap-2 rounded-xl font-black uppercase tracking-widest text-[10px]">
                        <CameraOff class="w-4 h-4" />
                        {{ ui.stop_scanning || 'Stop Scanning' }}
                    </Button>
                </div>
            </div>
        </Modal>
    </div>
</template>
