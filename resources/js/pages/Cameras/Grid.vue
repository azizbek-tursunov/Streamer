<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Camera, BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import VideoPlayer from '@/components/VideoPlayer.vue';

defineProps<{
    cameras: Camera[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Kameralar', href: '/cameras' },
    { title: 'Jonli Mozaika', href: '/cameras/grid' },
];

const getStreamUrl = (camera: Camera) => {
    const host = window.location.hostname;
    return `http://viewer:viewer@${host}:8888/cam_${camera.id}/index.m3u8`;
};
</script>

<template>
    <Head title="Jonli Mozaika" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col">
            <!-- CCTV Grid -->
            <div v-if="cameras.length > 0" class="grid h-full grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-[1px] p-[1px] overflow-auto">
                <div 
                    v-for="camera in cameras" 
                    :key="camera.id"
                    class="relative aspect-video bg-black group overflow-hidden"
                >
                    <VideoPlayer 
                        :stream-url="getStreamUrl(camera)" 
                        :autoplay="true"
                        class="h-full w-full object-cover"
                    />
                    
                    <!-- Camera Overlay -->
                    <div class="absolute inset-x-0 top-0 bg-gradient-to-b from-black/60 to-transparent p-2 opacity-0 group-hover:opacity-100 transition-opacity flex justify-between items-start pointer-events-none">
                        <span class="text-white text-xs font-mono bg-black/50 px-1 rounded">{{ camera.name }}</span>
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
