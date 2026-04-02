<script setup lang="ts">
import Hls from 'hls.js';
import { onBeforeUnmount, onMounted, ref, watch, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Play, Pause, Volume2, VolumeX, Maximize, Minimize, RefreshCw } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    streamUrl: string;
    whepUrl?: string;
    autoplay?: boolean;
    rotation?: number;
}>();

const page = usePage();
const iceServers = computed(() => {
    const turn = (page.props.turnServers as RTCIceServer[] | undefined) ?? [];
    return [{ urls: 'stun:stun.l.google.com:19302' }, ...turn];
});

const videoRef = ref<HTMLVideoElement | null>(null);
const containerRef = ref<HTMLDivElement | null>(null);
const isLoading = ref(true);
const isPlaying = ref(false);
const isMuted = ref(false);
const isFullscreen = ref(false);
const showControls = ref(false);
let hls: Hls | null = null;
let peerConnection: RTCPeerConnection | null = null;
let webrtcFetchController: AbortController | null = null;
let retryCount = 0;
let initAttempt = 0;
const MAX_RETRIES = 10;
const RETRY_DELAY = 2000;
const WEBRTC_ICE_GATHER_TIMEOUT = 250;
const WEBRTC_START_TIMEOUT = 4000;
const WEBRTC_DISCONNECT_GRACE_PERIOD = 3000;
let controlsTimeout: ReturnType<typeof setTimeout>;
let webrtcStartTimeout: ReturnType<typeof setTimeout> | null = null;
let webrtcDisconnectTimeout: ReturnType<typeof setTimeout> | null = null;
let resizeObserver: ResizeObserver | null = null;

const containerWidth = ref(0);
const containerHeight = ref(0);

const transformStyle = computed(() => {
    const r = Number(props.rotation || 0);
    const style: any = {
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
    if (webrtcFetchController) {
        webrtcFetchController.abort();
        webrtcFetchController = null;
    }
    if (webrtcStartTimeout) {
        clearTimeout(webrtcStartTimeout);
        webrtcStartTimeout = null;
    }
    if (webrtcDisconnectTimeout) {
        clearTimeout(webrtcDisconnectTimeout);
        webrtcDisconnectTimeout = null;
    }
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

const startHlsFallback = (attempt: number) => {
    if (attempt !== initAttempt) return;
    cleanupWebRTC();
    initHls();
};

const initWebRTC = async (attempt: number): Promise<boolean> => {
    if (!videoRef.value || !props.whepUrl) return false;

    cleanupWebRTC();

    try {
        const pc = new RTCPeerConnection({
            iceServers: iceServers.value,
        });
        peerConnection = pc;

        pc.addTransceiver('video', { direction: 'recvonly' });
        pc.addTransceiver('audio', { direction: 'recvonly' });

        pc.ontrack = (event) => {
            if (attempt !== initAttempt) return;
            if (webrtcStartTimeout) {
                clearTimeout(webrtcStartTimeout);
                webrtcStartTimeout = null;
            }
            if (videoRef.value && event.streams[0]) {
                videoRef.value.srcObject = event.streams[0];
                isLoading.value = false;
                if (props.autoplay) {
                    videoRef.value.play().catch(() => {
                        // Browser blocked unmuted autoplay — retry muted
                        if (videoRef.value) {
                            videoRef.value.muted = true;
                            isMuted.value = true;
                            videoRef.value.play().catch(() => {});
                        }
                    });
                    isPlaying.value = true;
                }
            }
        };

        pc.onconnectionstatechange = () => {
            if (attempt !== initAttempt) return;

            if (pc.connectionState === 'connected') {
                if (webrtcDisconnectTimeout) {
                    clearTimeout(webrtcDisconnectTimeout);
                    webrtcDisconnectTimeout = null;
                }
                return;
            }

            if (pc.connectionState === 'disconnected') {
                if (!webrtcDisconnectTimeout) {
                    webrtcDisconnectTimeout = setTimeout(() => {
                        startHlsFallback(attempt);
                    }, WEBRTC_DISCONNECT_GRACE_PERIOD);
                }
                return;
            }

            if (pc.connectionState === 'failed' || pc.connectionState === 'closed') {
                startHlsFallback(attempt);
            }
        };

        const offer = await pc.createOffer();
        await pc.setLocalDescription(offer);

        // Don't hold the WHEP request for a full ICE gather; send after the
        // first candidate appears or a short timeout expires.
        await new Promise<void>((resolve) => {
            let settled = false;
            const finish = () => {
                if (settled) return;
                settled = true;
                clearTimeout(timeout);
                resolve();
            };

            if (pc.iceGatheringState === 'complete' || pc.localDescription?.sdp.includes('\na=candidate:')) {
                finish();
                return;
            }

            const timeout = setTimeout(finish, WEBRTC_ICE_GATHER_TIMEOUT);

            pc.addEventListener('icecandidate', (event) => {
                if (event.candidate || event.candidate === null) {
                    finish();
                }
            });

            pc.addEventListener('icegatheringstatechange', () => {
                if (pc.iceGatheringState === 'complete') {
                    finish();
                }
            });
        });

        webrtcFetchController = new AbortController();
        const response = await fetch(props.whepUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/sdp' },
            body: pc.localDescription!.sdp,
            signal: webrtcFetchController.signal,
        });
        webrtcFetchController = null;

        if (!response.ok) {
            cleanupWebRTC();
            return false;
        }

        const answerSdp = await response.text();
        await pc.setRemoteDescription({ type: 'answer', sdp: answerSdp });

        webrtcStartTimeout = setTimeout(() => {
            if (videoRef.value?.srcObject) return;
            startHlsFallback(attempt);
        }, WEBRTC_START_TIMEOUT);

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
                videoRef.value?.play().catch(() => {
                    // Browser blocked unmuted autoplay — retry muted
                    if (videoRef.value) {
                        videoRef.value.muted = true;
                        isMuted.value = true;
                        videoRef.value.play().catch(() => {});
                    }
                });
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
    const attempt = ++initAttempt;
    isLoading.value = true;
    retryCount = 0;
    cleanupHls();

    // Try WebRTC first (sub-second latency), fall back to HLS
    if (props.whepUrl) {
        const ok = await initWebRTC(attempt);
        if (attempt !== initAttempt) return;
        if (ok) return;
    }

    if (attempt !== initAttempt) return;
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

watch(() => [props.streamUrl, props.whepUrl], () => {
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
