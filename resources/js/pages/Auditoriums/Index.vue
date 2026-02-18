<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { BreadcrumbItem, Auditorium } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import {
    Search,
    Building2,
    Users,
    DoorOpen,
    MapPin,
    Layers,
    RefreshCw,
    CheckCircle,
    Eye,
    EyeOff,
} from 'lucide-vue-next';
import { debounce } from 'lodash';

const props = defineProps<{
    auditoriums: Auditorium[];
    filters: {
        search?: string;
    };
    lastSyncedAt: string | null;
}>();

const page = usePage();

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Ma'lumotnomalar", href: '#' },
    { title: 'Auditoriyalar', href: '/auditoriums' },
];

const search = ref(props.filters.search || '');
const selectedType = ref<string | null>(null);
const showInactive = ref(false);
const syncing = ref(false);

watch(search, debounce((value: string) => {
    router.get('/auditoriums', { search: value }, { preserveState: true, replace: true });
}, 300));

// Extract unique auditorium types
const auditoriumTypes = computed(() => {
    const types = new Map<string, string>();
    props.auditoriums.forEach(a => {
        if (a.auditoriumType?.code) {
            types.set(a.auditoriumType.code, a.auditoriumType.name);
        }
    });
    return Array.from(types.entries()).map(([code, name]) => ({ code, name }));
});

// Filter by active status, then by selected type
const filteredAuditoriums = computed(() => {
    let result = props.auditoriums;
    if (!showInactive.value) {
        result = result.filter(a => a.active);
    }
    if (selectedType.value) {
        result = result.filter(a => a.auditoriumType?.code === selectedType.value);
    }
    return result;
});

const inactiveCount = computed(() => props.auditoriums.filter(a => !a.active).length);

// Group by building
const groupedByBuilding = computed(() => {
    const groups = new Map<string, { name: string; auditoriums: Auditorium[] }>();

    filteredAuditoriums.value.forEach(a => {
        const buildingName = a.building?.name ?? 'Bino ko\'rsatilmagan';
        const buildingId = a.building?.id?.toString() ?? 'unknown';

        if (!groups.has(buildingId)) {
            groups.set(buildingId, { name: buildingName, auditoriums: [] });
        }
        groups.get(buildingId)!.auditoriums.push(a);
    });

    return Array.from(groups.entries()).map(([id, group]) => ({
        id,
        name: group.name,
        auditoriums: group.auditoriums,
        activeCount: group.auditoriums.filter(a => a.active).length,
        totalCapacity: group.auditoriums.reduce((sum, a) => sum + (a.volume || 0), 0),
    }));
});


// Stats
const totalActive = computed(() => filteredAuditoriums.value.filter(a => a.active).length);
const totalCapacity = computed(() => filteredAuditoriums.value.reduce((sum, a) => sum + (a.volume || 0), 0));

const toggleType = (code: string) => {
    selectedType.value = selectedType.value === code ? null : code;
};

const syncFromApi = () => {
    syncing.value = true;
    router.post('/auditoriums/sync', {}, {
        preserveState: false,
        onFinish: () => {
            syncing.value = false;
        },
    });
};

