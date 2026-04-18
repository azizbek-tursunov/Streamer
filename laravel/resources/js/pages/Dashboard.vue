<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { computed } from 'vue';
import { Camera, Building2, Play, Youtube, TriangleAlert, BookOpen, Users, ArrowRight } from 'lucide-vue-next';

const props = defineProps<{
    stats: {
        cameras: {
            total: number;
            active: number;
            public: number;
            streaming: number;
        };
        auditoriums: {
            total: number;
            active: number;
            with_camera: number;
        };
        faculties: number;
    };
    deanDashboard: null | {
        faculty_name?: string | null;
        scope: {
            auditoriums: number;
            with_camera: number;
        };
        today: {
            active_lessons: number;
            open_anomalies: number;
            lesson_no_people: number;
            people_no_lesson: number;
        };
        recent_anomalies: Array<{
            id: number;
            type: string;
            detected_at: string | null;
            auditorium: {
                id: number | null;
                name: string | null;
                building_name: string | null;
            };
            lesson: {
                subject_name: string;
                employee_name: string;
            } | null;
        }>;
    };
}>();

const page = usePage();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Boshqaruv Paneli',
        href: dashboard().url,
    },
];

const months = ['yanvar', 'fevral', 'mart', 'aprel', 'may', 'iyun', 'iyul', 'avgust', 'sentyabr', 'oktyabr', 'noyabr', 'dekabr'];
const days = ['Yakshanba', 'Dushanba', 'Seshanba', 'Chorshanba', 'Payshanba', 'Juma', 'Shanba'];
const now = new Date();
const currentDate = `${now.getDate()}-${months[now.getMonth()]}, ${now.getFullYear()}-yil, ${days[now.getDay()]}`;

const anomalyLabels: Record<string, string> = {
    lesson_no_people: "Dars bor, odam yo'q",
    people_no_lesson: "Odam bor, dars yo'q",
    camera_offline_during_lesson: 'Dars paytida kamera uzilgan',
    stale_snapshot: 'Snapshot eskirgan',
};

const isDean = computed(() => (page.props.auth?.user?.roles || []).includes('deans'));

