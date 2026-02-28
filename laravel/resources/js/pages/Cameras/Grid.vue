<script setup lang="ts">
import { reactive, computed, onMounted, onUnmounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Camera, BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

interface SnapshotInfo {
    url: string;
    timestamp: number;
}

const props = defineProps<{
    cameras: Camera[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Kameralar', href: '/cameras' },
    { title: 'Jonli Mozaika', href: '/cameras/grid' },
];

// Smart polling: store snapshot URLs with timestamps
// Key: cameraId, Value: { url, timestamp }
const snapshotUrls = reactive<Record<number, SnapshotInfo | null>>({});
const cachedTimestamps = reactive<Record<number, number>>({});

let pollInterval: ReturnType<typeof setInterval> | null = null;

// Initialize snapshot URLs from props
const initializeSnapshots = () => {
    props.cameras.forEach(camera => {
        if (camera.snapshot_url) {
            snapshotUrls[camera.id] = {
                url: camera.snapshot_url,
                timestamp: Date.now()
            };
            cachedTimestamps[camera.id] = 0; // Will be updated on first poll
        }
    });
};

// Poll for new snapshots - only update if timestamp changed
const pollSnapshots = async () => {
    try {
        const response = await fetch('/cameras/snapshots');
        const data: Record<number, SnapshotInfo | null> = await response.json();
        
        Object.entries(data).forEach(([cameraId, info]) => {
            const id = parseInt(cameraId);
            if (info && info.timestamp !== cachedTimestamps[id]) {
                // New snapshot detected! Update the URL
                snapshotUrls[id] = {
                    url: `${info.url}?t=${info.timestamp}`,
                    timestamp: info.timestamp
                };
                cachedTimestamps[id] = info.timestamp;
            }
        });
    } catch (error) {
        console.error('Failed to poll snapshots:', error);
    }
};

// Cameras with dynamic snapshot URLs
const camerasWithSnapshots = computed(() => {
    return props.cameras.map(camera => ({
        ...camera,
        displaySnapshotUrl: snapshotUrls[camera.id]?.url || camera.snapshot_url
    }));
});

onMounted(() => {
    initializeSnapshots();
    // Poll immediately, then every 30 seconds
    pollSnapshots();
    pollInterval = setInterval(pollSnapshots, 30000);
});

onUnmounted(() => {
    if (pollInterval) {
        clearInterval(pollInterval);
    }
});
</script>

<template>
    <Head title="Jonli Mozaika" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4">
            <!-- Toolbar -->
            <div class="flex items-center gap-4 px-4 pt-4 flex-wrap">
                <span class="text-sm text-muted-foreground">
                    {{ camerasWithSnapshots.length }} ta kamera
                </span>
            </div>

            <!-- CCTV Grid -->
            <div v-if="camerasWithSnapshots.length > 0" class="grid flex-1 grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-2 p-4 overflow-auto">
                <div 
                    v-for="camera in camerasWithSnapshots" 
                    :key="camera.id"
                    class="relative aspect-video bg-black group overflow-hidden rounded-lg"
                >
                    <div class="relative h-full w-full overflow-hidden">
                        <img 
                            v-if="camera.displaySnapshotUrl"
                            :src="camera.displaySnapshotUrl" 
                            :alt="camera.name"
                            class="h-full w-full object-cover transition-transform duration-300"
                            :style="{ transform: `rotate(${camera.rotation || 0}deg) ${Math.abs(camera.rotation || 0) % 180 === 90 ? 'scale(1.777)' : ''}` }"
                        />
                        <div v-else class="flex h-full w-full items-center justify-center bg-zinc-800">
                            <span class="text-xs text-muted-foreground p-4 text-center">Rasm yo'q</span>
                        </div>
                    </div>
                    
                    <!-- Camera Overlay -->
                    <div class="absolute inset-x-0 top-0 bg-gradient-to-b from-black/60 to-transparent p-2 opacity-0 group-hover:opacity-100 transition-opacity flex justify-between items-start pointer-events-none">
                        <div class="flex flex-col gap-0.5">
                            <span class="text-white text-xs font-mono bg-black/50 px-1 rounded">{{ camera.name }}</span>
                            <span v-if="camera.faculty" class="text-white/80 text-[10px] font-mono bg-black/50 px-1 rounded">{{ camera.faculty.name }}</span>
                        </div>
                        <div class="h-2 w-2 rounded-full bg-red-500 animate-pulse" v-if="camera.is_active" title="Live"></div>
                    </div>

                    <Link 
                        :href="`/cameras/${camera.id}`" 
                        class="absolute inset-0 z-10 focus:ring-2 focus:ring-inset focus:ring-primary"
                        aria-label="Kamerani ochish"
                    ></Link>
                </div>
            </div>
            
            <div v-else class="flex flex-1 items-center justify-center p-10 bg-background">
                <div class="flex flex-col items-center gap-2 text-center">
                    <h3 class="text-xl font-bold">Faol Kameralar Yo'q</h3>
                    <p class="text-muted-foreground">
                        Hozirda faol kameralar mavjud emas.
                    </p>
                    <Button as-child variant="outline" class="mt-4">
                        <Link href="/cameras">Kameralarni Boshqarish</Link>
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
