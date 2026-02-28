<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Camera } from '@/types';
import VideoPlayer from '@/components/VideoPlayer.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    cameras: Camera[];
}>();

const selectedCameraId = ref<number | null>(props.cameras.length > 0 ? props.cameras[0].id : null);

const selectedCamera = computed(() => 
    props.cameras.find(c => c.id === selectedCameraId.value)
);

const getStreamUrl = (camera: Camera) => {
    const host = window.location.hostname;
    // Embed viewer credentials for HLS playback compatibility
    return `http://viewer:viewer@${host}:8888/cam_${camera.id}/index.m3u8`;
};
</script>

<template>
    <Head title="Ommaviy Efirlar" />

    <div class="min-h-screen bg-background text-foreground flex flex-col">
        <!-- Header -->
        <header class="border-b">
            <div class="container mx-auto px-4 h-16 flex items-center justify-between">
                <div class="font-bold text-xl flex items-center gap-2">
                    <span class="text-primary">Stream</span>Platform
                </div>
                <nav>
                    <Link href="/login" class="text-sm font-medium hover:underline">
                        Kirish
                    </Link>
                </nav>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 container mx-auto px-4 py-8">
             <div v-if="cameras.length === 0" class="text-center py-20 text-muted-foreground">
                <p>Hozircha faol efirlar mavjud emas.</p>
            </div>

            <div v-else class="grid grid-cols-1 lg:grid-cols-4 gap-6 h-[calc(100vh-10rem)]">
                <!-- Column 1: Camera List -->
                <Card class="lg:col-span-1 h-full flex flex-col overflow-hidden">
                    <div class="p-4 border-b bg-muted/50 font-medium">
                        Faol Kameralar
                    </div>
                    <div class="flex-1 overflow-y-auto">
                        <div class="p-2 space-y-2">
                            <button
                                v-for="camera in cameras"
                                :key="camera.id"
                                @click="selectedCameraId = camera.id"
                                :class="[
                                    'w-full text-left px-4 py-3 rounded-md transition-colors text-sm font-medium flex items-center justify-between',
                                    selectedCameraId === camera.id 
                                        ? 'bg-primary text-primary-foreground shadow-sm' 
                                        : 'hover:bg-muted text-muted-foreground hover:text-foreground'
                                ]"
                            >
                                <span class="truncate">{{ camera.name }}</span>
                                <span v-if="selectedCameraId === camera.id" class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                                <span v-else class="w-2 h-2 rounded-full bg-green-500"></span>
                            </button>
                        </div>
                    </div>
                </Card>

                <!-- Column 2: Video Player -->
                <div class="lg:col-span-3 flex flex-col gap-4">
                    <Card class="flex-1 overflow-hidden bg-black relative flex items-center justify-center border-0 ring-1 ring-border">
                        <VideoPlayer 
                            v-if="selectedCamera" 
                            :key="selectedCamera.id"
                            :stream-url="getStreamUrl(selectedCamera)" 
                            :autoplay="true"
                        />
                        <div v-else class="text-white/50">
                            Ko'rish uchun kamerani tanlang
                        </div>
                    </Card>
                    
                    <div v-if="selectedCamera" class="flex flex-col gap-1">
                        <h2 class="text-2xl font-bold">{{ selectedCamera.name }}</h2>
                        <div class="flex items-center gap-2 text-muted-foreground text-sm">
                            <span class="inline-block w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                            Jonli Efir
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
