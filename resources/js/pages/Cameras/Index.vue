<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Camera, BreadcrumbItem } from '@/types';
import VideoPlayer from '@/components/VideoPlayer.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Plus, Play, Square, Trash2, Edit } from 'lucide-vue-next';

defineProps<{
    cameras: Camera[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Cameras',
        href: '/cameras',
    },
];

const getStreamUrl = (camera: Camera) => {
    // Using current hostname with port 8888 for MediaMTX HLS
    const host = window.location.hostname;
    // We use xhrSetup in VideoPlayer to inject Basic Auth for 'viewer' user
    // So we just return the clean URL here (or include viewer:viewer for Safari fallback if needed)
    // Safari ignores XHR headers for native HLS, so we might need embedded credentials for Safari.
    // Let's use embedded viewer credentials for simple compatibility across both.
    return `http://viewer:viewer@${host}:8888/cam_${camera.id}/index.m3u8`;
};

const deleteCamera = (camera: Camera) => {
    if (confirm('Are you sure you want to delete this camera?')) {
        router.delete(`/cameras/${camera.id}`);
    }
};

const toggleStream = (camera: Camera) => {
    if (camera.is_streaming_to_youtube) {
        router.post(`/cameras/${camera.id}/stop-stream`);
    } else {
        router.post(`/cameras/${camera.id}/stream`);
    }
};
</script>

<template>
    <Head title="Cameras" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Cameras</h1>
                <Button as-child>
                    <Link href="/cameras/create">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Camera
                    </Link>
                </Button>
            </div>

            <div v-if="cameras.length === 0" class="flex flex-col items-center justify-center p-8 text-muted-foreground border rounded-xl border-dashed">
                <p>No cameras found.</p>
            </div>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <Card v-for="camera in cameras" :key="camera.id" class="overflow-hidden">
                    <CardHeader class="p-4 pb-2">
                        <div class="flex justify-between items-start">
                            <CardTitle class="text-lg font-medium truncate" :title="camera.name">{{ camera.name }}</CardTitle>
                            <Badge :variant="camera.is_active ? 'default' : 'secondary'">
                                {{ camera.is_active ? 'Active' : 'Inactive' }}
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent class="p-0 aspect-video relative bg-black">
                        <VideoPlayer 
                            v-if="camera.is_active" 
                            :stream-url="getStreamUrl(camera)" 
                            :autoplay="true"
                        />
                        <div v-else class="flex items-center justify-center h-full text-white/50">
                            Camera Offline
                        </div>
                        
                        <div v-if="camera.is_streaming_to_youtube" class="absolute top-2 left-2">
                            <Badge variant="destructive" class="animate-pulse">
                                LIVE on YouTube
                            </Badge>
                        </div>
                    </CardContent>
                    <CardFooter class="flex justify-between p-4 bg-muted/50">
                         <div class="flex gap-2">
                            <Button size="sm" variant="outline" as-child>
                                <Link :href="`/cameras/${camera.id}/edit`">
                                    <Edit class="h-4 w-4" />
                                </Link>
                            </Button>
                            <Button size="sm" variant="destructive" @click="deleteCamera(camera)">
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                        
                        <Button 
                            size="sm" 
                            :variant="camera.is_streaming_to_youtube ? 'secondary' : 'default'"
                            @click="toggleStream(camera)"
                            :disabled="!camera.is_active || !camera.youtube_url"
                            :title="!camera.youtube_url ? 'Configure YouTube URL first' : ''"
                        >
                            <component :is="camera.is_streaming_to_youtube ? Square : Play" class="mr-2 h-4 w-4" />
                            {{ camera.is_streaming_to_youtube ? 'Stop Stream' : 'Go Live' }}
                        </Button>
                    </CardFooter>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
