<script setup lang="ts">
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Camera, BreadcrumbItem, Branch, Floor, Faculty } from '@/types';
import VideoPlayer from '@/components/VideoPlayer.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardFooter, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Switch } from '@/components/ui/switch';
import { Play, Square, Edit, ArrowLeft } from 'lucide-vue-next';
import CameraDialog from '@/components/CameraDialog.vue';
import YouTubeDialog from '@/components/YouTubeDialog.vue';

const props = defineProps<{
    camera: Camera;
    branches: Branch[];
    floors: Floor[];
    faculties: Faculty[];
}>();

const showDialog = ref(false);
const showYouTubeDialog = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Kameralar', href: '/cameras' },
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

const toggleActive = (camera: Camera) => {
    router.post(`/cameras/${camera.id}/toggle-active`, {}, {
        preserveScroll: true,
    });
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
                            {{ camera.is_active ? 'Faol' : 'Faol Emas' }}
                        </Badge>
                        <Badge v-if="camera.is_streaming_to_youtube" variant="destructive" class="animate-pulse">
                            YouTube Jonli Efirda
                        </Badge>
                    </h1>
                    <p class="text-sm text-muted-foreground">{{ camera.ip_address }}:{{ camera.port }}</p>
                </div>
                <Button variant="secondary" @click="showDialog = true">
                    <Edit class="mr-2 h-4 w-4" />
                    Tahrirlash
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
                            Kamera O'chirib Qo'yilgan
                        </div>
                    </div>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Boshqaruv Paneli</CardTitle>
                        <CardDescription>Stream va sozlamalarni boshqarish</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <!-- Camera Status Section -->
                        <div class="space-y-2">
                             <div class="flex items-center justify-between">
                                <div class="space-y-0.5">
                                    <h3 class="font-medium text-sm">Kamera Holati</h3>
                                    <p class="text-xs text-muted-foreground">Kamerani yoqish yoki o'chirish</p>
                                </div>
                                <Switch 
                                    :checked="camera.is_active"
                                    @update:checked="toggleActive(camera)"
                                />
                            </div>
                        </div>

                         <div class="space-y-1">
                            <div class="flex items-center justify-between">
                                <h3 class="font-medium text-sm">YouTube URL</h3>
                                <Button variant="ghost" size="sm" class="h-6 px-2 text-xs" @click="showYouTubeDialog = true">
                                    <Edit class="h-3 w-3 mr-1" /> Sozlash
                                </Button>
                            </div>
                            <p v-if="camera.youtube_url" class="text-xs text-muted-foreground break-all font-mono bg-muted p-2 rounded">
                                {{ camera.youtube_url }}
                            </p>
                            <p v-else class="text-xs text-destructive">Sozlanmagan</p>
                        </div>
                        
                        <div class="pt-2">
                            <Button 
                                class="w-full"
                                :variant="camera.is_streaming_to_youtube ? 'destructive' : 'default'"
                                @click="toggleStream(camera)"
                                :disabled="!camera.is_active || !camera.youtube_url"
                            >
                                <component :is="camera.is_streaming_to_youtube ? Square : Play" class="mr-2 h-4 w-4" />
                                {{ camera.is_streaming_to_youtube ? 'Efirni To\'xtatish' : 'Efirni Boshlash' }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
            
            <CameraDialog 
                v-model:open="showDialog"
                :camera="camera"
                :branches="branches"
                :floors="floors"
                :faculties="faculties"
            />
            
            <YouTubeDialog
                v-model:open="showYouTubeDialog"
                :camera="camera"
            />
        </div>
    </AppLayout>
</template>
