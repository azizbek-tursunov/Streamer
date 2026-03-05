<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="relative grid min-h-dvh flex-col items-center justify-center px-6 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
        <!-- Left panel: camera-themed background -->
        <div class="relative hidden h-full flex-col justify-between overflow-hidden bg-slate-900 p-10 text-white lg:flex">
            <!-- Animated grid pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: repeating-linear-gradient(0deg, transparent, transparent 49px, rgba(255,255,255,0.1) 49px, rgba(255,255,255,0.1) 50px), repeating-linear-gradient(90deg, transparent, transparent 49px, rgba(255,255,255,0.1) 49px, rgba(255,255,255,0.1) 50px);"></div>
            </div>

            <!-- Decorative camera lens circles -->
            <div class="absolute -right-20 -top-20 h-80 w-80 rounded-full border border-blue-500/20"></div>
            <div class="absolute -right-10 -top-10 h-60 w-60 rounded-full border border-blue-400/15"></div>
            <div class="absolute -right-0 top-0 h-40 w-40 rounded-full border border-blue-300/10"></div>
            <div class="absolute -left-16 bottom-20 h-72 w-72 rounded-full border border-blue-500/10"></div>
            <div class="absolute left-1/2 top-1/3 h-96 w-96 -translate-x-1/2 rounded-full border border-blue-500/5"></div>

            <!-- Gradient overlay -->
            <div class="absolute inset-0 bg-gradient-to-br from-slate-900 via-slate-900/95 to-blue-950"></div>

            <!-- Logo -->
            <Link href="/" class="relative z-20 flex items-center gap-3">
                <AppLogoIcon class="size-10" />
                <span class="text-xl font-bold tracking-tight">UniVision</span>
            </Link>

            <!-- Center content -->
            <div class="relative z-20 flex flex-col items-center gap-6 text-center">
                <!-- Camera icon -->
                <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-blue-500/10 ring-1 ring-blue-500/20">
                    <svg class="h-10 w-10 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Kameralarni real vaqtda kuzating</h2>
                    <p class="mt-2 text-sm text-slate-400 max-w-sm">
                        200+ kameralar, onlayn monitoring, YouTube efir va sun'iy intellekt orqali odam sanash — barchasi bir platformada
                    </p>
                </div>

                <!-- Feature badges -->
                <div class="flex flex-wrap justify-center gap-2 mt-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-500/10 px-3 py-1 text-xs font-medium text-blue-300 ring-1 ring-blue-500/20">
                        <span class="h-1.5 w-1.5 rounded-full bg-blue-400"></span>
                        Jonli efir
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-300 ring-1 ring-emerald-500/20">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                        YouTube stream
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-violet-500/10 px-3 py-1 text-xs font-medium text-violet-300 ring-1 ring-violet-500/20">
                        <span class="h-1.5 w-1.5 rounded-full bg-violet-400"></span>
                        AI odam sanash
                    </span>
                </div>
            </div>

            <!-- Footer -->
            <div class="relative z-20">
                <p class="text-sm text-slate-500">Namangan davlat universiteti</p>
            </div>
        </div>

        <!-- Right panel: login form -->
        <div class="flex items-center justify-center p-6 lg:p-8">
            <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[380px]">
                <!-- Mobile logo -->
                <div class="flex flex-col items-center gap-3 lg:hidden">
                    <AppLogoIcon class="size-12" />
                    <span class="text-lg font-bold">UniVision</span>
                </div>

                <div class="flex flex-col space-y-2 text-center">
                    <h1 class="text-2xl font-semibold tracking-tight">{{ title }}</h1>
                    <p class="text-sm text-muted-foreground">{{ description }}</p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
