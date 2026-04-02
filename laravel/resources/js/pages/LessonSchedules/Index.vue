<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { BreadcrumbItem, LessonSchedule } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Progress } from '@/components/ui/progress';
import { Search, RefreshCw, GraduationCap, Radio } from 'lucide-vue-next';
import { debounce } from 'lodash';
import axios from 'axios';
import { toast } from 'vue-sonner';
import { syncInit, syncAuditorium } from '@/actions/App/Http/Controllers/LessonScheduleController';

const props = defineProps<{
    schedules: {
        data: LessonSchedule[];
        links: any[];
        current_page: number;
        last_page: number;
        total: number;
        from: number;
        to: number;
    };
    filters: {
        search?: string;
    };
}>();

const { hasPermission } = usePermissions();

const breadcrumbs: BreadcrumbItem[] = [
    { title: "O'quv jarayoni", href: '#' },
    { title: 'Dars jadvali', href: '/lesson-schedules' },
];

const search = ref(props.filters.search || '');

// Sync Progress State
const showSyncDialog = ref(false);
const isSyncing = ref(false);
const syncProgress = ref(0);
const syncLog = ref<string[]>([]);
const syncedCount = ref(0);

watch(search, debounce((value: string) => {
    router.get(
        '/lesson-schedules',
        { search: value },
        { preserveState: true, replace: true }
    );
}, 300));

const startSync = async () => {
    try {
        isSyncing.value = true;
        showSyncDialog.value = true;
        syncProgress.value = 0;
        syncLog.value = ['Auditoriyalar ro\'yxati olinmoqda...'];
        syncedCount.value = 0;

        const initRes = await axios.post(syncInit.url());
        const auditoriums: string[] = initRes.data.auditoriums;

        if (!auditoriums || auditoriums.length === 0) {
            syncLog.value.push('Faol auditoriyalar topilmadi.');
            isSyncing.value = false;
            toast.warning('Faol auditoriyalar topilmadi.');
            return;
        }

        syncLog.value.push(`Jami ${auditoriums.length} ta auditoriya topildi. Sinxronlash boshlandi...`);
        let currentStep = 0;

        for (const code of auditoriums) {
            try {
                const syncRes = await axios.post(syncAuditorium.url(code));
                if (syncRes.data.success) {
                    syncedCount.value += syncRes.data.synced_count;
                }
            } catch (err: any) {
                syncLog.value.push(`Xatolik: ${code} - ${err.response?.data?.error || err.message}`);
                console.error(`Failed to sync auditorium ${code}`, err);
            }

            currentStep++;
            syncProgress.value = Math.floor((currentStep / auditoriums.length) * 100);
        }

        syncLog.value.push('Sinxronlash yakunlandi!');
        toast.success(`${syncedCount.value} ta dars jadvali yangilandi.`);
    } catch (e: any) {
        syncLog.value.push(`Umumiy xatolik: ${e.response?.data?.message || e.message}`);
        toast.error('Sinxronlashda xatolik yuz berdi');
        console.error(e);
    } finally {
        isSyncing.value = false;
        router.reload({ only: ['schedules'] });
    }
};

const formatTime = (time: string | null) => {
    if (!time) return '';
    return time.substring(0, 5);
};

// Derive para number from start_time (matches feedbacks page logic)
const getPara = (startTime: string | null): string => {
    if (!startTime) return '—';
    const [h, m] = startTime.split(':').map(Number);
    const mins = h * 60 + m;
    if (mins < 9 * 60 + 15)  return '1';
    if (mins < 10 * 60 + 45) return '2';
    if (mins < 12 * 60 + 30) return '3';
    if (mins < 15 * 60)      return '4';
    if (mins < 16 * 60 + 30) return '5';
    return '6';
};

// Check if lesson is currently running (using start/end timestamps)
const isNow = (schedule: LessonSchedule): boolean => {
    if (!schedule.start_timestamp || !schedule.end_timestamp) return false;
    const now = Date.now();
    return now >= new Date(schedule.start_timestamp).getTime()
        && now <= new Date(schedule.end_timestamp).getTime();
};

// Group schedules by start_time key
const grouped = computed(() => {
    const groups: Record<string, LessonSchedule[]> = {};
    for (const s of props.schedules.data) {
        const key = s.start_time?.substring(0, 5) ?? '—';
        if (!groups[key]) groups[key] = [];
        groups[key].push(s);
    }
    return groups;
});

