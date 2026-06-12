<script setup>
import { ref, onMounted, onUnmounted, nextTick, watch } from 'vue'
import * as faceapi from 'face-api.js'
import axios from 'axios'
import Modal from '@/Components/ui/Modal.vue'
import Button from '@/Components/ui/Button.vue'
import { Camera, CameraOff, XCircle, ShieldCheck, UserCheck } from '@lucide/vue'

const props = defineProps({
    show: Boolean,
    email: String,
    title: { type: String, default: 'Face Verification' },
    message: { type: String, default: 'Please scan your face to continue login.' }
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
            const modelPath = '/models'
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
            status.value = 'Position your face in the frame'
            
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
            status.value = 'Face detected! Verifying...'
            
            const res = await axios.post(route('api.login.verify-face'), {
                email: props.email,
                face_descriptor: Array.from(detection.descriptor)
            })
            
            if (res.data.approved) {
                status.value = 'Face Verified Successfully'
                
                // NO SOUND as per request
                
                setTimeout(() => {
                    emit('approved')
                    close()
                }, 1000)
            } else {
                status.value = 'Face not recognized. Please try again.'
                isProcessing.value = false
                setTimeout(scanFace, 2000)
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
                    <UserCheck class="w-6 h-6" />
                </div>
                <div>
                    <h3 class="text-lg font-black uppercase tracking-tight text-primary">{{ title }}</h3>
                    <p class="text-xs font-bold text-muted-foreground">{{ message }}</p>
                </div>
            </div>

            <div class="relative bg-black rounded-[2rem] overflow-hidden aspect-square border-4 border-primary/20 mb-6 shadow-2xl">
                <video v-show="isCameraActive" ref="videoEl" autoplay muted playsinline class="w-full h-full object-cover"></video>
                
                <div v-if="!isCameraActive" class="absolute inset-0 flex flex-col items-center justify-center text-primary/20 space-y-2">
                    <Camera class="w-12 h-12 animate-pulse" />
                    <span class="text-[10px] font-black uppercase tracking-[0.2em]">Readying Camera...</span>
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

            <div :class="['p-4 rounded-2xl border text-center mb-6 transition-all', isProcessing ? 'bg-primary/5 border-primary/10' : 'bg-secondary/50 border-secondary']">
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
