<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { AlertCircle, CheckCircle2, Clock, RefreshCw, Radio, Settings, Youtube } from 'lucide-vue-next';

type StreamStatus =
    | 'pushing'
    | 'waiting'
    | 'configured'
    | 'not_configured'
    | 'camera_inactive'
    | 'not_in_mediamtx'
    | 'not_ready';

interface YoutubeStream {
    id: number;
    name: string;
    ip_address: string;
    youtube_url: string | null;
    is_active: boolean;
    is_streaming_to_youtube: boolean;
    path_name: string;
    status: StreamStatus;
    path_ready: boolean;
    reader_count: number;
    tracks: string[];
    bytes_received: number;
    bytes_sent: number;
    ready_time: string | null;
    updated_at: string;
}

const props = defineProps<{
    streams: YoutubeStream[];
    stats: {
        total: number;
        configured: number;
        requested: number;
        pushing: number;
        waiting: number;
        attention: number;
    };
    mediaMtxError: string | null;
    refreshedAt: string;
}>();

const statusMeta: Record<StreamStatus, { label: string; badge: 'default' | 'secondary' | 'destructive' | 'outline'; class: string }> = {
    pushing: {
        label: 'Uzatilmoqda',
        badge: 'default',
        class: 'bg-emerald-600 text-white hover:bg-emerald-600',
    },
    waiting: {
        label: 'Kutmoqda',
        badge: 'secondary',
        class: 'bg-amber-100 text-amber-900 hover:bg-amber-100',
    },
    configured: {
        label: 'Sozlangan',
        badge: 'outline',
        class: '',
    },
    not_configured: {
        label: 'Sozlanmagan',
        badge: 'destructive',
        class: '',
    },
    camera_inactive: {
        label: 'Kamera o‘chirilgan',
        badge: 'destructive',
        class: '',
    },
    not_in_mediamtx: {
        label: 'MediaMTXda yo‘q',
        badge: 'destructive',
        class: '',
    },
    not_ready: {
        label: 'Tayyor emas',
        badge: 'destructive',
        class: '',
    },
};

const refresh = () => {
    router.reload({ only: ['streams', 'stats', 'mediaMtxError', 'refreshedAt'] });
};

const formatBytes = (value: number) => {
    if (!value) return '0 B';

    const units = ['B', 'KB', 'MB', 'GB'];
    const index = Math.min(Math.floor(Math.log(value) / Math.log(1024)), units.length - 1);
    const amount = value / Math.pow(1024, index);

    return `${amount.toFixed(index === 0 ? 0 : 1)} ${units[index]}`;
};

const formatDate = (value: string | null) => {
    if (!value) return '-';

    return new Intl.DateTimeFormat('uz-UZ', {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    }).format(new Date(value));
};

const hideStreamKey = (url: string | null) => {
    if (!url) return '-';

    const parts = url.split('/');
    const key = parts.pop() || '';
    const masked = key.length > 8 ? `${key.slice(0, 4)}...${key.slice(-4)}` : '****';

    return `${parts.join('/')}/${masked}`;
};
</script>

<template>
    <Head title="YouTube Efirlar" />

    <AppLayout :breadcrumbs="[
        { title: 'Tizim', href: '#' },
        { title: 'YouTube Efirlar', href: '/system/youtube-streams' },
    ]">
        <div class="flex-1 space-y-4 p-4 md:p-8">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">YouTube Efirlar</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Kameralardan YouTube Live ga uzatilayotgan oqimlar holati.
                    </p>
                </div>
                <Button variant="outline" @click="refresh">
                    <RefreshCw class="mr-2 h-4 w-4" />
                    Yangilash
                </Button>
            </div>

            <div v-if="mediaMtxError" class="rounded-md border border-destructive/40 bg-destructive/10 p-3 text-sm text-destructive">
                MediaMTX holatini olishda xatolik: {{ mediaMtxError }}
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Jami</CardTitle>
                        <Youtube class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total }}</div>
                        <p class="mt-1 text-xs text-muted-foreground">YouTube sozlamasi bor yoki efir belgilangan</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Sozlangan</CardTitle>
                        <Settings class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.configured }}</div>
                        <p class="mt-1 text-xs text-muted-foreground">Stream key saqlangan</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Boshlangan</CardTitle>
                        <Radio class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.requested }}</div>
                        <p class="mt-1 text-xs text-muted-foreground">Platformada efir yoqilgan</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Uzatilmoqda</CardTitle>
                        <CheckCircle2 class="h-4 w-4 text-emerald-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.pushing }}</div>
                        <p class="mt-1 text-xs text-muted-foreground">MediaMTX path tayyor va reader bor</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">E'tibor kerak</CardTitle>
                        <AlertCircle class="h-4 w-4 text-destructive" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.attention }}</div>
                        <p class="mt-1 text-xs text-muted-foreground">Sozlama yoki kamera holati muammoli</p>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <CardTitle>Oqimlar</CardTitle>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Oxirgi yangilanish: {{ formatDate(refreshedAt) }}
                        </p>
                    </div>
                    <Badge variant="outline" class="rounded-md">
                        <Clock class="h-3 w-3" />
                        Avtomatik emas
                    </Badge>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Kamera</TableHead>
                                    <TableHead>Holat</TableHead>
                                    <TableHead>MediaMTX</TableHead>
                                    <TableHead>O'qishlar</TableHead>
                                    <TableHead>Traffic</TableHead>
                                    <TableHead>YouTube URL</TableHead>
                                    <TableHead class="text-right">Amal</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="streams.length === 0">
                                    <TableCell colspan="7" class="h-24 text-center text-muted-foreground">
                                        YouTube efir sozlamasi bor kamera topilmadi.
                                    </TableCell>
                                </TableRow>
                                <TableRow v-for="stream in streams" :key="stream.id">
                                    <TableCell>
                                        <div class="font-medium">{{ stream.name }}</div>
                                        <div class="mt-1 text-xs text-muted-foreground">{{ stream.ip_address }} · {{ stream.path_name }}</div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="statusMeta[stream.status].badge" :class="statusMeta[stream.status].class">
                                            {{ statusMeta[stream.status].label }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2 text-sm">
                                            <span
                                                class="h-2 w-2 rounded-full"
                                                :class="stream.path_ready ? 'bg-emerald-500' : 'bg-muted-foreground'"
                                            />
                                            {{ stream.path_ready ? 'Tayyor' : 'Kutmoqda' }}
                                        </div>
                                        <div class="mt-1 text-xs text-muted-foreground">{{ formatDate(stream.ready_time) }}</div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="text-sm font-medium">{{ stream.reader_count }}</div>
                                        <div class="mt-1 text-xs text-muted-foreground">{{ stream.tracks.join(', ') || '-' }}</div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="text-sm">{{ formatBytes(stream.bytes_sent) }} sent</div>
                                        <div class="mt-1 text-xs text-muted-foreground">{{ formatBytes(stream.bytes_received) }} received</div>
                                    </TableCell>
                                    <TableCell class="max-w-[260px]">
                                        <div class="truncate font-mono text-xs text-muted-foreground">
                                            {{ hideStreamKey(stream.youtube_url) }}
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <Button variant="ghost" size="sm" as-child>
                                            <Link :href="`/cameras/${stream.id}`">Ochish</Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                    <p class="mt-3 text-xs text-muted-foreground">
                        Holat MediaMTX va UniVision ma'lumotlaridan hisoblanadi. YouTube Studio dagi bitrate yoki translyatsiya sifati uchun YouTube API ulash kerak bo'ladi.
                    </p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
