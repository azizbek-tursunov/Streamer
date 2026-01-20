<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Camera, BreadcrumbItem } from '@/types';
import VideoPlayer from '@/components/VideoPlayer.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardFooter, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert';
import { Play, Square, Edit, ArrowLeft, RefreshCw, TriangleAlert } from 'lucide-vue-next';

const props = defineProps<{
    camera: Camera;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Cameras', href: '/cameras' },
    { title: props.camera.name, href: '#' },
];

const getStreamUrl = (camera: Camera) => {
    const host = window.location.hostname;
    // Use viewer credentials
    return `http://viewer:viewer@${host}:8888/cam_${camera.id}/index.m3u8`;
};

const toggleStream = (camera: Camera) => {
    if (camera.is_streaming_to_youtube) {
        router.post(`/cameras/${camera.id}/stop-stream`);
    } else {
        router.post(`/cameras/${camera.id}/stream`);
    }
};

const analyzeCamera = (camera: Camera) => {
    router.post(`/cameras/${camera.id}/analyze`);
};
</script>

<template>
    <Head :title="camera.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center gap-4">
                <Button variant="outline" size="icon" as-child>
                    <Link href="/cameras">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold flex items-center gap-3">
                        {{ camera.name }}
                        <Badge :variant="camera.is_active ? 'default' : 'secondary'">
                            {{ camera.is_active ? 'Active' : 'Inactive' }}
                        </Badge>
                        <Badge v-if="camera.is_streaming_to_youtube" variant="destructive" class="animate-pulse">
                            LIVE on YouTube
                        </Badge>
                    </h1>
                    <p class="text-sm text-muted-foreground">{{ camera.ip_address }}:{{ camera.port }}</p>
                </div>
                <Button variant="secondary" as-child>
                    <Link :href="`/cameras/${camera.id}/edit`">
                        <Edit class="mr-2 h-4 w-4" />
                        Edit
                    </Link>
                </Button>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <Card class="md:col-span-2 overflow-hidden bg-black border-0 ring-1 ring-border">
                    <div class="aspect-video relative">
                         <VideoPlayer 
                            v-if="camera.is_active" 
                            :stream-url="getStreamUrl(camera)" 
                            :autoplay="true"
                        />
                        <div v-else class="flex items-center justify-center h-full text-white/50">
                            Camera Offline
                        </div>
                    </div>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Stream Controls</CardTitle>
                        <CardDescription>Manage YouTube restreaming</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                         <div class="space-y-1">
                            <h3 class="font-medium text-sm">Video Format</h3>
                            <div class="flex items-center gap-2">
                                <Badge v-if="camera.video_codec" variant="outline">{{ camera.video_codec }}</Badge>
                                <span v-else class="text-xs text-muted-foreground">Unknown</span>
                                <Button size="sm" variant="ghost" @click="analyzeCamera(camera)">
                                    <RefreshCw class="h-3 w-3 mr-1" /> Check
                                </Button>
                            </div>
                        </div>

                         <Alert v-if="camera.video_codec === 'hevc' || camera.video_codec === 'h265'" variant="destructive">
                            <TriangleAlert class="h-4 w-4" />
                            <AlertTitle>Optimization Required</AlertTitle>
                            <AlertDescription>
                                This camera is using <strong>H.265</strong>. This causes high CPU usage.
                                <br/>
                                Recommendation: Change to <strong>H.264</strong> in camera settings.
                                <br/>
                                <a :href="`http://${camera.ip_address}/doc/page/config.asp`" target="_blank" class="underline font-bold">
                                    Open Camera Settings &rarr;
                                </a>
                            </AlertDescription>
                        </Alert>

                        <div class="space-y-1">
                            <h3 class="font-medium text-sm">YouTube URL</h3>
                            <p v-if="camera.youtube_url" class="text-xs text-muted-foreground break-all font-mono bg-muted p-2 rounded">
                                {{ camera.youtube_url }}
                            </p>
                            <p v-else class="text-xs text-destructive">Not configured</p>
                        </div>
                        
                        <div class="pt-4">
                            <Button 
                                class="w-full"
                                :variant="camera.is_streaming_to_youtube ? 'destructive' : 'default'"
                                @click="toggleStream(camera)"
                                :disabled="!camera.is_active || !camera.youtube_url"
                            >
                                <component :is="camera.is_streaming_to_youtube ? Square : Play" class="mr-2 h-4 w-4" />
                                {{ camera.is_streaming_to_youtube ? 'Stop Stream' : 'Start Stream' }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
