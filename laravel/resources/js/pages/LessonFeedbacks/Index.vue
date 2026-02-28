<script setup lang="ts">
import { ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import Pagination from '@/components/Pagination.vue';
import { ThumbsUp, ThumbsDown, MessageSquareText, Calendar, Clock, MapPin, Users, User, Search, Edit, Eye, Trash2 } from 'lucide-vue-next';

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

const formatDate = (dateStr: string) => {
    const date = new Date(dateStr);
    const yyyy = date.getFullYear();
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const dd = String(date.getDate()).padStart(2, '0');
    const hh = String(date.getHours()).padStart(2, '0');
    const min = String(date.getMinutes()).padStart(2, '0');
    return `${dd}.${mm}.${yyyy} ${hh}:${min}`;
};
</script>

<template>
    <Head title="Dars tahlili" />

    <AppLayout :breadcrumbs="[
        { title: 'O\'quv jarayoni', href: '#' },
        { title: 'Dars tahlili', href: '/feedbacks' },
    ]">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        Dars tahlili
                    </h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        O'qituvchilar va ma'muriyat tomonidan dars jarayonlari haqida qoldirilgan fikr-mulohazalar.
                    </p>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row flex-wrap gap-4 mb-6 w-full">
                <!-- Search Input -->
                <div class="relative w-full sm:max-w-sm flex-1">
                    <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input 
                        v-model="search" 
                        type="search" 
                        placeholder="Fan, o'qituvchi, guruh yoki matn orqali izlash..." 
                        class="pl-8 bg-background border-input"
                    />
                </div>

                <!-- Building Filter -->
                <Select v-model="building">
                    <SelectTrigger class="w-full sm:w-[180px] bg-background">
                        <SelectValue placeholder="Bino tanlang" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Barcha binolar</SelectItem>
                        <SelectItem v-for="bname in buildings" :key="bname" :value="bname">{{ bname }}</SelectItem>
                    </SelectContent>
                </Select>

                <!-- Type Filter -->
                <Select v-model="type">
                    <SelectTrigger class="w-full sm:w-[150px] bg-background">
                        <SelectValue placeholder="Baholash turi" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Barchasi</SelectItem>
                        <SelectItem value="good">Ijobiy</SelectItem>
                        <SelectItem value="bad">Salbiy</SelectItem>
                    </SelectContent>
                </Select>

                <!-- Date Filter -->
                <div class="relative w-full sm:w-[150px]">
                    <Input 
                        v-model="date" 
                        type="date" 
                        class="w-full bg-background" 
                        title="Sana bo'yicha filtrlash"
                    />
                </div>
                
                <!-- Clear Filters Button -->
                <button 
                    v-if="search || type !== 'all' || date || building !== 'all'"
                    @click="search = ''; type = 'all'; date = ''; building = 'all';"
                    class="text-sm text-primary hover:underline self-center whitespace-nowrap px-2"
                >
                    Tozalash
                </button>
            </div>

            <div class="border rounded-lg overflow-hidden bg-background">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 border-b">
                        <tr>
                            <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Dars</th>
                            <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground whitespace-nowrap">O'qituvchi</th>
                            <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground min-w-[100px]">Guruh</th>
                            <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Auditoriya</th>
                            <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground whitespace-nowrap">Vaqt</th>
                            <th class="h-10 px-4 text-center align-middle font-medium text-muted-foreground">Holati</th>
                            <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground min-w-[200px]">Mulohaza</th>
                            <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground whitespace-nowrap">Kiritdi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="feedbacks.data.length === 0">
                            <td colspan="8" class="p-8 text-center text-muted-foreground">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <MessageSquareText class="h-8 w-8 opacity-20" />
                                    <span>Hozircha hech qanday tahlil qoldirilmagan.</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-for="feedback in feedbacks.data" :key="feedback.id" class="border-b transition-colors hover:bg-muted/50">
                            <!-- Lesson -->
                            <td class="p-4 align-top w-[200px]">
                                <span class="font-medium" :title="feedback.lesson_name ?? ''">
                                    {{ feedback.lesson_name || 'Noma\'lum fan' }}
                                </span>
                            </td>

                            <!-- Teacher -->
                            <td class="p-4 align-top w-[180px]">
                                <span class="font-medium">
                                    {{ feedback.employee_name || 'Noma\'lum o\'qituvchi' }}
                                </span>
                            </td>

                            <!-- Group -->
                            <td class="p-4 align-top">
                                <span class="text-sm">
                                    {{ feedback.group_name || 'Guruhsiz' }}
                                </span>
                            </td>

                            <!-- Auditorium -->
                            <td class="p-4 align-top w-[180px]">
                                <div class="flex flex-col gap-1 text-sm">
                                    <span class="font-medium truncate" :title="feedback.auditorium?.building?.name">
                                        {{ feedback.auditorium?.building?.name || 'Binoga biriktirilmagan' }}
                                    </span>
                                    <span class="text-xs text-muted-foreground" :title="feedback.auditorium?.name">
                                        {{ feedback.auditorium?.name || 'Topilmadi' }}
                                    </span>
                                </div>
                            </td>

                            <!-- Time -->
                            <td class="p-4 align-top">
                                <span class="text-sm border border-border/40 rounded px-1.5 py-0.5 bg-muted/60" v-if="feedback.start_time || feedback.end_time">
                                    {{ feedback.start_time?.substring(0, 5) || '?' }} - {{ feedback.end_time?.substring(0, 5) || '?' }}
                                </span>
                            </td>

                            <!-- Type badge -->
                            <td class="p-4 align-top text-center">
                                <Badge 
                                    v-if="feedback.type === 'good'" 
                                    class="bg-emerald-100 text-emerald-800 border-emerald-200 hover:bg-emerald-100 dark:bg-emerald-950/50 dark:text-emerald-400 dark:border-emerald-800 shadow-none px-2.5 mx-auto w-fit font-medium"
                                >
                                    Ijobiy
                                </Badge>
                                <Badge 
                                    v-else 
                                    class="bg-red-100 text-red-800 border-red-200 hover:bg-red-100 dark:bg-red-950/50 dark:text-red-400 dark:border-red-800 shadow-none px-2.5 mx-auto w-fit font-medium"
                                >
                                    Salbiy
                                </Badge>
                            </td>

                            <!-- Message -->
                            <td class="p-4 align-top max-w-[300px]">
                                <p class="whitespace-pre-wrap text-sm" v-if="feedback.message">{{ feedback.message }}</p>
                                <p class="text-muted-foreground/50 text-xs italic" v-else>Izohlarsiz...</p>
                            </td>

                            <!-- User & Date -->
                            <td class="p-4 align-top">
                                <div class="flex flex-col gap-1 text-xs">
                                    <span class="font-medium">{{ feedback.user?.name || 'Tizim' }}</span>
                                    <span class="text-muted-foreground">{{ formatDate(feedback.created_at) }}</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <Pagination :links="feedbacks.links" />
            </div>
        </div>
    </AppLayout>
</template>
