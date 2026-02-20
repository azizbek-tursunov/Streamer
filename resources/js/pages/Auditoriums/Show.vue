<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem, Auditorium, Lesson } from '@/types';
import VideoPlayer from '@/components/VideoPlayer.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Calendar, Clock, User, Users, BookOpen, MapPin, VideoOff } from 'lucide-vue-next';

const props = defineProps<{
    auditorium: Auditorium;
    schedule: Lesson[];
    now: number; // Server timestamp
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Ma'lumotnomalar", href: '#' },
    { title: 'Auditoriyalar', href: '/auditoriums' },
    { title: props.auditorium.name, href: `/auditoriums/${props.auditorium.code}` },
];

const currentTime = ref(Date.now());

let timer: number;
onMounted(() => {
    timer = window.setInterval(() => {
        currentTime.value = Date.now();
    }, 30_000); // Update every 30 seconds
});

onUnmounted(() => {
    clearInterval(timer);
});

const processedSchedule = computed(() => {
    return props.schedule.map(lesson => ({
        ...lesson,
        startTime: lesson.lessonPair.start_time,
        endTime: lesson.lessonPair.end_time,
    })).sort((a, b) => a.start_timestamp - b.start_timestamp);
});

const currentLesson = computed(() => {
    const nowSeconds = Math.floor(currentTime.value / 1000);
    return processedSchedule.value.find(l =>
        nowSeconds >= l.start_timestamp && nowSeconds <= l.end_timestamp
    );
});

const upcomingLessons = computed(() => {
    const nowSeconds = Math.floor(currentTime.value / 1000);
    return processedSchedule.value.filter(l => l.start_timestamp > nowSeconds);
});

const pastLessons = computed(() => {
    const nowSeconds = Math.floor(currentTime.value / 1000);
    return processedSchedule.value.filter(l =>
        l.end_timestamp < nowSeconds && l !== currentLesson.value
    );
});

const streamUrl = computed(() => {
    if (!props.auditorium.camera) return '';
    if (typeof window === 'undefined') return '';
    const host = window.location.hostname;
    return `http://viewer:viewer@${host}:8888/cam_${props.auditorium.camera.id}/index.m3u8`;
});
</script>

