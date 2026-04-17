<script setup lang="ts">
import { reactive, computed, ref, onMounted, onUnmounted, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Camera, BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
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
const gridContainer = ref<HTMLElement | null>(null);

const buildGridUrl = (overrides: Record<string, string | number | null | undefined> = {}) => {
    const params = new URLSearchParams(window.location.search);

    Object.entries(overrides).forEach(([key, value]) => {
        if (value === null || value === undefined || value === '' || value === 'all') {
            params.delete(key);
            return;
        }

        params.set(key, String(value));
    });

    const query = params.toString();
    return query ? `/cameras/grid?${query}` : '/cameras/grid';
};

const changeGridSize = (size: number) => {
    window.location.assign(buildGridUrl({ per_page: size, page: null, building: selectedBuilding.value || null }));
};

const changeBuilding = (value: string) => {
    selectedBuilding.value = value;
    window.location.assign(buildGridUrl({ per_page: currentPerPage.value, page: null, building: value }));
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Kameralar', href: '/cameras' },
    { title: 'Jonli Mozaika', href: '/cameras/grid' },
];

// Smart polling: keep the last successfully loaded snapshot visible.
// Key: cameraId, Value: { url, timestamp }
const snapshotUrls = reactive<Record<number, SnapshotInfo | null>>({});
const cachedTimestamps = reactive<Record<number, number>>({});

// Per-camera image state: 'loading' | 'loaded' | 'error'
const imgState = reactive<Record<number, 'loading' | 'loaded' | 'error'>>({});

let pollInterval: ReturnType<typeof setInterval> | null = null;

const preloadSnapshot = (cameraId: number, nextUrl: string, timestamp: number) => {
    const img = new Image();

    img.onload = () => {
        snapshotUrls[cameraId] = {
            url: nextUrl,
            timestamp,
        };
        cachedTimestamps[cameraId] = timestamp;
        imgState[cameraId] = 'loaded';
    };

    img.onerror = () => {
        if (!snapshotUrls[cameraId]?.url) {
            imgState[cameraId] = 'error';
        }
    };

    img.src = nextUrl;
};

const extractTimestamp = (url?: string | null): number => {
    if (!url) {
        return 0;
    }

    try {
        const parsedUrl = new URL(url, window.location.origin);
        return Number(parsedUrl.searchParams.get('t') || 0);
    } catch {
        return 0;
    }
};

// Initialize snapshot URLs from props
const initializeSnapshots = () => {
    props.cameras.data.forEach(camera => {
        imgState[camera.id] = camera.snapshot_url ? 'loaded' : 'error';
        if (camera.snapshot_url) {
            const timestamp = extractTimestamp(camera.snapshot_url);
            snapshotUrls[camera.id] = {
                url: camera.snapshot_url,
                timestamp
            };
            cachedTimestamps[camera.id] = timestamp;
        }
    });
};

// Poll for new snapshots - only update if timestamp changed
// Snapshots are captured every 5 min, so polling every 2 min is sufficient
const pollSnapshots = async () => {
    try {
        const response = await fetch('/cameras/snapshots');
        if (!response.ok) {
            return;
        }
        const data: Record<number, SnapshotInfo | null> = await response.json();

        Object.entries(data).forEach(([cameraId, info]) => {
            const id = parseInt(cameraId);
            if (info) {
                const nextUrl = `${info.url}?t=${info.timestamp}`;
                const currentUrl = snapshotUrls[id]?.url || null;

                if (info.timestamp === cachedTimestamps[id] && nextUrl === currentUrl) {
                    return;
                }

                if (nextUrl !== currentUrl) {
                    imgState[id] = currentUrl ? 'loaded' : 'loading';
                    preloadSnapshot(id, nextUrl, info.timestamp);
                    return;
                }

                cachedTimestamps[id] = info.timestamp;
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

watch(
    () => props.cameras.data.map((camera) => ({
        id: camera.id,
        snapshot_url: camera.snapshot_url,
    })),
    (cameras) => {
        const activeIds = new Set(cameras.map((camera) => camera.id));

        Object.keys(snapshotUrls).forEach((id) => {
            if (!activeIds.has(Number(id))) {
                delete snapshotUrls[Number(id)];
            }
        });

        Object.keys(cachedTimestamps).forEach((id) => {
            if (!activeIds.has(Number(id))) {
                delete cachedTimestamps[Number(id)];
            }
        });

        Object.keys(imgState).forEach((id) => {
            if (!activeIds.has(Number(id))) {
                delete imgState[Number(id)];
            }
        });

        initializeSnapshots();
        pollSnapshots();
        gridContainer.value?.scrollTo({ top: 0, behavior: 'auto' });
    },
    { deep: true }
);

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
            <div
                v-if="camerasWithSnapshots.length > 0"
                ref="gridContainer"
                class="grid flex-1 content-start grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-2 p-4 overflow-auto"
            >
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
                            :class="{ 'opacity-0': imgState[camera.id] === 'loading' && !snapshotUrls[camera.id]?.url }"
                            :style="{ transform: `rotate(${camera.rotation || 0}deg) ${Math.abs(camera.rotation || 0) % 180 === 90 ? 'scale(1.777)' : ''}` }"
                            @load="imgState[camera.id] = 'loaded'"
                            @error="imgState[camera.id] = snapshotUrls[camera.id]?.url ? 'loaded' : 'error'"
                        />
                        <div v-if="imgState[camera.id] === 'error'" class="flex h-full w-full items-center justify-center bg-zinc-800">
                            <span class="text-xs text-zinc-500 p-4 text-center">Rasm yo'q</span>
                        </div>

                        <!-- Live indicator -->
                        <div class="absolute top-2 right-2 h-2 w-2 rounded-full bg-red-500 animate-pulse" v-if="camera.is_active" title="Live"></div>

                        <a
                            :href="`/cameras/${camera.id}`"
                            class="absolute inset-0 z-10 focus:ring-2 focus:ring-inset focus:ring-primary"
                            aria-label="Kamerani ochish"
                        ></a>
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
                        <a href="/cameras">Kameralarni Boshqarish</a>
                    </Button>
                </div>
            </div>

            <!-- Pagination -->
            <div class="mt-4 pb-4 px-4" v-if="cameras.data.length > 0">
                <Pagination :links="cameras.links" hard-reload />
            </div>
        </div>
    </AppLayout>
</template>
