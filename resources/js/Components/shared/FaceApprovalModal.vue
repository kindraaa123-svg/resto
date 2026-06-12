<script setup>
import { ref, onMounted, onUnmounted, nextTick, watch } from 'vue'
import * as faceapi from 'face-api.js'
import axios from 'axios'
import Modal from '@/Components/ui/Modal.vue'
import Button from '@/Components/ui/Button.vue'
import { Camera, CameraOff, XCircle, ShieldCheck, AlertCircle } from '@lucide/vue'

const props = defineProps({
    show: Boolean,
    title: { type: String, default: 'Manager Authorization' },
    message: { type: String, default: 'Please scan manager face to authorize this action.' }
})

const emit = defineEmits(['close', 'approved', 'error'])

const videoEl = ref(null)
const isCameraActive = ref(false)
const isModelsLoaded = ref(false)
const status = ref('')
const isProcessing = ref(false)
let stream = null

const startCamera = async () => {
    status.value = 'Loading AI Models...'
    try {
        if (!isModelsLoaded.value) {
            const modelPath = '/POS/models'
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(modelPath),
                faceapi.nets.faceLandmark68Net.loadFromUri(modelPath),
                faceapi.nets.faceRecognitionNet.loadFromUri(modelPath)
            ])
            isModelsLoaded.value = true
        }
        
        status.value = 'Starting camera...'
        stream = await navigator.mediaDevices.getUserMedia({ video: true })
        isCameraActive.value = true
        
        await nextTick()
        if (videoEl.value) {
            videoEl.value.srcObject = stream
            status.value = 'Position face in frame'
            
            // Auto-scan after 2 seconds
            setTimeout(scanFace, 2000)
        }
    } catch (err) {
        console.error(err)
        status.value = 'Error: ' + err.message
    }
}

const stopCamera = () => {
    if (stream) {
        stream.getTracks().forEach(track => track.stop())
        stream = null
    }
    isCameraActive.value = false
}

const scanFace = async () => {
    if (!videoEl.value || isProcessing.value) return
    
    isProcessing.value = true
    status.value = 'Analyzing face...'
    
    try {
        const detection = await faceapi.detectSingleFace(videoEl.value, new faceapi.TinyFaceDetectorOptions())
                                    .withFaceLandmarks()
                                    .withFaceDescriptor()
                                    
        if (detection) {
            status.value = 'Face detected! Verifying with server...'
            
            const res = await axios.post(route('api.face-approval.verify'), {
                face_descriptor: Array.from(detection.descriptor)
            })
            
            if (res.data.approved) {
                status.value = 'Approved by ' + res.data.manager_name
                
                // Play success sound
                try {
                    const audio = new Audio('/POS/sound/success.mp3')
                    audio.play().catch(e => console.warn('Sound play blocked:', e))
                } catch (err) {}

                setTimeout(() => {
                    emit('approved', { managerId: res.data.manager_id })
                    close()
                }, 1000)
            } else {
                status.value = 'Unauthorized: Face not recognized as Manager/Admin.'
                isProcessing.value = false
            }
        } else {
            status.value = 'No face detected. Re-trying...'
            isProcessing.value = false
            setTimeout(scanFace, 1500)
        }
    } catch (err) {
        status.value = 'Verification failed.'
        console.error(err)
        isProcessing.value = false
    }
}

const close = () => {
    stopCamera()
    emit('close')
}

// Watch for show prop changes to start/stop camera
watch(() => props.show, async (newVal) => {
    if (newVal) {
        await nextTick()
        startCamera()
    } else {
        stopCamera()
    }
})

onUnmounted(() => {
    stopCamera()
})
</script>

<template>
    <Modal :show="show" @close="close" max-width="md">
        <div class="p-6">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
                    <ShieldCheck class="w-6 h-6" />
                </div>
                <div>
                    <h3 class="text-lg font-black uppercase tracking-tight text-primary">{{ title }}</h3>
                    <p class="text-xs font-bold text-muted-foreground">{{ message }}</p>
                </div>
            </div>

            <div class="relative bg-black rounded-[2rem] overflow-hidden aspect-square border-4 border-primary/20 mb-6">
                <video v-show="isCameraActive" ref="videoEl" autoplay muted playsinline class="w-full h-full object-cover"></video>
                
                <div v-if="!isCameraActive" class="absolute inset-0 flex flex-col items-center justify-center text-primary/20 space-y-2">
                    <Camera class="w-12 h-12" />
                    <span class="text-[10px] font-black uppercase tracking-[0.2em]">Initializing...</span>
                </div>

                <!-- Overlay -->
                <div class="absolute inset-0 pointer-events-none flex flex-col items-center justify-center">
                    <div class="w-64 h-64 border-2 border-primary/40 rounded-full animate-pulse relative">
                         <div class="absolute -top-1 -left-1 w-10 h-10 border-t-4 border-l-4 border-primary rounded-tl-[2rem]"></div>
                         <div class="absolute -top-1 -right-1 w-10 h-10 border-t-4 border-r-4 border-primary rounded-tr-[2rem]"></div>
                         <div class="absolute -bottom-1 -left-1 w-10 h-10 border-b-4 border-l-4 border-primary rounded-bl-[2rem]"></div>
                         <div class="absolute -bottom-1 -right-1 w-10 h-10 border-b-4 border-r-4 border-primary rounded-br-[2rem]"></div>
                    </div>
                </div>
            </div>

            <div :class="['p-4 rounded-2xl border text-center mb-6', isProcessing ? 'bg-primary/5 border-primary/10' : 'bg-secondary/50 border-secondary']">
                <p class="text-xs font-black uppercase tracking-widest" :class="isProcessing ? 'text-primary' : 'text-muted-foreground'">
                    {{ status }}
                </p>
            </div>

            <div class="flex gap-3">
                <Button @click="close" variant="ghost" class="flex-1 gap-2 font-black uppercase text-[10px] tracking-widest h-12 rounded-xl">
                    <XCircle class="w-4 h-4" /> Cancel
                </Button>
                <Button @click="scanFace" :disabled="isProcessing" class="flex-1 gap-2 font-black uppercase text-[10px] tracking-widest h-12 rounded-xl shadow-lg shadow-primary/20">
                    <Camera class="w-4 h-4" /> Rescan
                </Button>
            </div>
        </div>
    </Modal>
</template>
