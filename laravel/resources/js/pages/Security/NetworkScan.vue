<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import VideoPlayer from '@/components/VideoPlayer.vue';
import { Head, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import { BreadcrumbItem } from '@/types';
import { ref, watch } from 'vue';
import { Radar, Globe, Video, RefreshCw, Play, X } from 'lucide-vue-next';

interface ScanResult {
    ip: string;
    port: number;
    rtsp_verified: boolean;
    has_web?: boolean;
    details?: Array<{
        codec: string | null;
        type: string | null;
        resolution: string | null;
    }>;
}

const props = defineProps<{
    results: ScanResult[] | null;
    scanRunning: boolean;
    lastScanAt: string | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tarmoq Skaneri', href: '/network-scan' },
];

const scanning = ref(props.scanRunning);

// Preview state
const previewOpen = ref(false);
const previewDevice = ref<ScanResult | null>(null);
const previewUsername = ref('admin');
const previewPassword = ref('');
const previewStreamPath = ref('Streaming/Channels/101');
const previewLoading = ref(false);
const previewError = ref('');
const previewHlsUrl = ref('');
const previewWhepUrl = ref('');
const previewActive = ref(false);

const startScan = () => {
    scanning.value = true;
    router.post('/network-scan', {}, {
        preserveScroll: true,
    });
};

const refresh = () => {
    router.reload({ preserveScroll: true });
    scanning.value = props.scanRunning;
};

const getSubnet = (ip: string) => {
    const parts = ip.split('.');
    return `${parts[0]}.${parts[1]}.${parts[2]}.0/24`;
};

const openPreview = (device: ScanResult) => {
    previewDevice.value = device;
    previewError.value = '';
    previewActive.value = false;
    previewHlsUrl.value = '';
    previewWhepUrl.value = '';
    previewOpen.value = true;
};

const startPreview = async () => {
    if (!previewDevice.value) return;
    previewLoading.value = true;
    previewError.value = '';

    try {
        const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;
        const res = await fetch('/network-scan/preview', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                ip: previewDevice.value.ip,
                port: previewDevice.value.port,
                username: previewUsername.value,
                password: previewPassword.value,
                stream_path: previewStreamPath.value,
            }),
        });

        const data = await res.json();
        if (!res.ok) {
            previewError.value = data.error || 'Xatolik yuz berdi';
            return;
        }

        previewHlsUrl.value = data.hlsUrl;
        previewWhepUrl.value = data.whepUrl;
        previewActive.value = true;
    } catch (e) {
        previewError.value = 'Serverga ulanib bo\'lmadi';
    } finally {
        previewLoading.value = false;
    }
};

const stopPreview = async () => {
    if (!previewDevice.value) return;

    previewActive.value = false;
    previewHlsUrl.value = '';
    previewWhepUrl.value = '';

    try {
        const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;
        await fetch('/network-scan/stop-preview', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ ip: previewDevice.value.ip }),
        });
    } catch {
        // ignore
    }
};

watch(previewOpen, (open) => {
    if (!open && previewActive.value) {
        stopPreview();
    }
});
</script>

