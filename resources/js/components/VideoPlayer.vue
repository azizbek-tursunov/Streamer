<script setup lang="ts">
import Hls from 'hls.js';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps<{
    streamUrl: string;
    autoplay?: boolean;
}>();

const videoRef = ref<HTMLVideoElement | null>(null);
let hls: Hls | null = null;

const initPlayer = () => {
    if (!videoRef.value) return;

    if (Hls.isSupported()) {
        if (hls) hls.destroy();

        // Configure HLS.js to send Basic Auth credentials
        hls = new Hls({
            debug: false,
            xhrSetup: (xhr, url) => {
                // If the stream URL expects auth (viewer:viewer), we can header it.
                // However, our URL in Index.vue is constructed http://host:8888/path
                // We should inject the Authorization header here.
                
                // Hardcoded viewer credentials for now as configured in mediamtx.yml
                // User: viewer, Pass: viewer
                // internal auth requires Basic Auth
                // btoa('viewer:viewer') = dmlld2VyOnZpZXdlcg==
                
                // Only send auth to the media server (check port 8888)
                if (url.includes(':8888')) {
                     xhr.setRequestHeader('Authorization', 'Basic ' + btoa('viewer:viewer'));
                }
            },
        });

        hls.loadSource(props.streamUrl);
        hls.attachMedia(videoRef.value);
        hls.on(Hls.Events.MANIFEST_PARSED, () => {
            if (props.autoplay) videoRef.value?.play().catch(() => {});
        });
        hls.on(Hls.Events.ERROR, (event, data) => {
             if (data.fatal) {
                switch (data.type) {
                case Hls.ErrorTypes.NETWORK_ERROR:
                    // try to recover network error
                    console.log('fatal network error encountered, try to recover');
                    hls?.startLoad();
                    break;
                case Hls.ErrorTypes.MEDIA_ERROR:
                    console.log('fatal media error encountered, try to recover');
                    hls?.recoverMediaError();
                    break;
                default:
                    // cannot recover
                    hls?.destroy();
                    break;
                }
            }
        });
    } else if (videoRef.value.canPlayType('application/vnd.apple.mpegurl')) {
        // Native HLS support (Safari) - might require credentials in URL http://user:pass@host...
        // We handle this in Index.vue by stripping them for Hls.js but including them for Safari?
        // Actually, Index.vue currently has embedded Admin credentials. We should switch to Viewer.
        videoRef.value.src = props.streamUrl;
        if (props.autoplay) {
            videoRef.value.addEventListener('loadedmetadata', () => {
                videoRef.value?.play().catch(() => {});
            });
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
    <video ref="videoRef" controls muted playsinline class="w-full h-full object-cover bg-black rounded-lg"></video>
</template>
