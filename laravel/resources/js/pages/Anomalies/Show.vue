<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { BreadcrumbItem } from '@/types';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { TriangleAlert, Building2, GraduationCap, Clock3, Camera, ArrowLeft, BookOpen, Users, History } from 'lucide-vue-next';

const props = defineProps<{
    anomaly: {
        id: number;
        type: string;
        status: string;
        detected_at: string | null;
        last_seen_at: string | null;
        resolved_at: string | null;
        payload: Record<string, unknown> | null;
        auditorium: {
            id: number;
            name: string;
            building?: { id: number; name: string } | null;
            faculties: Array<{ id: number; name: string }>;
        } | null;
        camera: { id: number; name: string } | null;
        lesson: {
            id: number;
            subject_name: string;
            employee_name: string;
            group_name: string;
            start_time: string;
            end_time: string;
        } | null;
    };
    related: {
        other_open_count: number;
    };
    history: Array<{
        id: number;
        from_status: string | null;
        to_status: string;
        note: string | null;
        created_at: string | null;
        user: {
            id: number;
            name: string;
        } | null;
    }>;
    typeOptions: Array<{ value: string; label: string }>;
}>();

const page = usePage();

const breadcrumbs: BreadcrumbItem[] = [
    { title: "O'quv jarayoni", href: '#' },
    { title: 'Anomaliyalar', href: '/anomalies' },
    { title: `#${props.anomaly.id}`, href: `/anomalies/${props.anomaly.id}` },
];

const typeLabel = Object.fromEntries(props.typeOptions.map(option => [option.value, option.label]));
const note = ref('');
const successMessage = computed(() => (page.props.flash as Record<string, string> | undefined)?.success);
const errorMessage = computed(() => (page.props.flash as Record<string, string> | undefined)?.error);
const statusLabel = (value: string | null) => ({
    open: 'Ochiq',
    resolved: 'Hal qilindi',
    dismissed: 'Bekor qilindi',
}[value ?? ''] ?? (value || '—'));