<template>
    <Head title="Tarmoq Skaneri" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold flex items-center gap-3">
                        <Radar class="h-6 w-6" />
                        Tarmoq Skaneri
                    </h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        Tarmoqdagi bazaga qo'shilmagan kameralarni topish
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" @click="refresh">
                        <RefreshCw class="h-4 w-4 mr-1" />
                        Yangilash
                    </Button>
                    <Button @click="startScan" :disabled="scanning">
                        <Radar class="h-4 w-4 mr-2" :class="{ 'animate-spin': scanning }" />
                        {{ scanning ? 'Skanerlanmoqda...' : 'Skanerlashni boshlash' }}
                    </Button>
                </div>
            </div>

            <div v-if="scanning" class="rounded-lg border border-yellow-200 bg-yellow-50 dark:border-yellow-900 dark:bg-yellow-950 p-4">
                <p class="text-sm text-yellow-800 dark:text-yellow-200">
                    Tarmoq skanerlanmoqda... Bu 5-10 daqiqa davom etishi mumkin.
                    Sahifani "Yangilash" tugmasi bilan yangilab turing.
                </p>
            </div>

            <div v-if="results" class="flex items-center gap-3">
                <p v-if="lastScanAt" class="text-xs text-muted-foreground">
                    Oxirgi skanerlash: {{ lastScanAt }}
                </p>
                <Badge variant="outline">{{ results.length }} qurilma topildi</Badge>
            </div>

            <div v-if="!results && !scanning" class="flex flex-col items-center justify-center py-20 text-muted-foreground">
                <Radar class="h-16 w-16 mb-4 opacity-20" />
                <p>Hali skanerlash o'tkazilmagan</p>
                <p class="text-sm">Tarmoqdagi yangi kameralarni topish uchun skanerlashni boshlang</p>
            </div>

            <div v-else-if="results && results.length === 0" class="flex flex-col items-center justify-center py-20 text-muted-foreground">
                <p class="text-lg font-medium">Yangi kamera topilmadi</p>
                <p class="text-sm">Barcha tarmoqdagi kameralar allaqachon bazada mavjud</p>
            </div>

            <div v-else-if="results" class="grid gap-3 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <Card v-for="device in results" :key="device.ip"
                    class="relative cursor-pointer transition-colors hover:bg-muted/50"
                    @click="openPreview(device)">
                    <CardHeader class="pb-3">
                        <div class="flex items-center justify-between">
                            <CardTitle class="text-base font-mono">{{ device.ip }}</CardTitle>
                            <Badge v-if="device.rtsp_verified" variant="default">
                                <Video class="h-3 w-3 mr-1" /> RTSP
                            </Badge>
                            <Badge v-else variant="secondary">Port 554</Badge>
                        </div>
                        <CardDescription>
                            {{ getSubnet(device.ip) }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center gap-2">
                            <Badge v-if="device.has_web" variant="outline" class="text-xs">
                                <Globe class="h-3 w-3 mr-1" /> Web
                            </Badge>
                            <Button variant="ghost" size="sm" class="ml-auto h-7 text-xs"
                                @click.stop="openPreview(device)">
                                <Play class="h-3 w-3 mr-1" /> Ko'rish
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Preview Dialog -->
        <Dialog v-model:open="previewOpen">
            <DialogContent class="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle class="font-mono">{{ previewDevice?.ip }}</DialogTitle>
                    <DialogDescription>
                        Kamera tasvirini ko'rish uchun login ma'lumotlarini kiriting
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4">
                    <!-- Credentials form -->
                    <div v-if="!previewActive" class="space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <Label for="prev-user">Foydalanuvchi</Label>
                                <Input id="prev-user" v-model="previewUsername" placeholder="admin" />
                            </div>
                            <div class="space-y-1">
                                <Label for="prev-pass">Parol</Label>
                                <Input id="prev-pass" v-model="previewPassword" type="password" placeholder="Parol" />
                            </div>
                        </div>
                        <div class="space-y-1">
                            <Label for="prev-path">RTSP yo'li</Label>
                            <Input id="prev-path" v-model="previewStreamPath" placeholder="Streaming/Channels/101" />
                            <p class="text-xs text-muted-foreground">Hikvision: Streaming/Channels/101 | Dahua: cam/realmonitor?channel=1&subtype=0</p>
                        </div>

                        <p v-if="previewError" class="text-sm text-red-500">{{ previewError }}</p>

                        <div class="flex gap-2">
                            <Button @click="startPreview" :disabled="previewLoading" class="flex-1">
                                <Play class="h-4 w-4 mr-1" />
                                {{ previewLoading ? 'Ulanmoqda...' : 'Ko\'rish' }}
                            </Button>
                            <Button v-if="previewDevice?.has_web" variant="outline" as-child>
                                <a :href="'http://' + previewDevice.ip" target="_blank">
                                    <Globe class="h-4 w-4 mr-1" /> Web UI
                                </a>
                            </Button>
                        </div>
                    </div>

                    <!-- Video Player -->
                    <div v-else class="space-y-3">
                        <div class="aspect-video w-full overflow-hidden rounded-lg bg-black">
                            <VideoPlayer
                                :stream-url="previewHlsUrl"
                                :whep-url="previewWhepUrl"
                                :autoplay="true"
                            />
                        </div>
                        <div class="flex gap-2">
                            <Button variant="destructive" size="sm" @click="stopPreview">
                                <X class="h-4 w-4 mr-1" /> To'xtatish
                            </Button>
                            <p class="text-xs text-muted-foreground self-center">
                                30 soniya faoliyatsizlikdan keyin avtomatik to'xtaydi
                            </p>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
