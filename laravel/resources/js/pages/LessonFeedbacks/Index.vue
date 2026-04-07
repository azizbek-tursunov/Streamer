<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import { MessageSquareText, Search, Download, ImageIcon, ThumbsUp, ThumbsDown } from 'lucide-vue-next';

const props = defineProps<{
    feedbacks: {
        data: Array<{
            id: number;
            lesson_name: string | null;
            employee_name: string | null;
            group_name: string | null;
            start_time: string | null;
            end_time: string | null;
            type: 'good' | 'bad';
            message: string | null;
            snapshot_url: string | null;
            created_at: string;
            user?: { name: string };
            auditorium?: { name: string; building?: { name: string } };
        }>;
        links: any[];
    };
    buildings: string[];
    filters: {
        search?: string;
        type?: string;
        date?: string;
        building?: string;
    };
}>();

const search = ref(props.filters.search || '');
const type = ref(props.filters.type || 'all');
const date = ref(props.filters.date || '');
const building = ref(props.filters.building || 'all');

watch([search, type, date, building], debounce(([searchVal, typeVal, dateVal, buildingVal]) => {
    router.get('/feedbacks', {
        search: searchVal,
        type: typeVal === 'all' ? undefined : typeVal,
        date: dateVal || undefined,
        building: buildingVal === 'all' ? undefined : buildingVal,
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300));

const previewImage = ref<string | null>(null);

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    if (search.value) params.set('search', search.value);
    if (type.value !== 'all') params.set('type', type.value);
    if (date.value) params.set('date', date.value);
    if (building.value !== 'all') params.set('building', building.value);
    const qs = params.toString();
    return '/feedbacks/export' + (qs ? '?' + qs : '');
});

const DAYS_UZ = ['Yakshanba', 'Dushanba', 'Seshanba', 'Chorshanba', 'Payshanba', 'Juma', 'Shanba'];

const formatDateHeader = (dateStr: string) => {
    const d = new Date(dateStr);
    const dd = String(d.getDate()).padStart(2, '0');
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const yyyy = d.getFullYear();
    return `${dd}.${mm}.${yyyy} — ${DAYS_UZ[d.getDay()]}`;
};

const formatTime = (dateStr: string) => {
    const d = new Date(dateStr);
    return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
};

const getDateKey = (dateStr: string) => {
    return new Date(dateStr).toISOString().slice(0, 10);
};

const getPara = (startTime: string | null): string => {
    if (!startTime) return '—';
    const [h, m] = startTime.split(':').map(Number);
    const mins = h * 60 + m;
    if (mins < 9 * 60 + 15) return '1';
    if (mins < 10 * 60 + 45) return '2';
    if (mins < 12 * 60 + 30) return '3';
    if (mins < 15 * 60) return '4';
    if (mins < 16 * 60 + 30) return '5';
    return '6';
};

// Group feedbacks by date
const grouped = computed(() => {
    const groups: Record<string, typeof props.feedbacks.data> = {};
    for (const fb of props.feedbacks.data) {
        const key = getDateKey(fb.created_at);
        if (!groups[key]) groups[key] = [];
        groups[key].push(fb);
    }
    return groups;
});
</script>

<template>
    <Head title="Dars tahlili" />

    <AppLayout :breadcrumbs="[
        { title: 'O\'quv jarayoni', href: '#' },
        { title: 'Dars tahlili', href: '/feedbacks' },
    ]">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">Dars tahlili</h1>
                    <p class="text-sm text-muted-foreground mt-1">O'qituvchilar va ma'muriyat tomonidan dars jarayonlari haqida qoldirilgan fikr-mulohazalar.</p>
                </div>
                <a :href="exportUrl">
                    <Button variant="outline" size="sm" class="gap-2">
                        <Download class="h-4 w-4" />
                        Excel
                    </Button>
                </a>
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row flex-wrap gap-3 w-full">
                <div class="relative w-full sm:max-w-sm flex-1">
                    <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input
                        v-model="search"
                        type="search"
                        placeholder="Fan, o'qituvchi, guruh yoki matn..."
                        class="pl-8 bg-background"
                    />
                </div>

                <Select v-model="building">
                    <SelectTrigger class="w-full sm:w-[180px] bg-background">
                        <SelectValue placeholder="Bino tanlang" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Barcha binolar</SelectItem>
                        <SelectItem v-for="bname in buildings" :key="bname" :value="bname">{{ bname }}</SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="type">
                    <SelectTrigger class="w-full sm:w-[140px] bg-background">
                        <SelectValue placeholder="Holati" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Barchasi</SelectItem>
                        <SelectItem value="good">Ijobiy</SelectItem>
                        <SelectItem value="bad">Salbiy</SelectItem>
                    </SelectContent>
                </Select>

                <Input
                    v-model="date"
                    type="date"
                    class="w-full sm:w-[150px] bg-background"
                />

                <button
                    v-if="search || type !== 'all' || date || building !== 'all'"
                    @click="search = ''; type = 'all'; date = ''; building = 'all';"
                    class="text-sm text-primary hover:underline self-center whitespace-nowrap px-2"
                >
                    Tozalash
                </button>
            </div>

            <!-- Empty state -->
            <div v-if="feedbacks.data.length === 0" class="border rounded-lg p-12 text-center text-muted-foreground bg-background">
                <div class="flex flex-col items-center gap-2">
                    <MessageSquareText class="h-8 w-8 opacity-20" />
                    <span>Hech qanday tahlil topilmadi.</span>
                </div>
            </div>

            <!-- Schedule Table -->
            <div v-else class="border rounded-lg overflow-hidden bg-background">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-muted border-b-2 border-border">
                            <th class="px-3 py-2.5 text-center font-semibold text-muted-foreground border-r border-border w-[52px]">Rasm</th>
                            <th class="px-3 py-2.5 text-center font-semibold text-muted-foreground w-[70px] border-r border-border">Para</th>
                            <th class="px-3 py-2.5 text-left font-semibold text-muted-foreground border-r border-border">Fan</th>
                            <th class="px-3 py-2.5 text-left font-semibold text-muted-foreground border-r border-border w-[190px]">O'qituvchi</th>
                            <th class="px-3 py-2.5 text-left font-semibold text-muted-foreground border-r border-border w-[110px]">Guruh</th>
                            <th class="px-3 py-2.5 text-left font-semibold text-muted-foreground border-r border-border w-[160px]">Auditoriya</th>
                            <th class="px-3 py-2.5 text-center font-semibold text-muted-foreground border-r border-border w-[80px]">Holat</th>
                            <th class="px-3 py-2.5 text-left font-semibold text-muted-foreground border-r border-border">Mulohaza</th>
                            <th class="px-3 py-2.5 text-left font-semibold text-muted-foreground w-[130px]">Kiritdi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="(items, dateKey) in grouped" :key="dateKey">
                            <!-- Date header row -->
                            <tr class="bg-primary/8 dark:bg-primary/10 border-y border-primary/20">
                                <td colspan="9" class="px-4 py-1.5">
                                    <div class="flex items-center gap-3">
                                        <span class="font-semibold text-primary text-sm">{{ formatDateHeader(items[0].created_at) }}</span>
                                        <span class="text-xs text-muted-foreground bg-muted rounded-full px-2 py-0.5">{{ items.length }} ta yozuv</span>
                                    </div>
                                </td>
                            </tr>

                            <!-- Feedback rows for that date -->
                            <tr
                                v-for="(fb, idx) in items"
                                :key="fb.id"
                                class="border-b border-border/60 transition-colors hover:bg-muted/40"
                                :class="idx % 2 === 1 ? 'bg-muted/20' : ''"
                            >
                                <!-- Snapshot -->
                                <td class="px-2 py-2.5 text-center align-middle border-r border-border/40">
                                    <button
                                        v-if="fb.snapshot_url"
                                        @click="previewImage = fb.snapshot_url"
                                        class="inline-block rounded overflow-hidden border border-border hover:ring-2 hover:ring-primary transition-all"
                                    >
                                        <img :src="fb.snapshot_url" class="h-9 w-14 object-cover" alt="Snapshot" />
                                    </button>
                                    <ImageIcon v-else class="h-4 w-4 mx-auto text-muted-foreground/30" />
                                </td>

                                <!-- Para + time -->
                                <td class="px-2 py-2.5 text-center align-middle border-r border-border/40">
                                    <div class="flex flex-col items-center gap-0.5">
                                        <span class="font-bold text-base leading-none text-foreground">{{ getPara(fb.start_time) }}</span>
                                        <span class="text-[10px] text-muted-foreground leading-none">para</span>
                                        <span class="text-[10px] text-muted-foreground mt-1 leading-none">
                                            {{ fb.start_time?.substring(0, 5) || '?' }}-{{ fb.end_time?.substring(0, 5) || '?' }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Subject -->
                                <td class="px-3 py-2.5 align-middle border-r border-border/40">
                                    <span class="font-medium text-sm leading-snug">{{ fb.lesson_name || 'Noma\'lum fan' }}</span>
                                </td>

                                <!-- Teacher -->
                                <td class="px-3 py-2.5 align-middle border-r border-border/40">
                                    <span class="text-sm">{{ fb.employee_name || '—' }}</span>
                                </td>

                                <!-- Group -->
                                <td class="px-3 py-2.5 align-middle border-r border-border/40">
                                    <span class="text-sm font-mono">{{ fb.group_name || '—' }}</span>
                                </td>

                                <!-- Auditorium -->
                                <td class="px-3 py-2.5 align-middle border-r border-border/40">
                                    <div class="text-sm leading-snug">
                                        <div class="text-muted-foreground text-xs">{{ fb.auditorium?.building?.name || '' }}</div>
                                        <div class="font-medium">{{ fb.auditorium?.name || '—' }}</div>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-2 py-2.5 text-center align-middle border-r border-border/40">
                                    <div v-if="fb.type === 'good'" class="flex flex-col items-center gap-0.5">
                                        <ThumbsUp class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                                        <span class="text-[10px] text-emerald-700 dark:text-emerald-400 font-medium">Ijobiy</span>
                                    </div>
                                    <div v-else class="flex flex-col items-center gap-0.5">
                                        <ThumbsDown class="h-4 w-4 text-red-500 dark:text-red-400" />
                                        <span class="text-[10px] text-red-600 dark:text-red-400 font-medium">Salbiy</span>
                                    </div>
                                </td>

                                <!-- Message -->
                                <td class="px-3 py-2.5 align-middle border-r border-border/40 max-w-[260px]">
                                    <p class="text-sm whitespace-pre-wrap" v-if="fb.message">{{ fb.message }}</p>
                                    <p class="text-xs text-muted-foreground/40 italic" v-else>—</p>
                                </td>

                                <!-- Entered by -->
                                <td class="px-3 py-2.5 align-middle">
                                    <div class="flex flex-col gap-0.5 text-xs">
                                        <span class="font-medium text-foreground">{{ fb.user?.name || 'Tizim' }}</span>
                                        <span class="text-muted-foreground">{{ formatTime(fb.created_at) }}</span>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="mt-2">
                <Pagination :links="feedbacks.links" />
            </div>
        </div>

        <!-- Image Preview Modal -->
        <Teleport to="body">
            <div
                v-if="previewImage"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 cursor-pointer"
                @click="previewImage = null"
            >
                <img :src="previewImage" class="max-h-[90vh] max-w-[90vw] rounded-lg shadow-2xl" alt="Preview" />
            </div>
        </Teleport>
    </AppLayout>
</template>
