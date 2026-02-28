<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Camera, Building2, Play, Users, DoorOpen, Youtube, ArrowRight } from 'lucide-vue-next';

defineProps<{
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
}>();

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
                        <CardTitle class="text-sm font-medium">Jami Auditoriyalar</CardTitle>
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

            <!-- Quick Actions -->
            <div class="mt-8">
                <h3 class="text-lg font-medium mb-4">Tezkor Boshqaruv</h3>
                <div class="grid gap-4 md:grid-cols-3">
                    <Link href="/cameras" class="group relative rounded-lg border p-6 hover:border-foreground/50 hover:bg-muted/50 transition-all flex flex-col justify-between h-full bg-card shadow-sm">
                        <div class="flex flex-col space-y-2">
                            <Camera class="h-5 w-5 text-foreground/70 group-hover:text-foreground transition-colors" />
                            <h4 class="font-semibold tracking-tight text-lg mt-4">Kameralar</h4>
                            <p class="text-sm text-muted-foreground">Tizimdagi barcha IP kameralarni sozlash, holatini tekshirish va kuzatish.</p>
                        </div>
                        <div class="flex items-center text-sm font-medium text-foreground mt-6">
                            Boshqarish <ArrowRight class="ml-1 h-4 w-4 opacity-0 -translate-x-2 transition-all group-hover:opacity-100 group-hover:translate-x-0" />
                        </div>
                    </Link>

                    <Link href="/auditoriums" class="group relative rounded-lg border p-6 hover:border-foreground/50 hover:bg-muted/50 transition-all flex flex-col justify-between h-full bg-card shadow-sm">
                        <div class="flex flex-col space-y-2">
                            <DoorOpen class="h-5 w-5 text-foreground/70 group-hover:text-foreground transition-colors" />
                            <h4 class="font-semibold tracking-tight text-lg mt-4">Auditoriyalar</h4>
                            <p class="text-sm text-muted-foreground">O'quv xonalarini kameralarga biriktirish va dars jadvallarini nazorat qilish.</p>
                        </div>
                        <div class="flex items-center text-sm font-medium text-foreground mt-6">
                            Ko'rish <ArrowRight class="ml-1 h-4 w-4 opacity-0 -translate-x-2 transition-all group-hover:opacity-100 group-hover:translate-x-0" />
                        </div>
                    </Link>

                    <Link href="/cameras" class="group relative rounded-lg border p-6 hover:border-foreground/50 hover:bg-muted/50 transition-all flex flex-col justify-between h-full bg-card shadow-sm">
                        <div class="flex flex-col space-y-2">
                            <Youtube class="h-5 w-5 text-foreground/70 group-hover:text-foreground transition-colors" />
                            <h4 class="font-semibold tracking-tight text-lg mt-4">YouTube Efirlar</h4>
                            <p class="text-sm text-muted-foreground">Kameralar faoliyatini to'g'ridan-to'g'ri YouTube platformasiga ochiq translyatsiya qilish.</p>
                        </div>
                        <div class="flex items-center text-sm font-medium text-foreground mt-6">
                            Boshqarish <ArrowRight class="ml-1 h-4 w-4 opacity-0 -translate-x-2 transition-all group-hover:opacity-100 group-hover:translate-x-0" />
                        </div>
                    </Link>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