const formatDate = (dateStr: string | null): string => {
    if (!dateStr) return 'Hali sinxronlanmagan';
    const date = new Date(dateStr);
    return date.toLocaleDateString('uz-UZ', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getTypeColor = (code: string): string => {
    const colors: Record<string, string> = {
        '11': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        '12': 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400',
        '13': 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        '14': 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        '15': 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
    };
    return colors[code] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
};

// Flash message
const successMessage = computed(() => (page.props.flash as Record<string, string> | undefined)?.success);
</script>

<template>
    <Head title="Auditoriyalar" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col gap-1">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-bold tracking-tight">Auditoriyalar</h1>
                    <div class="flex items-center gap-3">
                        <span v-if="lastSyncedAt" class="hidden text-xs text-muted-foreground sm:inline">
                            Oxirgi sinxron: {{ formatDate(lastSyncedAt) }}
                        </span>
                        <Button
                            @click="syncFromApi"
                            :disabled="syncing"
                            variant="outline"
                            size="sm"
                        >
                            <RefreshCw class="mr-2 h-4 w-4" :class="{ 'animate-spin': syncing }" />
                            {{ syncing ? 'Sinxronlanmoqda...' : 'HEMIS dan sinxronlash' }}
                        </Button>
                    </div>
                </div>
                <p class="text-sm text-muted-foreground">HEMIS tizimidan sinxronlangan auditoriyalar</p>
            </div>

            <!-- Success Toast -->
            <div
                v-if="successMessage"
                class="flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-400"
            >
                <CheckCircle class="h-4 w-4 shrink-0" />
                {{ successMessage }}
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <Card class="py-4">
                    <CardContent class="flex items-center gap-3 pb-0">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                            <DoorOpen class="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold">{{ filteredAuditoriums.length }}</p>
                            <p class="text-xs text-muted-foreground">Auditoriyalar soni</p>
                        </div>
                    </CardContent>
                </Card>
                <Card class="py-4">
                    <CardContent class="flex items-center gap-3 pb-0">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10">
                            <MapPin class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold">{{ totalActive }}</p>
                            <p class="text-xs text-muted-foreground">Faol auditoriyalar</p>
                        </div>
                    </CardContent>
                </Card>
                <Card class="py-4">
                    <CardContent class="flex items-center gap-3 pb-0">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-violet-500/10">
                            <Users class="h-5 w-5 text-violet-600 dark:text-violet-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold">{{ totalCapacity.toLocaleString() }}</p>
                            <p class="text-xs text-muted-foreground">Umumiy sig'im</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Search + Type Filters -->
            <div class="flex flex-col gap-3">
                <div class="relative w-full max-w-md">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        v-model="search"
                        placeholder="Auditoriya nomi bo'yicha qidirish..."
                        class="pl-9"
                    />
                </div>

                <div v-if="auditoriumTypes.length > 0" class="flex flex-wrap items-center gap-2">
                    <Layers class="h-4 w-4 text-muted-foreground" />
                    <span class="text-xs font-medium text-muted-foreground mr-1">Turi:</span>
                    <button
                        v-for="type in auditoriumTypes"
                        :key="type.code"
                        class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium transition-all cursor-pointer"
                        :class="selectedType === type.code
                            ? 'bg-primary text-primary-foreground border-primary shadow-sm'
                            : 'bg-background hover:bg-muted border-border text-muted-foreground hover:text-foreground'"
                        @click="toggleType(type.code)"
                    >
                        {{ type.name }}
                    </button>
                    <button
                        v-if="selectedType"
                        class="text-xs text-muted-foreground hover:text-foreground underline cursor-pointer ml-1"
                        @click="selectedType = null"
                    >
                        Tozalash
                    </button>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        v-if="inactiveCount > 0"
                        class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium transition-all cursor-pointer"
                        :class="showInactive
                            ? 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800'
                            : 'bg-background hover:bg-muted border-border text-muted-foreground hover:text-foreground'"
                        @click="showInactive = !showInactive"
                    >
                        <Eye v-if="showInactive" class="h-3 w-3" />
                        <EyeOff v-else class="h-3 w-3" />
                        Nofaollar ({{ inactiveCount }})
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="auditoriums.length === 0" class="flex flex-col items-center justify-center gap-4 py-16 text-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-muted">
                    <Building2 class="h-8 w-8 text-muted-foreground" />
                </div>
                <div>
                    <p class="font-medium">Auditoriyalar topilmadi</p>
                    <p class="text-sm text-muted-foreground mb-4">
                        HEMIS tizimidan ma'lumotlarni sinxronlang
                    </p>
                    <Button @click="syncFromApi" :disabled="syncing">
                        <RefreshCw class="mr-2 h-4 w-4" :class="{ 'animate-spin': syncing }" />
                        {{ syncing ? 'Sinxronlanmoqda...' : 'Sinxronlash' }}
                    </Button>
                </div>
            </div>

            <!-- No Results After Filter -->
            <div v-else-if="filteredAuditoriums.length === 0" class="flex flex-col items-center justify-center gap-3 py-16 text-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-muted">
                    <Search class="h-8 w-8 text-muted-foreground" />
                </div>
                <div>
                    <p class="font-medium">Natija topilmadi</p>
                    <p class="text-sm text-muted-foreground">Filtrni o'zgartirib ko'ring</p>
                </div>
            </div>

            <!-- Building Accordions -->
            <Accordion v-else type="multiple" class="space-y-3">
                <AccordionItem
                    v-for="building in groupedByBuilding"
                    :key="building.id"
                    :value="building.id"
                    class="rounded-lg border bg-card shadow-sm overflow-hidden"
                >
                    <AccordionTrigger class="px-4 py-3 hover:no-underline hover:bg-muted/50 [&[data-state=open]]:border-b">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                                <Building2 class="h-4 w-4 text-primary" />
                            </div>
                            <div class="flex flex-col items-start gap-0.5">
                                <span class="text-sm font-semibold">{{ building.name }}</span>
                                <div class="flex items-center gap-3 text-xs text-muted-foreground">
                                    <span>{{ building.auditoriums.length }} auditoriya</span>
                                    <span>•</span>
                                    <span>{{ building.activeCount }} faol</span>
                                    <span>•</span>
                                    <span>{{ building.totalCapacity }} o'rinlik</span>
                                </div>
                            </div>
                        </div>
                    </AccordionTrigger>
                    <AccordionContent class="px-4 pb-4 pt-3">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            <Card
                                v-for="item in building.auditoriums"
                                :key="item.code"
                                class="group relative py-0 transition-all hover:shadow-md hover:border-primary/30"
                                :class="{ 'opacity-50': !item.active }"
                            >
                                <CardHeader class="pb-2 pt-4 px-4">
                                    <div class="flex items-start justify-between gap-2">
                                        <CardTitle class="text-sm font-semibold leading-tight line-clamp-2">
                                            {{ item.name }}
                                        </CardTitle>
                                        <span
                                            class="mt-0.5 inline-flex h-2 w-2 shrink-0 rounded-full"
                                            :class="item.active ? 'bg-emerald-500' : 'bg-red-400'"
                                            :title="item.active ? 'Faol' : 'Nofaol'"
                                        />
                                    </div>
                                </CardHeader>
                                <CardContent class="px-4 pb-4 pt-0">
                                    <div class="flex flex-col gap-2">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-muted-foreground">Kod</span>
                                            <span class="font-mono font-medium">{{ item.code }}</span>
                                        </div>
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-muted-foreground">Sig'imi</span>
                                            <div class="flex items-center gap-1">
                                                <Users class="h-3 w-3 text-muted-foreground" />
                                                <span class="font-medium">{{ item.volume }}</span>
                                            </div>
                                        </div>
                                        <div v-if="item.auditoriumType?.name" class="pt-1">
                                            <span
                                                class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-medium"
                                                :class="getTypeColor(item.auditoriumType.code)"
                                            >
                                                {{ item.auditoriumType.name }}
                                            </span>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </AccordionContent>
                </AccordionItem>
            </Accordion>
        </div>
    </AppLayout>
</template>
