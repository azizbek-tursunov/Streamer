<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { debounce } from 'lodash';
import { BreadcrumbItem, Faculty } from '@/types';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import Pagination from '@/components/Pagination.vue';
import { Search, TriangleAlert, CheckCircle2, Ban, Building2, GraduationCap, ArrowRight } from 'lucide-vue-next';

type AnomalyRow = {
    id: number;
    type: string;
    status: string;
    detected_at: string | null;
    last_seen_at: string | null;
    resolved_at: string | null;
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

const props = defineProps<{
    anomalies: {
        data: AnomalyRow[];
        links: any[];
    };
    summary: {
        open: number;
        resolved: number;
        dismissed: number;
    };
    buildings: string[];
    faculties: Faculty[];
    filters: {
        search?: string;
        status?: string;
        type?: string;
        building?: string;
        faculty_id?: number;
    };
    typeOptions: Array<{ value: string; label: string }>;
}>();

const page = usePage();

const breadcrumbs: BreadcrumbItem[] = [
    { title: "O'quv jarayoni", href: '#' },
    { title: 'Anomaliyalar', href: '/anomalies' },
];

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');
const type = ref(props.filters.type || 'all');
const building = ref(props.filters.building || 'all');
const facultyId = ref(props.filters.faculty_id?.toString() || 'all');

watch([search, status, type, building, facultyId], debounce(([searchVal, statusVal, typeVal, buildingVal, facultyVal]) => {
    router.get('/anomalies', {
        search: searchVal || undefined,
        status: statusVal === 'all' ? undefined : statusVal,
        type: typeVal === 'all' ? undefined : typeVal,
        building: buildingVal === 'all' ? undefined : buildingVal,
        faculty_id: facultyVal === 'all' ? undefined : parseInt(facultyVal, 10),
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 250));

const typeLabelMap = computed<Record<string, string>>(() =>
    Object.fromEntries(props.typeOptions.map(option => [option.value, option.label])),
);
const successMessage = computed(() => (page.props.flash as Record<string, string> | undefined)?.success);
const errorMessage = computed(() => (page.props.flash as Record<string, string> | undefined)?.error);

const formatDate = (value: string | null) => {
    if (!value) return '—';

    return new Intl.DateTimeFormat('uz-UZ', {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const statusLabel = (value: string) => ({
    open: 'Ochiq',
    resolved: 'Hal qilingan',
    dismissed: 'Bekor qilingan',
}[value] ?? value);

const statusClass = (value: string) => ({
    open: 'bg-red-50 text-red-700 border-red-200',
    resolved: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    dismissed: 'bg-slate-50 text-slate-700 border-slate-200',
}[value] ?? 'bg-slate-50 text-slate-700 border-slate-200');

const clearFilters = () => {
    search.value = '';
    status.value = 'all';
    type.value = 'all';
    building.value = 'all';
    facultyId.value = 'all';
};

const updateStatus = (id: number, nextStatus: 'open' | 'resolved' | 'dismissed') => {
    router.post(`/anomalies/${id}/status`, {
        status: nextStatus,
    }, {
        preserveScroll: true,
        preserveState: true,
    });
};
</script>

<template>
    <Head title="Anomaliyalar" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Anomaliyalar</h1>
                    <p class="mt-1 text-sm text-muted-foreground">Dars va auditoriya holatidagi mos kelmasliklarni kuzatish sahifasi.</p>
                </div>
            </div>
            <div v-if="successMessage" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ successMessage }}
            </div>
            <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ errorMessage }}
            </div>

            <div class="grid gap-3 md:grid-cols-3">
                <Card class="border-red-200">
                    <CardContent class="flex items-center gap-3 p-4">
                        <TriangleAlert class="h-9 w-9 rounded-xl bg-red-50 p-2 text-red-600" />
                        <div>
                            <div class="text-2xl font-bold">{{ summary.open }}</div>
                            <div class="text-sm text-muted-foreground">Ochiq anomaliya</div>
                        </div>
                    </CardContent>
                </Card>
                <Card class="border-emerald-200">
                    <CardContent class="flex items-center gap-3 p-4">
                        <CheckCircle2 class="h-9 w-9 rounded-xl bg-emerald-50 p-2 text-emerald-600" />
                        <div>
                            <div class="text-2xl font-bold">{{ summary.resolved }}</div>
                            <div class="text-sm text-muted-foreground">Hal qilingan</div>
                        </div>
                    </CardContent>
                </Card>
                <Card class="border-slate-200">
                    <CardContent class="flex items-center gap-3 p-4">
                        <Ban class="h-9 w-9 rounded-xl bg-slate-50 p-2 text-slate-600" />
                        <div>
                            <div class="text-2xl font-bold">{{ summary.dismissed }}</div>
                            <div class="text-sm text-muted-foreground">Bekor qilingan</div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="flex flex-col gap-3 rounded-xl border bg-background p-3 sm:flex-row sm:flex-wrap">
                <div class="relative w-full sm:max-w-sm sm:flex-1">
                    <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input v-model="search" type="search" placeholder="Auditoriya, fan yoki tur..." class="pl-8" />
                </div>

                <Select v-model="status">
                    <SelectTrigger class="w-full sm:w-[170px]">
                        <SelectValue placeholder="Holat" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Barcha holatlar</SelectItem>
                        <SelectItem value="open">Ochiq</SelectItem>
                        <SelectItem value="resolved">Hal qilingan</SelectItem>
                        <SelectItem value="dismissed">Bekor qilingan</SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="type">
                    <SelectTrigger class="w-full sm:w-[240px]">
                        <SelectValue placeholder="Tur" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Barcha turlar</SelectItem>
                        <SelectItem v-for="option in typeOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="building">
                    <SelectTrigger class="w-full sm:w-[190px]">
                        <SelectValue placeholder="Bino" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Barcha binolar</SelectItem>
                        <SelectItem v-for="name in buildings" :key="name" :value="name">
                            {{ name }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="facultyId">
                    <SelectTrigger class="w-full sm:w-[220px]">
                        <SelectValue placeholder="Fakultet" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Barcha fakultetlar</SelectItem>
                        <SelectItem v-for="faculty in faculties" :key="faculty.id" :value="faculty.id.toString()">
                            {{ faculty.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <button
                    v-if="search || status !== 'all' || type !== 'all' || building !== 'all' || facultyId !== 'all'"
                    type="button"
                    class="text-sm text-primary hover:underline"
                    @click="clearFilters"
                >
                    Tozalash
                </button>
            </div>

            <div v-if="anomalies.data.length === 0" class="rounded-xl border bg-background p-12 text-center text-muted-foreground">
                <TriangleAlert class="mx-auto mb-3 h-8 w-8 opacity-20" />
                Filtrlar bo'yicha anomaliya topilmadi.
            </div>

            <div v-else class="space-y-3">
                <Card v-for="anomaly in anomalies.data" :key="anomaly.id" class="overflow-hidden">
                    <CardContent class="p-0">
                        <div class="flex flex-col gap-4 p-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0 flex-1 space-y-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center gap-1 rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">
                                        <TriangleAlert class="h-3.5 w-3.5" />
                                        {{ typeLabelMap[anomaly.type] ?? anomaly.type }}
                                    </span>
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium" :class="statusClass(anomaly.status)">
                                        {{ statusLabel(anomaly.status) }}
                                    </span>
                                    <span class="text-xs text-muted-foreground">
                                        Aniqlangan: {{ formatDate(anomaly.detected_at) }}
                                    </span>
                                </div>

                                <div>
                                    <h3 class="text-lg font-semibold">{{ anomaly.auditorium?.name || 'Auditoriya' }}</h3>
                                    <div class="mt-1 flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                                        <span class="inline-flex items-center gap-1">
                                            <Building2 class="h-4 w-4" />
                                            {{ anomaly.auditorium?.building?.name || 'Bino ko‘rsatilmagan' }}
                                        </span>
                                        <span v-if="anomaly.camera" class="inline-flex items-center gap-1">
                                            Kamera: {{ anomaly.camera.name }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="faculty in anomaly.auditorium?.faculties || []"
                                        :key="faculty.id"
                                        class="inline-flex items-center gap-1 rounded-full border border-border/70 bg-muted/40 px-2 py-1 text-xs"
                                    >
                                        <GraduationCap class="h-3.5 w-3.5" />
                                        {{ faculty.name }}
                                    </span>
                                </div>

                                <div v-if="anomaly.lesson" class="rounded-lg border bg-muted/20 p-3 text-sm">
                                    <div class="font-medium">{{ anomaly.lesson.subject_name }}</div>
                                    <div class="mt-1 text-muted-foreground">
                                        {{ anomaly.lesson.employee_name }} · {{ anomaly.lesson.group_name }}
                                    </div>
                                    <div class="mt-1 text-xs text-muted-foreground">
                                        {{ anomaly.lesson.start_time }} - {{ anomaly.lesson.end_time }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 lg:max-w-[240px] lg:justify-end lg:pl-4">
                                <button
                                    v-if="anomaly.status !== 'resolved'"
                                    type="button"
                                    class="inline-flex items-center rounded-md border border-emerald-200 px-3 py-2 text-sm font-medium text-emerald-700 transition hover:bg-emerald-50"
                                    @click="updateStatus(anomaly.id, 'resolved')"
                                >
                                    Hal qilindi
                                </button>
                                <button
                                    v-if="anomaly.status !== 'dismissed'"
                                    type="button"
                                    class="inline-flex items-center rounded-md border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                                    @click="updateStatus(anomaly.id, 'dismissed')"
                                >
                                    Bekor qilish
                                </button>
                                <button
                                    v-if="anomaly.status !== 'open'"
                                    type="button"
                                    class="inline-flex items-center rounded-md border border-amber-200 px-3 py-2 text-sm font-medium text-amber-700 transition hover:bg-amber-50"
                                    @click="updateStatus(anomaly.id, 'open')"
                                >
                                    Qayta ochish
                                </button>
                                <Link
                                    :href="`/anomalies/${anomaly.id}`"
                                    class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm font-medium transition hover:bg-muted"
                                >
                                    Batafsil
                                    <ArrowRight class="h-4 w-4" />
                                </Link>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Pagination :links="anomalies.links" />
        </div>
    </AppLayout>
</template>
