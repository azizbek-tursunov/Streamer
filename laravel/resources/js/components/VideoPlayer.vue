<script setup lang="ts">
import Hls from 'hls.js';
import { onBeforeUnmount, onMounted, ref, watch, computed } from 'vue';
import { Play, Pause, Volume2, VolumeX, Maximize, Minimize, RefreshCw } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    streamUrl: string;
    whepUrl?: string;
    autoplay?: boolean;
    rotation?: number;
}>();

const videoRef = ref<HTMLVideoElement | null>(null);
const containerRef = ref<HTMLDivElement | null>(null);
const isLoading = ref(true);
const isPlaying = ref(false);
const isMuted = ref(true);
const isFullscreen = ref(false);
const showControls = ref(false);
let hls: Hls | null = null;
let peerConnection: RTCPeerConnection | null = null;
let retryCount = 0;
const MAX_RETRIES = 10;
const RETRY_DELAY = 2000;
let controlsTimeout: ReturnType<typeof setTimeout>;
let resizeObserver: ResizeObserver | null = null;

const containerWidth = ref(0);
const containerHeight = ref(0);

const transformStyle = computed(() => {
    const r = Number(props.rotation || 0);
    let style: any = {
        transform: `rotate(${r}deg)`
    };
    if (r % 180 !== 0) {
        style.width = `${containerHeight.value}px`;
        style.height = `${containerWidth.value}px`;
    } else {
        style.width = '100%';
        style.height = '100%';
    }
    return style;
});

const togglePlay = () => {
    if (!videoRef.value) return;
    if (videoRef.value.paused) {
        videoRef.value.play();
        isPlaying.value = true;
    } else {
        videoRef.value.pause();
        isPlaying.value = false;
    }
};

const toggleMute = () => {
    if (!videoRef.value) return;
    videoRef.value.muted = !videoRef.value.muted;
    isMuted.value = videoRef.value.muted;
};

const toggleFullscreen = async () => {
    if (!containerRef.value) return;

    if (!document.fullscreenElement) {
        await containerRef.value.requestFullscreen();
        isFullscreen.value = true;
    } else {
        await document.exitFullscreen();
        isFullscreen.value = false;
    }
};

const onMouseMove = () => {
    showControls.value = true;
    clearTimeout(controlsTimeout);
    controlsTimeout = setTimeout(() => {
        if (isPlaying.value) {
            showControls.value = false;
        }
    }, 3000);
};

const cleanupWebRTC = () => {
    if (peerConnection) {
        peerConnection.close();
        peerConnection = null;
    }
};

const cleanupHls = () => {
    if (hls) {
        hls.destroy();
        hls = null;
    }
};

const initWebRTC = async (): Promise<boolean> => {
    if (!videoRef.value || !props.whepUrl) return false;

    cleanupWebRTC();

    try {
        const pc = new RTCPeerConnection({
            iceServers: [{ urls: 'stun:stun.l.google.com:19302' }],
        });
        peerConnection = pc;

        pc.addTransceiver('video', { direction: 'recvonly' });
        pc.addTransceiver('audio', { direction: 'recvonly' });

        pc.ontrack = (event) => {
            if (videoRef.value && event.streams[0]) {
                videoRef.value.srcObject = event.streams[0];
                isLoading.value = false;
                if (props.autoplay) {
                    videoRef.value.play().catch(() => {});
                    isPlaying.value = true;
                }
            }
        };

        pc.oniceconnectionstatechange = () => {
            if (pc.iceConnectionState === 'failed' || pc.iceConnectionState === 'disconnected') {
                cleanupWebRTC();
                initHls();
            }
        };

        const offer = await pc.createOffer();
        await pc.setLocalDescription(offer);

        // Wait for ICE gathering to complete (or timeout after 1s)
        await new Promise<void>((resolve) => {
            if (pc.iceGatheringState === 'complete') { resolve(); return; }
            const timeout = setTimeout(resolve, 1000);
            pc.onicegatheringstatechange = () => {
                if (pc.iceGatheringState === 'complete') {
                    clearTimeout(timeout);
                    resolve();
                }
            };
        });

        const response = await fetch(props.whepUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/sdp' },
            body: pc.localDescription!.sdp,
        });

        if (!response.ok) return false;

        const answerSdp = await response.text();
        await pc.setRemoteDescription({ type: 'answer', sdp: answerSdp });

        return true;
    } catch {
        cleanupWebRTC();
        return false;
    }
};