// Color for training type
const typeColor = (name: string | undefined): string => {
    if (!name) return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
    if (name.toLowerCase().includes('ma\'ruza') || name.toLowerCase().includes('маъруза'))
        return 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300';
    if (name.toLowerCase().includes('amaliy') || name.toLowerCase().includes('амалий'))
        return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300';
    if (name.toLowerCase().includes('laborator'))
        return 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300';
    if (name.toLowerCase().includes('seminar'))
        return 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300';
    return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Dars Jadvali" />

        <div class="flex h-full flex-1 flex-col gap-4 p-4">

            <!-- Header -->
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">Bugungi Dars Jadvali</h1>
                    <p class="text-sm text-muted-foreground mt-1">Tizimdagi barcha rejalashtirilgan bugungi darslar ro'yxati.</p>
                </div>
                <Button v-if="hasPermission('sync-lesson-schedules')" @click="startSync" :disabled="isSyncing">
                    <RefreshCw class="mr-2 h-4 w-4" :class="{ 'animate-spin': isSyncing }" />
                    {{ isSyncing ? 'Sinxronlanmoqda...' : 'HEMISdan sinxronlash' }}
                </Button>
            </div>

            <!-- Search -->
            <div class="relative w-full max-w-sm">
                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Fan, o'qituvchi, guruh yoki auditoriya..."
                    class="pl-8 bg-background"
                />
            </div>

            <!-- Empty state -->
            <div v-if="schedules.data.length === 0" class="border rounded-lg p-12 text-center text-muted-foreground bg-background">
                <div class="flex flex-col items-center gap-2">
                    <GraduationCap class="h-8 w-8 opacity-20" />
                    <span>Bugun uchun dars jadvali topilmadi.</span>
                </div>
            </div>

            <!-- Schedule Table -->
            <div v-else class="border rounded-lg overflow-hidden bg-background">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-muted border-b-2 border-border">
                            <th class="px-3 py-2.5 text-center font-semibold text-muted-foreground w-[70px] border-r border-border">Para</th>
                            <th class="px-3 py-2.5 text-left font-semibold text-muted-foreground border-r border-border">Fan</th>
                            <th class="px-3 py-2.5 text-left font-semibold text-muted-foreground border-r border-border w-[200px]">O'qituvchi</th>
                            <th class="px-3 py-2.5 text-left font-semibold text-muted-foreground border-r border-border w-[110px]">Guruh</th>
                            <th class="px-3 py-2.5 text-left font-semibold text-muted-foreground border-r border-border w-[150px]">Auditoriya</th>
                            <th class="px-3 py-2.5 text-left font-semibold text-muted-foreground w-[130px]">Tur</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="(items, timeKey) in grouped" :key="timeKey">
                            <!-- Period header row -->
                            <tr class="bg-primary/8 dark:bg-primary/10 border-y border-primary/20">
                                <td colspan="6" class="px-4 py-1.5">
                                    <div class="flex items-center gap-3">
                                        <span class="font-semibold text-primary text-sm">
                                            {{ getPara(items[0].start_time) }}-para
                                        </span>
                                        <span class="text-xs text-muted-foreground">
                                            {{ formatTime(items[0].start_time) }} — {{ formatTime(items[0].end_time) }}
                                        </span>
                                        <span class="text-xs text-muted-foreground bg-muted rounded-full px-2 py-0.5">
                                            {{ items.length }} ta dars
                                        </span>
                                        <!-- Live indicator if any lesson in this period is running now -->
                                        <span
                                            v-if="items.some(isNow)"
                                            class="flex items-center gap-1 text-xs text-red-600 dark:text-red-400 font-medium"
                                        >
                                            <Radio class="h-3 w-3 animate-pulse" />
                                            Hozir
                                        </span>
                                    </div>
                                </td>
                            </tr>

                            <!-- Lesson rows for this period -->
                            <tr
                                v-for="(s, idx) in items"
                                :key="s.id"
                                class="border-b border-border/60 transition-colors hover:bg-muted/40"
                                :class="[
                                    idx % 2 === 1 ? 'bg-muted/20' : '',
                                    isNow(s) ? 'ring-1 ring-inset ring-red-400/30' : '',
                                ]"
                            >
                                <!-- Para + time -->
                                <td class="px-2 py-2.5 text-center align-middle border-r border-border/40">
                                    <div class="flex flex-col items-center gap-0.5">
                                        <span class="font-bold text-base leading-none text-foreground">{{ getPara(s.start_time) }}</span>
                                        <span class="text-[10px] text-muted-foreground leading-none">para</span>
                                        <span class="text-[10px] text-muted-foreground mt-1 leading-none">
                                            {{ formatTime(s.start_time) }}-{{ formatTime(s.end_time) }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Subject -->
                                <td class="px-3 py-2.5 align-middle border-r border-border/40">
                                    <div class="flex items-start gap-2">
                                        <div>
                                            <p class="font-medium text-sm leading-snug">{{ s.subject_name || "Noma'lum fan" }}</p>
                                            <p class="text-[10px] text-muted-foreground mt-0.5" v-if="s.lesson_pair_name">{{ s.lesson_pair_name }}</p>
                                        </div>
                                        <span
                                            v-if="isNow(s)"
                                            class="flex-shrink-0 mt-0.5 h-2 w-2 rounded-full bg-red-500 animate-pulse"
                                            title="Hozir davom etmoqda"
                                        />
                                    </div>
                                </td>

                                <!-- Teacher -->
                                <td class="px-3 py-2.5 align-middle border-r border-border/40">
                                    <div class="flex items-center gap-1.5">
                                        <GraduationCap class="h-3.5 w-3.5 text-muted-foreground flex-shrink-0" />
                                        <span class="text-sm leading-snug">{{ s.employee_name || '—' }}</span>
                                    </div>
                                </td>

                                <!-- Group -->
                                <td class="px-3 py-2.5 align-middle border-r border-border/40">
                                    <span class="text-sm font-mono">{{ s.group_name || '—' }}</span>
                                </td>

                                <!-- Auditorium -->
                                <td class="px-3 py-2.5 align-middle border-r border-border/40">
                                    <span class="text-sm font-medium">{{ s.auditorium?.name || s.auditorium_code || '—' }}</span>
                                </td>

                                <!-- Training type -->
                                <td class="px-3 py-2.5 align-middle">
                                    <span
                                        class="inline-block text-xs font-medium px-2 py-0.5 rounded-full"
                                        :class="typeColor(s.training_type_name)"
                                    >
                                        {{ s.training_type_name || "Noma'lum" }}
                                    </span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="schedules.last_page > 1" class="flex items-center justify-between mt-2">
                <p class="text-sm text-muted-foreground">
                    Jami {{ schedules.total }} ta dars &mdash; sahifa {{ schedules.current_page }} / {{ schedules.last_page }}
                </p>
                <div class="flex gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!schedules.links[0].url"
                        @click="router.get(schedules.links[0].url, {}, { preserveScroll: true, preserveState: true })"
                    >
                        Oldingisi
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!schedules.links[schedules.links.length - 1].url"
                        @click="router.get(schedules.links[schedules.links.length - 1].url, {}, { preserveScroll: true, preserveState: true })"
                    >
                        Keyingisi
                    </Button>
                </div>
            </div>
        </div>

        <!-- Sync Progress Dialog -->
        <Dialog :open="showSyncDialog" @update:open="(val) => { if (!isSyncing) showSyncDialog = val }">
            <DialogContent class="sm:max-w-md" @interact-outside="(e) => isSyncing && e.preventDefault()">
                <DialogHeader>
                    <DialogTitle>HEMIS bilan sinxronlash (Bugungi darslar)</DialogTitle>
                    <DialogDescription>
                        Iltimos, kuting. Auditoriyalar jadvali ketma-ket yangilanmoqda.
                    </DialogDescription>
                </DialogHeader>

                <div class="py-4 space-y-6">
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm font-medium">
                            <span>Sinxronlash holati</span>
                            <span>{{ syncProgress }}%</span>
                        </div>
                        <Progress :model-value="syncProgress" class="h-2" />
                    </div>

                    <div class="bg-muted rounded-md p-3 h-32 overflow-y-auto text-xs font-mono space-y-1 border">
                        <div v-for="(log, idx) in syncLog" :key="idx" :class="log.includes('Xatolik') ? 'text-destructive' : 'text-muted-foreground'">
                            > {{ log }}
                        </div>
                    </div>

                    <div v-if="!isSyncing" class="text-sm font-semibold text-green-600 dark:text-green-400 flex items-center justify-center gap-2">
                        Sinxronlash muvaffaqiyatli yakunlandi. Jami yozuvlar: {{ syncedCount }}
                    </div>
                </div>

                <div class="flex justify-end gap-2" v-if="!isSyncing">
                    <Button variant="secondary" @click="showSyncDialog = false">Yopish</Button>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
