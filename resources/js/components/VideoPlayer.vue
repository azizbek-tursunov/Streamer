<script setup lang="ts">
import Hls from 'hls.js';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps<{
    streamUrl: string;
    autoplay?: boolean;
}>();

const videoRef = ref<HTMLVideoElement | null>(null);
const isLoading = ref(true);
let hls: Hls | null = null;
let retryCount = 0;
const MAX_RETRIES = 10;
const RETRY_DELAY = 2000;

const initPlayer = () => {
    if (!videoRef.value) return;
    isLoading.value = true;
    retryCount = 0;

    if (Hls.isSupported()) {
        if (hls) hls.destroy();

        hls = new Hls({
            debug: false,
            enableWorker: true,
            lowLatencyMode: true,
            backBufferLength: 90,
            xhrSetup: (xhr, url) => {
                if (url.includes(':8888')) {
                     xhr.setRequestHeader('Authorization', 'Basic ' + btoa('viewer:viewer'));
                }
            },
        });

        const loadStream = () => {
            hls?.loadSource(props.streamUrl);
            hls?.attachMedia(videoRef.value!);
        };

        loadStream();

        hls.on(Hls.Events.MANIFEST_PARSED, () => {
             isLoading.value = false;
            if (props.autoplay) videoRef.value?.play().catch(() => {});
        });

        hls.on(Hls.Events.ERROR, (event, data) => {
             if (data.fatal) {
                switch (data.type) {
                case Hls.ErrorTypes.NETWORK_ERROR:
                    // Check if it's a 404 (stream starting up)
                    if (data.response?.code === 404 && retryCount < MAX_RETRIES) {
                        console.log(`Stream not ready, retrying (${retryCount + 1}/${MAX_RETRIES})...`);
                        retryCount++;
                        setTimeout(() => {
                            hls?.loadSource(props.streamUrl);
                        }, RETRY_DELAY);
                    } else {
                        console.log('fatal network error encountered, try to recover');
                        hls?.startLoad();
                    }
                    break;
                case Hls.ErrorTypes.MEDIA_ERROR:
                    console.log('fatal media error encountered, try to recover');
                    hls?.recoverMediaError();
                    break;
                default:
                    hls?.destroy();
                    break;
                }
            }
        });
    } else if (videoRef.value.canPlayType('application/vnd.apple.mpegurl')) {
        videoRef.value.src = props.streamUrl;
        if (props.autoplay) {
            videoRef.value.addEventListener('loadedmetadata', () => {
                videoRef.value?.play().catch(() => {});
                isLoading.value = false;
            });
             videoRef.value.addEventListener('waiting', () => isLoading.value = true);
             videoRef.value.addEventListener('playing', () => isLoading.value = false);
        }
    }
};

onMounted(() => {
    initPlayer();
});

watch(() => props.streamUrl, () => {
    initPlayer();
});

onBeforeUnmount(() => {
    if (hls) hls.destroy();
});
</script>

<template>
    <div class="relative w-full h-full bg-black rounded-lg overflow-hidden group">
        <video 
            ref="videoRef" 
            controls 
            muted 
            playsinline 
            class="w-full h-full object-cover"
            :class="{ 'opacity-50': isLoading }"
            @waiting="isLoading = true"
            @playing="isLoading = false"
        ></video>
        
        <div v-if="isLoading" class="absolute inset-0 flex items-center justify-center pointer-events-none">
            <div class="flex flex-col items-center gap-2">
                <div class="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent"></div>
                <span class="text-xs text-white/80 font-medium bg-black/50 px-2 py-1 rounded">Connecting...</span>
            </div>
        </div>
    </div>
</template>
