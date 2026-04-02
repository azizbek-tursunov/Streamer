<script setup lang="ts">
import { reactive, computed, ref, onMounted, onUnmounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Camera, BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import Pagination from '@/components/Pagination.vue';

interface SnapshotInfo {
    url: string;
    timestamp: number;
}

const props = defineProps<{
    cameras: {
        data: Camera[];
        links: any[];
        per_page: number;
    };
    buildings?: string[];
    filters?: {
        building?: string;
    };
}>();

const gridSizes = [16, 24, 32] as const;
const currentPerPage = computed(() => props.cameras.per_page || 16);

const selectedBuilding = ref(props.filters?.building || '');

const changeGridSize = (size: number) => {
    const params: Record<string, any> = { per_page: size };
    if (selectedBuilding.value) params.building = selectedBuilding.value;
    router.get('/cameras/grid', params, { preserveState: true, preserveScroll: true });
};

const changeBuilding = (value: string) => {
    selectedBuilding.value = value;
    const params: Record<string, any> = { per_page: currentPerPage.value };
    if (value && value !== 'all') params.building = value;
    router.get('/cameras/grid', params, { preserveState: true, preserveScroll: true });
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Kameralar', href: '/cameras' },
    { title: 'Jonli Mozaika', href: '/cameras/grid' },
];

// Smart polling: store snapshot URLs with timestamps
// Key: cameraId, Value: { url, timestamp }
const snapshotUrls = reactive<Record<number, SnapshotInfo | null>>({});
const cachedTimestamps = reactive<Record<number, number>>({});

// Per-camera image state: 'loading' | 'loaded' | 'error'
const imgState = reactive<Record<number, 'loading' | 'loaded' | 'error'>>({});

let pollInterval: ReturnType<typeof setInterval> | null = null;

// Initialize snapshot URLs from props
const initializeSnapshots = () => {
    props.cameras.data.forEach(camera => {
        imgState[camera.id] = camera.snapshot_url ? 'loading' : 'error';
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
// Snapshots are captured every 5 min, so polling every 2 min is sufficient
const pollSnapshots = async () => {
    try {
        const response = await fetch('/cameras/snapshots');
        const data: Record<number, SnapshotInfo | null> = await response.json();

        Object.entries(data).forEach(([cameraId, info]) => {
            const id = parseInt(cameraId);
            if (info && info.timestamp !== cachedTimestamps[id]) {
                snapshotUrls[id] = {
                    url: `${info.url}?t=${info.timestamp}`,
                    timestamp: info.timestamp
                };
                cachedTimestamps[id] = info.timestamp;
                imgState[id] = 'loading'; // reset so skeleton shows briefly on update
            }
        });
    } catch (error) {
        console.error('Failed to poll snapshots:', error);
    }
};

// Cameras with dynamic snapshot URLs
const camerasWithSnapshots = computed(() => {
    return props.cameras.data.map(camera => ({
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
            <div class="flex items-center justify-between gap-4 px-4 pt-4 flex-wrap">
                <div class="flex items-center gap-3">
                    <span class="text-sm text-muted-foreground">
                        {{ camerasWithSnapshots.length }} ta kamera
                    </span>
                    <Select :model-value="selectedBuilding || 'all'" @update:model-value="changeBuilding">
                        <SelectTrigger class="w-[220px] h-8 text-xs">
                            <SelectValue placeholder="Barcha binolar" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Barcha binolar</SelectItem>
                            <SelectItem v-for="b in buildings" :key="b" :value="b">{{ b }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="flex items-center gap-1">
                    <span class="text-xs text-muted-foreground mr-2">Ko'rsatish:</span>
                    <Button
                        v-for="size in gridSizes"
                        :key="size"
                        :variant="currentPerPage === size ? 'default' : 'outline'"
                        size="sm"
                        class="h-7 px-3 text-xs"
                        @click="changeGridSize(size)"
                    >
                        {{ size }}
                    </Button>
                </div>
            </div>

            <!-- CCTV Grid -->
            <div v-if="camerasWithSnapshots.length > 0" class="grid flex-1 content-start grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-2 p-4 overflow-auto">
                <div
                    v-for="camera in camerasWithSnapshots"
                    :key="camera.id"
                    class="relative bg-black group overflow-hidden rounded-lg flex flex-col"
                >
                    <div class="relative aspect-video w-full overflow-hidden">
                        <!-- Skeleton shimmer while loading -->
                        <div
                            v-if="imgState[camera.id] === 'loading'"
                            class="absolute inset-0 bg-zinc-800 animate-pulse"
                        />

                        <img
                            v-if="camera.displaySnapshotUrl && imgState[camera.id] !== 'error'"
                            :src="camera.displaySnapshotUrl"
                            :alt="camera.name"
                            loading="lazy"
                            decoding="async"
                            class="h-full w-full object-cover transition-transform duration-300"
                            :class="{ 'opacity-0': imgState[camera.id] === 'loading' }"
                            :style="{ transform: `rotate(${camera.rotation || 0}deg) ${Math.abs(camera.rotation || 0) % 180 === 90 ? 'scale(1.777)' : ''}` }"
                            @load="imgState[camera.id] = 'loaded'"
                            @error="imgState[camera.id] = 'error'"
                        />
                        <div v-if="imgState[camera.id] === 'error'" class="flex h-full w-full items-center justify-center bg-zinc-800">
                            <span class="text-xs text-zinc-500 p-4 text-center">Rasm yo'q</span>
                        </div>

                        <!-- Live indicator -->
                        <div class="absolute top-2 right-2 h-2 w-2 rounded-full bg-red-500 animate-pulse" v-if="camera.is_active" title="Live"></div>

                        <Link
                            :href="`/cameras/${camera.id}`"
                            class="absolute inset-0 z-10 focus:ring-2 focus:ring-inset focus:ring-primary"
                            aria-label="Kamerani ochish"
                        ></Link>
                    </div>

                    <!-- Card Footer -->
                    <div class="px-2 py-1.5 bg-zinc-900 text-white">
                        <p class="text-xs font-medium truncate">{{ camera.name }}</p>
                        <p v-if="camera.faculty" class="text-[10px] text-zinc-400 truncate">{{ camera.faculty.name }}</p>
                    </div>
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

            <!-- Pagination -->
            <div class="mt-4 pb-4 px-4" v-if="cameras.data.length > 0">
                <Pagination :links="cameras.links" />
            </div>
        </div>
    </AppLayout>
</template>
