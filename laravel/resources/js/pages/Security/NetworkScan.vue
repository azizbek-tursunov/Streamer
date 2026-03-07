<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { BreadcrumbItem } from '@/types';
import { ref } from 'vue';
import { Radar, Globe, Video, MonitorSmartphone, RefreshCw } from 'lucide-vue-next';

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

const startScan = () => {
    scanning.value = true;
    router.post('/network-scan', {}, {
        preserveScroll: true,
        onFinish: () => {
            // Keep scanning true, it runs in background
        },
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

            <p v-if="lastScanAt" class="text-xs text-muted-foreground">
                Oxirgi skanerlash: {{ lastScanAt }}
            </p>

            <div v-if="!results && !scanning" class="flex flex-col items-center justify-center py-20 text-muted-foreground">
                <Radar class="h-16 w-16 mb-4 opacity-20" />
                <p>Hali skanerlash o'tkazilmagan</p>
                <p class="text-sm">Tarmoqdagi yangi kameralarni topish uchun skanerlashni boshlang</p>
            </div>

            <div v-else-if="results && results.length === 0" class="flex flex-col items-center justify-center py-20 text-muted-foreground">
                <p class="text-lg font-medium">Yangi kamera topilmadi</p>
                <p class="text-sm">Barcha tarmoqdagi kameralar allaqachon bazada mavjud</p>
            </div>

            <div v-else-if="results" class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                <Card v-for="device in results" :key="device.ip" class="relative">
                    <CardHeader class="pb-3">
                        <div class="flex items-center justify-between">
                            <CardTitle class="text-base font-mono">{{ device.ip }}</CardTitle>
                            <Badge v-if="device.rtsp_verified" variant="default">
                                <Video class="h-3 w-3 mr-1" /> RTSP
                            </Badge>
                            <Badge v-else variant="secondary">Port 554</Badge>
                        </div>
                        <CardDescription>
                            Tarmoq: {{ getSubnet(device.ip) }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <div v-if="device.details" class="space-y-1">
                            <div v-for="(stream, idx) in device.details" :key="idx"
                                class="text-xs bg-muted rounded px-2 py-1 font-mono">
                                <span class="text-muted-foreground">{{ stream.type }}:</span>
                                {{ stream.codec }}
                                <span v-if="stream.resolution" class="text-muted-foreground ml-1">{{ stream.resolution }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 pt-1">
                            <Badge v-if="device.has_web" variant="outline" class="text-xs">
                                <Globe class="h-3 w-3 mr-1" /> Web UI
                            </Badge>
                            <a v-if="device.has_web" :href="'http://' + device.ip"
                                target="_blank" class="text-xs text-blue-500 hover:underline">
                                Ochish
                            </a>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
