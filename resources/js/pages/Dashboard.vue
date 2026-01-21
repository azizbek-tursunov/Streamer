<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem, type Camera } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Camera as CameraIcon, Activity, Youtube } from 'lucide-vue-next';
import VideoPlayer from '@/components/VideoPlayer.vue';

defineProps<{
    stats: {
        total: number;
        active: number;
        streaming: number;
    };
    activeCameras: Camera[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const getStreamUrl = (camera: Camera) => {
    const host = window.location.hostname;
    return `http://viewer:viewer@${host}:8888/cam_${camera.id}/index.m3u8`;
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <!-- Stats Grid -->
            <div class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Cameras</CardTitle>
                        <CameraIcon class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Active Monitoring</CardTitle>
                        <Activity class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-500">{{ stats.active }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">YouTube Live</CardTitle>
                        <Youtube class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-500">{{ stats.streaming }}</div>
                    </CardContent>
                </Card>
            </div>

            <!-- Live Mosaic -->
            <div v-if="activeCameras.length > 0">
                <h3 class="font-medium mb-4">Live Views</h3>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Card 
                        v-for="camera in activeCameras" 
                        :key="camera.id"
                        class="overflow-hidden bg-black border-0 ring-1 ring-border"
                    >
                        <div class="aspect-video relative group">
                            <VideoPlayer 
                                :stream-url="getStreamUrl(camera)" 
                                :autoplay="true"
                            />
                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 to-transparent p-3 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                                <span class="text-white text-sm font-medium">{{ camera.name }}</span>
                            </div>
                            <Link 
                                :href="`/cameras/${camera.id}`" 
                                class="absolute inset-0 z-10"
                                aria-label="View Details"
                            ></Link>
                        </div>
                    </Card>
                </div>
            </div>
            
            <div v-else class="flex flex-1 items-center justify-center rounded-lg border border-dashed shadow-sm">
                <div class="flex flex-col items-center gap-1 text-center">
                    <h3 class="text-2xl font-bold tracking-tight">No Active Cameras</h3>
                    <p class="text-sm text-muted-foreground">
                        Activate cameras to see them here.
                    </p>
                    <Button class="mt-4" as-child>
                        <Link href="/cameras">Manage Cameras</Link>
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