const formatDate = (value: string | null) => {
    if (!value) return '—';

    return new Intl.DateTimeFormat('uz-UZ', {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};
</script>

<template>
    <Head title="Boshqaruv Paneli" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-4 p-8 pt-6">
            <!-- Header Section -->
            <div class="flex items-center justify-between space-y-2">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight">Kuzatuv Tizimi</h2>
                    <p class="text-muted-foreground mt-1">
                        UniVision markazlashtirilgan o'quv va kuzatuv platformasi.
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="bg-secondary text-secondary-foreground px-3 py-1.5 rounded-md text-sm font-medium border shadow-sm">
                        {{ currentDate }}
                    </div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4 mt-6">
                <!-- Metrics -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Jami Kameralar</CardTitle>
                        <Camera class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.cameras.total }}</div>
                        <p class="text-xs text-muted-foreground mt-1">
                            Shundan <span class="font-medium text-foreground">{{ stats.cameras.active }}</span> tasi faol monitoringda
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Ommaviy Kameralar</CardTitle>
                        <Play class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.cameras.public }}</div>
                        <p class="text-xs text-muted-foreground mt-1">
                            Platformaga ochiq ruxsat bilan
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Faol Auditoriyalar</CardTitle>
                        <Building2 class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.auditoriums.total }}</div>
                        <p class="text-xs text-muted-foreground mt-1">
                            <span class="font-medium text-foreground">{{ stats.auditoriums.with_camera }}</span> tasi kamera bilan jihozlangan
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">YouTube Efirlar</CardTitle>
                        <Youtube class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.cameras.streaming }}</div>
                        <p class="text-xs text-muted-foreground mt-1">
                            Hozirda translyatsiya qilinmoqda
                        </p>
                    </CardContent>
                </Card>
            </div>

            <div v-if="deanDashboard" class="mt-8 space-y-4">
                <div class="flex flex-col gap-1">
                    <h3 class="text-2xl font-semibold tracking-tight">
                        {{ isDean && deanDashboard.faculty_name ? `${deanDashboard.faculty_name} fakulteti` : "O'quv jarayoni" }}
                    </h3>
                    <p class="text-sm text-muted-foreground">
                        Fakultet va auditoriyalar bo'yicha bugungi operativ ko'rsatkichlar.
                    </p>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <Card class="border-primary/20">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Bugungi faol darslar</CardTitle>
                            <BookOpen class="h-4 w-4 text-primary" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ deanDashboard.today.active_lessons }}</div>
                            <p class="text-xs text-muted-foreground mt-1">
                                Kuzatuvdagi auditoriyalar: {{ deanDashboard.scope.auditoriums }}
                            </p>
                        </CardContent>
                    </Card>

                    <Card class="border-red-200">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Ochiq anomaliyalar</CardTitle>
                            <TriangleAlert class="h-4 w-4 text-red-500" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ deanDashboard.today.open_anomalies }}</div>
                            <p class="text-xs text-muted-foreground mt-1">
                                Darhol ko'rib chiqilishi kerak
                            </p>
                        </CardContent>
                    </Card>

                    <Card class="border-amber-200">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Dars bor, odam yo'q</CardTitle>
                            <Users class="h-4 w-4 text-amber-500" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ deanDashboard.today.lesson_no_people }}</div>
                            <p class="text-xs text-muted-foreground mt-1">
                                Hozirgi nofaol dars holatlari
                            </p>
                        </CardContent>
                    </Card>

                    <Card class="border-sky-200">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Odam bor, dars yo'q</CardTitle>
                            <Building2 class="h-4 w-4 text-sky-500" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ deanDashboard.today.people_no_lesson }}</div>
                            <p class="text-xs text-muted-foreground mt-1">
                                Rejasiz foydalanish ehtimoli
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <div class="grid gap-4 xl:grid-cols-[1.4fr,1fr]">
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between">
                            <div>
                                <CardTitle>Yaqin anomaliyalar</CardTitle>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    So'nggi aniqlangan ochiq holatlar.
                                </p>
                            </div>
                            <Link href="/anomalies" class="inline-flex items-center gap-2 text-sm font-medium text-primary hover:underline">
                                Barchasi
                                <ArrowRight class="h-4 w-4" />
                            </Link>
                        </CardHeader>
                        <CardContent>
                            <div v-if="deanDashboard.recent_anomalies.length === 0" class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
                                Hozircha ochiq anomaliya yo'q.
                            </div>
                            <div v-else class="space-y-3">
                                <Link
                                    v-for="item in deanDashboard.recent_anomalies"
                                    :key="item.id"
                                    :href="`/anomalies/${item.id}`"
                                    class="block rounded-lg border p-3 transition hover:bg-muted/40"
                                >
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="min-w-0">
                                            <div class="text-sm font-semibold">
                                                {{ anomalyLabels[item.type] ?? item.type }}
                                            </div>
                                            <div class="mt-1 text-sm text-muted-foreground">
                                                {{ item.auditorium.name }}
                                                <span v-if="item.auditorium.building_name">· {{ item.auditorium.building_name }}</span>
                                            </div>
                                            <div v-if="item.lesson" class="mt-1 text-xs text-muted-foreground">
                                                {{ item.lesson.subject_name }} · {{ item.lesson.employee_name }}
                                            </div>
                                        </div>
                                        <div class="shrink-0 text-xs text-muted-foreground">
                                            {{ formatDate(item.detected_at) }}
                                        </div>
                                    </div>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Qamrov</CardTitle>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Siz ko'rayotgan auditoriyalar holati.
                            </p>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="rounded-lg border p-4">
                                <div class="text-sm text-muted-foreground">Jami auditoriyalar</div>
                                <div class="mt-1 text-2xl font-bold">{{ deanDashboard.scope.auditoriums }}</div>
                            </div>
                            <div class="rounded-lg border p-4">
                                <div class="text-sm text-muted-foreground">Kamera bilan</div>
                                <div class="mt-1 text-2xl font-bold">{{ deanDashboard.scope.with_camera }}</div>
                            </div>
                            <Link href="/auditoriums" class="inline-flex items-center gap-2 text-sm font-medium text-primary hover:underline">
                                Auditoriyalar sahifasiga o'tish
                                <ArrowRight class="h-4 w-4" />
                            </Link>
                        </CardContent>
                    </Card>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