const initHls = () => {
    if (!videoRef.value) return;

    // Clear any WebRTC srcObject before using HLS
    if (videoRef.value.srcObject) {
        videoRef.value.srcObject = null;
    }

    if (Hls.isSupported()) {
        cleanupHls();

        hls = new Hls({
            debug: false,
            enableWorker: true,
            lowLatencyMode: true,
            backBufferLength: 90,
        });

        hls.loadSource(props.streamUrl);
        hls.attachMedia(videoRef.value);

        hls.on(Hls.Events.MANIFEST_PARSED, () => {
            isLoading.value = false;
            if (props.autoplay) {
                videoRef.value?.play().catch(() => {});
                isPlaying.value = true;
            }
        });

        hls.on(Hls.Events.ERROR, (event, data) => {
            if (data.fatal) {
                switch (data.type) {
                case Hls.ErrorTypes.NETWORK_ERROR:
                    if (data.response?.code === 404 && retryCount < MAX_RETRIES) {
                        retryCount++;
                        setTimeout(() => {
                            hls?.loadSource(props.streamUrl);
                        }, RETRY_DELAY);
                    } else {
                        hls?.startLoad();
                    }
                    break;
                case Hls.ErrorTypes.MEDIA_ERROR:
                    hls?.recoverMediaError();
                    break;
                default:
                    cleanupHls();
                    break;
                }
            }
        });
    } else if (videoRef.value.canPlayType('application/vnd.apple.mpegurl')) {
        videoRef.value.src = props.streamUrl;
        const onLoaded = () => {
            isLoading.value = false;
            if (props.autoplay) {
                videoRef.value?.play().catch(() => {});
            }
            videoRef.value?.removeEventListener('loadedmetadata', onLoaded);
        };
        videoRef.value.addEventListener('loadedmetadata', onLoaded);
    }
};

const initPlayer = async () => {
    if (!videoRef.value) return;
    isLoading.value = true;
    retryCount = 0;

    // Try WebRTC first (sub-second latency), fall back to HLS
    if (props.whepUrl) {
        const ok = await initWebRTC();
        if (ok) return;
    }

    initHls();
};

onMounted(() => {
    initPlayer();

    if (containerRef.value) {
        resizeObserver = new ResizeObserver((entries) => {
            for (const entry of entries) {
                containerWidth.value = entry.contentRect.width;
                containerHeight.value = entry.contentRect.height;
            }
        });
        resizeObserver.observe(containerRef.value);
    }
});

watch(() => props.streamUrl, () => {
    initPlayer();
});

onBeforeUnmount(() => {
    cleanupWebRTC();
    cleanupHls();
    clearTimeout(controlsTimeout);
    if (resizeObserver) resizeObserver.disconnect();
});
</script>

<template>
    <div 
        ref="containerRef"
        class="relative w-full h-full bg-black rounded-lg overflow-hidden group flex items-center justify-center"
        @mousemove="onMouseMove"
        @mouseleave="showControls = false"
    >
        <video 
            ref="videoRef" 
            muted 
            playsinline 
            class="object-contain transition-transform duration-300 pointer-events-none"
            :style="transformStyle"
            :class="{ 'opacity-50': isLoading }"
            @waiting="isLoading = true"
            @playing="isLoading = false"
            @pause="isPlaying = false"
            @play="isPlaying = true"
        ></video>
        
        <!-- Loading Overlay -->
        <div v-if="isLoading" class="absolute inset-0 flex items-center justify-center pointer-events-none z-10">
            <div class="flex flex-col items-center gap-2">
                <div class="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent"></div>
                <span class="text-xs text-white/80 font-medium bg-black/50 px-2 py-1 rounded">Connecting...</span>
            </div>
        </div>

        <!-- Custom Controls Overlay -->
        <div 
            class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 to-transparent p-4 transition-opacity duration-300 z-20 flex items-center justify-between"
            :class="{ 'opacity-0': !showControls && isPlaying, 'opacity-100': showControls || !isPlaying }"
        >
            <div class="flex items-center gap-2">
                <Button variant="ghost" size="icon" class="text-white hover:bg-white/20 h-8 w-8" @click="togglePlay">
                    <component :is="isPlaying ? Pause : Play" class="h-5 w-5 fill-current" />
                </Button>
                
                <Button variant="ghost" size="icon" class="text-white hover:bg-white/20 h-8 w-8" @click="toggleMute">
                    <component :is="isMuted ? VolumeX : Volume2" class="h-5 w-5" />
                </Button>

                <div class="flex items-center gap-2 ml-2" v-if="!isPlaying">
                     <span class="text-xs text-white/80 font-mono flex items-center gap-1">
                        <span class="h-2 w-2 rounded-full" :class="isLoading ? 'bg-yellow-500 animate-pulse' : 'bg-green-500'"></span>
                        {{ isLoading ? 'Loading...' : 'Live' }}
                     </span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <Button variant="ghost" size="icon" class="text-white hover:bg-white/20 h-8 w-8" @click="initPlayer">
                    <RefreshCw class="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="icon" class="text-white hover:bg-white/20 h-8 w-8" @click="toggleFullscreen">
                    <component :is="isFullscreen ? Minimize : Maximize" class="h-5 w-5" />
                </Button>
            </div>
        </div>
    </div>
</template>
