<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { BreadcrumbItem, LessonSchedule } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Progress } from '@/components/ui/progress';
import { Badge } from '@/components/ui/badge';
import { Search, RefreshCw, GraduationCap, Clock, Calendar } from 'lucide-vue-next';
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

// Watch for search filter changes
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
        router.reload({ only: ['schedules'] }); // Refresh the table
    }
};

const formatTime = (time: string | null) => {
    if (!time) return '';
    return time.substring(0, 5); // Format "HH:MM:SS" to "HH:MM"
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Dars Jadvali" />

        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Bugungi Dars Jadvali</h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        Tizimdagi barcha rejalashtirilgan bugungi darslar ro'yxati.
                    </p>
                </div>
                <div v-if="hasPermission('sync-lesson-schedules')" class="flex items-center gap-2">
                    <Button @click="startSync" :disabled="isSyncing">
                        <RefreshCw class="mr-2 h-4 w-4" :class="{ 'animate-spin': isSyncing }" />
                        {{ isSyncing ? 'Sinxronlanmoqda...' : 'HEMISdan sinxronlash' }}
                    </Button>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <div class="relative w-full max-w-sm">
                    <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input
                        v-model="search"
                        type="search"
                        placeholder="Fan, o'qituvchi, guruh yoki auditoriya..."
                        class="pl-8"
                    />
                </div>
            </div>

            <div class="border rounded-lg overflow-hidden bg-card">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 border-b">
                        <tr>
                            <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground whitespace-nowrap">Vaqt</th>
                            <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Fan / O'qituvchi</th>
                            <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground whitespace-nowrap">Auditoriya / Guruh</th>
                            <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground whitespace-nowrap">Dars turi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="schedules.data.length === 0">
                            <td colspan="4" class="p-8 text-center text-muted-foreground">
                                Hech qanday dars jadvali topilmadi.
                            </td>
                        </tr>
                        <tr
                            v-for="schedule in schedules.data"
                            :key="schedule.id"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td class="p-4 align-middle font-medium whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <Clock class="w-4 h-4 text-primary" />
                                    <span>{{ formatTime(schedule.start_time) }} - {{ formatTime(schedule.end_time) }}</span>
                                </div>
                            </td>
                            <td class="p-4 align-middle">
                                <div class="flex flex-col gap-1">
                                    <span class="font-semibold text-foreground" :title="schedule.subject_name">
                                        {{ schedule.subject_name || "Noma'lum" }}
                                    </span>
                                    <span class="text-sm text-muted-foreground flex items-center gap-1.5" :title="schedule.employee_name">
                                        <GraduationCap class="w-3.5 h-3.5 flex-shrink-0" />
                                        {{ schedule.employee_name || "O'qituvchi biriktirilmagan" }}
                                    </span>
                                </div>
                            </td>
                            <td class="p-4 align-middle whitespace-nowrap">
                                <div class="flex flex-col items-start gap-1">
                                    <Badge variant="secondary">
                                        <span class="font-medium mr-1">Auditoriya:</span>
                                        {{ schedule.auditorium?.name || schedule.auditorium_code || "Noma'lum" }}
                                    </Badge>
                                    <span class="text-sm font-medium pl-1 mt-1 text-muted-foreground">
                                        Guruh: {{ schedule.group_name }}
                                    </span>
                                </div>
                            </td>
                            <td class="p-4 align-middle whitespace-nowrap">
                                <div class="flex flex-col items-start gap-1">
                                    <Badge :variant="schedule.training_type_name === 'Ma\'ruza' ? 'default' : 'outline'">
                                        {{ schedule.training_type_name || 'Noma\'lum' }}
                                    </Badge>
                                    <span class="text-xs text-muted-foreground mt-1" v-if="schedule.lesson_pair_name">
                                        {{ schedule.lesson_pair_name }}
                                    </span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="schedules.last_page > 1" class="flex items-center justify-between mt-4">
                <p class="text-sm text-muted-foreground">
                    Jami {{ schedules.total }} ta dars, sahifa {{ schedules.current_page }} / {{ schedules.last_page }}
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
                    <Button variant="secondary" @click="showSyncDialog = false">
                        Yopish
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