const formatDate = (value: string | null) => {
    if (!value) return '—';

    return new Intl.DateTimeFormat('uz-UZ', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const updateStatus = (nextStatus: 'open' | 'resolved' | 'dismissed') => {
    router.post(`/anomalies/${props.anomaly.id}/status`, {
        status: nextStatus,
        note: note.value || undefined,
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            note.value = '';
        },
    });
};
</script>

<template>
    <Head :title="`Anomaliya #${anomaly.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-2">
                    <Link href="/anomalies" class="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground">
                        <ArrowLeft class="h-4 w-4" />
                        Anomaliyalar ro'yxatiga qaytish
                    </Link>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1 rounded-full border border-red-200 bg-red-50 px-3 py-1 text-sm font-semibold text-red-700">
                            <TriangleAlert class="h-4 w-4" />
                            {{ typeLabel[anomaly.type] ?? anomaly.type }}
                        </span>
                        <span class="rounded-full border border-border bg-muted/40 px-3 py-1 text-sm">
                            Holat: {{ anomaly.status }}
                        </span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight">
                        {{ anomaly.auditorium?.name || `Anomaliya #${anomaly.id}` }}
                    </h1>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-if="anomaly.status !== 'resolved'"
                        type="button"
                        class="inline-flex items-center rounded-md border border-emerald-200 px-3 py-2 text-sm font-medium text-emerald-700 transition hover:bg-emerald-50"
                        @click="updateStatus('resolved')"
                    >
                        Hal qilindi
                    </button>
                    <button
                        v-if="anomaly.status !== 'dismissed'"
                        type="button"
                        class="inline-flex items-center rounded-md border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                        @click="updateStatus('dismissed')"
                    >
                        Bekor qilish
                    </button>
                    <button
                        v-if="anomaly.status !== 'open'"
                        type="button"
                        class="inline-flex items-center rounded-md border border-amber-200 px-3 py-2 text-sm font-medium text-amber-700 transition hover:bg-amber-50"
                        @click="updateStatus('open')"
                    >
                        Qayta ochish
                    </button>
                </div>
            </div>
            <div v-if="successMessage" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ successMessage }}
            </div>
            <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ errorMessage }}
            </div>

            <div class="grid gap-4 lg:grid-cols-[1.6fr,1fr]">
                <Card>
                    <CardHeader>
                        <CardTitle>Asosiy ma'lumotlar</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="rounded-lg border p-3">
                                <div class="text-xs text-muted-foreground">Aniqlangan vaqt</div>
                                <div class="mt-1 font-medium">{{ formatDate(anomaly.detected_at) }}</div>
                            </div>
                            <div class="rounded-lg border p-3">
                                <div class="text-xs text-muted-foreground">Oxirgi ko'rilgan</div>
                                <div class="mt-1 font-medium">{{ formatDate(anomaly.last_seen_at) }}</div>
                            </div>
                        </div>

                        <div class="rounded-lg border p-4">
                            <div class="flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                                <span class="inline-flex items-center gap-1">
                                    <Building2 class="h-4 w-4" />
                                    {{ anomaly.auditorium?.building?.name || 'Bino ko‘rsatilmagan' }}
                                </span>
                                <span v-if="anomaly.camera" class="inline-flex items-center gap-1">
                                    <Camera class="h-4 w-4" />
                                    {{ anomaly.camera.name }}
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <Clock3 class="h-4 w-4" />
                                    Boshqa ochiq holatlar: {{ related.other_open_count }}
                                </span>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span
                                    v-for="faculty in anomaly.auditorium?.faculties || []"
                                    :key="faculty.id"
                                    class="inline-flex items-center gap-1 rounded-full border bg-muted/40 px-2 py-1 text-xs"
                                >
                                    <GraduationCap class="h-3.5 w-3.5" />
                                    {{ faculty.name }}
                                </span>
                            </div>
                        </div>

                        <div v-if="anomaly.lesson" class="rounded-lg border p-4">
                            <div class="mb-2 inline-flex items-center gap-2 text-sm font-medium">
                                <BookOpen class="h-4 w-4 text-primary" />
                                Dars ma'lumoti
                            </div>
                            <div class="text-base font-semibold">{{ anomaly.lesson.subject_name }}</div>
                            <div class="mt-2 text-sm text-muted-foreground">
                                {{ anomaly.lesson.employee_name }}
                            </div>
                            <div class="mt-1 inline-flex items-center gap-1 text-sm text-muted-foreground">
                                <Users class="h-4 w-4" />
                                {{ anomaly.lesson.group_name }}
                            </div>
                            <div class="mt-1 text-sm text-muted-foreground">
                                {{ anomaly.lesson.start_time }} - {{ anomaly.lesson.end_time }}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div class="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Izoh va amal</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <textarea
                                v-model="note"
                                class="min-h-[110px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                placeholder="Holatni izohlash uchun qisqa yozuv qoldiring..."
                            />
                            <p class="text-xs text-muted-foreground">
                                Bu izoh keyingi status o'zgarishi bilan tarixga yoziladi.
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Texnik tafsilotlar</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div class="rounded-lg border p-3">
                                <div class="text-xs text-muted-foreground">Anomaliya ID</div>
                                <div class="mt-1 font-mono text-sm">{{ anomaly.id }}</div>
                            </div>
                            <div class="rounded-lg border p-3">
                                <div class="text-xs text-muted-foreground">Tur kodi</div>
                                <div class="mt-1 font-mono text-sm">{{ anomaly.type }}</div>
                            </div>
                            <div v-if="anomaly.payload" class="rounded-lg border p-3">
                                <div class="text-xs text-muted-foreground">Payload</div>
                                <pre class="mt-2 overflow-x-auto whitespace-pre-wrap break-all text-xs text-muted-foreground">{{ JSON.stringify(anomaly.payload, null, 2) }}</pre>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <History class="h-5 w-5 text-primary" />
                        O'zgarishlar tarixi
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="history.length === 0" class="text-sm text-muted-foreground">
                        Hozircha status o'zgarishlari yo'q.
                    </div>
                    <div v-else class="space-y-3">
                        <div
                            v-for="event in history"
                            :key="event.id"
                            class="rounded-lg border p-3"
                        >
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div class="text-sm font-medium">
                                    {{ event.user?.name || 'Tizim' }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ formatDate(event.created_at) }}
                                </div>
                            </div>
                            <div class="mt-2 text-sm">
                                <span class="text-muted-foreground">{{ event.from_status || '—' }}</span>
                                <span class="mx-2">→</span>
                                <span class="font-medium">{{ event.to_status }}</span>
                            </div>
                            <div v-if="event.note" class="mt-2 rounded-md bg-muted/30 px-3 py-2 text-sm">
                                {{ event.note }}
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