<template>
    <Head :title="auditorium.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <div class="flex flex-col gap-1">
                <h1 class="text-2xl font-bold tracking-tight">{{ auditorium.name }}</h1>
                <p class="text-sm text-muted-foreground flex items-center gap-2">
                    <MapPin class="h-4 w-4" />
                    {{ auditorium.building?.name || 'Bino noma\'lum' }}
                    <span v-if="auditorium.auditoriumType" class="inline-flex items-center rounded-md bg-secondary px-2 py-0.5 text-xs font-medium text-secondary-foreground">
                        {{ auditorium.auditoriumType.name }}
                    </span>
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Video -->
                <div class="lg:col-span-2 flex flex-col gap-4">
                    <div class="aspect-video w-full overflow-hidden rounded-xl border bg-black shadow-sm relative">
                        <VideoPlayer 
                            v-if="auditorium.camera"
                            :stream-url="streamUrl" 
                            autoplay
                            :rotation="auditorium.camera.rotation"
                            class="h-full w-full"
                        />
                        <div v-else class="flex h-full w-full flex-col items-center justify-center gap-3 text-muted-foreground">
                            <VideoOff class="h-10 w-10 opacity-50" />
                            <p>Kamera ulanmagan</p>
                        </div>
                        
                        <!-- Live Indicator -->
                        <div v-if="auditorium.camera" class="absolute top-4 right-4 flex items-center gap-2 rounded-full bg-black/60 px-3 py-1 text-xs font-medium text-white backdrop-blur-md">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                            </span>
                            LIVE
                        </div>
                    </div>

                    <!-- Current Lesson Card (Mobile only, or duplicate info) -->
                    <Card class="lg:hidden" v-if="currentLesson">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-lg">
                                <Clock class="h-5 w-5 text-primary" />
                                Hozirgi dars
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="grid gap-4">
                            <div class="flex flex-col">
                                <span class="text-xs text-muted-foreground">Fan</span>
                                <span class="font-semibold text-lg">{{ currentLesson.subject.name }}</span>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex flex-col">
                                    <span class="text-xs text-muted-foreground">O'qituvchi</span>
                                    <div class="flex items-center gap-2 mt-1">
                                        <User class="h-4 w-4 text-muted-foreground" />
                                        <span class="font-medium">{{ currentLesson.employee.name }}</span>
                                    </div>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-xs text-muted-foreground">Guruh</span>
                                    <div class="flex items-center gap-2 mt-1">
                                        <Users class="h-4 w-4 text-muted-foreground" />
                                        <span class="font-medium">{{ currentLesson.group.name }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 mt-2 text-sm font-medium text-primary bg-primary/10 p-2 rounded-md justify-center">
                                {{ currentLesson.startTime }} - {{ currentLesson.endTime }}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Right Column: Sidebar info -->
                <div class="flex flex-col gap-6">
                    <!-- Current Lesson (Desktop) -->
                    <Card class="hidden lg:block border-primary/20 shadow-md">
                        <CardHeader class="pb-3">
                            <CardTitle class="flex items-center gap-2">
                                <span class="relative flex h-3 w-3 mr-1">
                                    <span v-if="currentLesson" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3" :class="currentLesson ? 'bg-primary' : 'bg-muted'"></span>
                                </span>
                                {{ currentLesson ? 'Hozirgi dars' : 'Ayni paytda dars yo\'q' }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="currentLesson" class="grid gap-4">
                                <div class="flex flex-col">
                                    <span class="text-xs text-muted-foreground">Fan</span>
                                    <span class="font-bold text-xl leading-tight text-primary">{{ currentLesson.subject.name }}</span>
                                    <span class="text-xs text-muted-foreground mt-1">{{ currentLesson.trainingType.name }}</span>
                                </div>
                                <hr class="border-dashed" />
                                <div class="grid gap-3">
                                    <div class="flex items-start gap-3">
                                        <User class="h-4 w-4 mt-0.5 text-muted-foreground" />
                                        <div class="flex flex-col">
                                            <span class="text-xs text-muted-foreground">O'qituvchi</span>
                                            <span class="font-medium">{{ currentLesson.employee.name }}</span>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <Users class="h-4 w-4 mt-0.5 text-muted-foreground" />
                                        <div class="flex flex-col">
                                            <span class="text-xs text-muted-foreground">Guruh</span>
                                            <span class="font-medium">{{ currentLesson.group.name }}</span>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <Clock class="h-4 w-4 mt-0.5 text-muted-foreground" />
                                        <div class="flex flex-col">
                                            <span class="text-xs text-muted-foreground">Vaqt</span>
                                            <span class="font-medium">{{ currentLesson.startTime }} - {{ currentLesson.endTime }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="py-8 flex flex-col items-center justify-center text-center text-muted-foreground">
                                <BookOpen class="h-10 w-10 mb-3 opacity-20" />
                                <p>Xona bo'sh</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Upcoming Lessons -->
                    <Card>
                        <CardHeader class="pb-3">
                            <CardTitle class="text-base font-medium">Keyingi darslar</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="upcomingLessons.length > 0" class="space-y-4">
                                <div v-for="lesson in upcomingLessons" :key="lesson.id" class="flex items-start gap-3 relative pb-4 last:pb-0">
                                    <!-- Timeline line -->
                                    <div class="absolute left-[5px] top-6 bottom-0 w-px bg-border last:hidden"></div>
                                    
                                    <div class="relative z-10 flex h-2.5 w-2.5 shrink-0 translate-y-1.5 items-center justify-center rounded-full bg-primary/20 ring-4 ring-background">
                                        <div class="h-1.5 w-1.5 rounded-full bg-primary"></div>
                                    </div>
                                    
                                    <div class="flex flex-col gap-1 w-full min-w-0">
                                        <div class="flex items-center justify-between text-xs text-muted-foreground">
                                            <span>{{ lesson.startTime }} - {{ lesson.endTime }}</span>
                                            <Badge variant="outline" class="text-[10px] px-1 py-0 h-4">{{ lesson.trainingType.name }}</Badge>
                                        </div>
                                        <span class="font-medium text-sm truncate" :title="lesson.subject.name">{{ lesson.subject.name }}</span>
                                        <div class="flex items-center justify-between text-xs text-muted-foreground mt-0.5">
                                            <span>{{ lesson.employee.name }}</span>
                                            <span>{{ lesson.group.name }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-sm text-muted-foreground text-center py-4">
                                Bugun boshqa dars yo'q
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
