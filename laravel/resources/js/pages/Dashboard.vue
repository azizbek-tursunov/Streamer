<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Camera, Building2, Play, Youtube } from 'lucide-vue-next';

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

        </div>
    </AppLayout>
</template>
