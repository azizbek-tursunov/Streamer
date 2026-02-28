<script setup lang="ts">
import { ref, computed, watch, reactive, onMounted, onUnmounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage, useForm } from '@inertiajs/vue3';
import { BreadcrumbItem, Auditorium, Camera, Faculty } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
    Video,
    Link as LinkIcon,
    GraduationCap,
    X,
    ThumbsUp,
    ThumbsDown,
    MessageSquareText,
} from 'lucide-vue-next';
import { debounce } from 'lodash';
import { useSortable } from '@vueuse/integrations/useSortable';
import { toast } from 'vue-sonner';

const props = defineProps<{
    auditoriums: Auditorium[];
    filters: {
        search?: string;
        faculty_id?: number;
    };
    lastSyncedAt: string | null;
    cameras: Camera[];
    faculties: Faculty[];
}>();

const page = usePage();

const breadcrumbs: BreadcrumbItem[] = [
    { title: "O'quv jarayoni", href: '#' },
    { title: 'Auditoriyalar', href: '/auditoriums' },
];

const search = ref(props.filters.search || '');
const selectedFaculty = ref<string>(props.filters.faculty_id?.toString() || 'all');
const selectedType = ref<string | null>(null);
const showInactive = ref(false);
const syncing = ref(false);

// Reordering State
const isReordering = ref(false);
const savingOrder = ref(false);
// Store ref to the root Accordion container for sortablejs
const accordionRef = ref<any>(null);

// Multi-select State
const isAssigning = ref(false);
const selectedAuditoriums = ref<number[]>([]);
const showFacultyDialog = ref(false);
const selectedBulkFacultyId = ref<string>('');
const assigningFaculty = ref(false);

// Camera Assignment State
const showCameraDialog = ref(false);
const selectedAuditorium = ref<Auditorium | null>(null);
const selectedCameraId = ref<string>('');
const assigningCamera = ref(false);
const cameraSearch = ref('');

// Lesson Feedback State
const showFeedbackDialog = ref(false);
const feedbackForm = useForm({
    auditorium_id: null as number | null,
    lesson_name: '',
    employee_name: '',
    group_name: '',
    start_time: '',
    end_time: '',
    type: 'good',
    message: '',
});

const openFeedbackDialog = (auditoriumId: number, lesson: any) => {
    feedbackForm.reset();
    feedbackForm.auditorium_id = auditoriumId;
    feedbackForm.lesson_name = lesson.subject_name;
    feedbackForm.employee_name = lesson.employee_name;
    feedbackForm.group_name = lesson.group_name;
    feedbackForm.start_time = lesson.start_time;
    feedbackForm.end_time = lesson.end_time;
    feedbackForm.type = 'good';
    feedbackForm.message = '';
    showFeedbackDialog.value = true;
};

const submitFeedback = () => {
    feedbackForm.post('/feedbacks', {
        preserveScroll: true,
        onSuccess: () => {
            showFeedbackDialog.value = false;
        },
    });
};

watch([search, selectedFaculty], debounce(([searchVal, facultyVal]) => {
    router.get('/auditoriums', { 
        search: searchVal as string, 
        faculty_id: facultyVal === 'all' ? undefined : parseInt(facultyVal as string) 
    }, { preserveState: true, replace: true });
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
// Using reactive to allow in-place sorting and updates
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
        auditoriums: group.auditoriums, // Note: ordered natively from controller
        activeCount: group.auditoriums.filter(a => a.active).length,
        totalCapacity: group.auditoriums.reduce((sum, a) => sum + (a.volume || 0), 0),
    }));
});

const mutableGroups = ref<any[]>([]);

watch(groupedByBuilding, (newVal) => {
    if (!isReordering.value) {
        mutableGroups.value = [...newVal];
    }
}, { immediate: true });

// Watch for reorder mode to initialize Sortable instances
watch(isReordering, (val) => {
    if (val && accordionRef.value) {
        // give DOM a tick
        setTimeout(() => {
            if (accordionRef.value) {
                // To attach to the actual accordion wrapper (typically Radix adds divs)
                // We target the immediate child elements with `.accordion-item-handle`
                useSortable(accordionRef.value.$el || accordionRef.value, mutableGroups, {
                    animation: 150,
                    handle: '.accordion-drag-handle',
                    ghostClass: 'opacity-50',
                });
            }
        }, 100);
    }
});

const toggleReorder = () => {
    isReordering.value = !isReordering.value;
    // reset search to see all when reordering
    if (isReordering.value) {
        search.value = '';
        selectedFaculty.value = 'all';
    }
};

const toggleAssigning = () => {
    isAssigning.value = !isAssigning.value;
    if (!isAssigning.value) {
        selectedAuditoriums.value = []; // clear selection on exit
    }
};

const saveOrder = () => {
    savingOrder.value = true;
    
    // Sort logic requires integer building ID
    const orderedItems = mutableGroups.value.map((group, index) => ({
        building_id: parseInt(group.id) || 0,
        sort_order: index + 1
    })).filter(g => g.building_id !== 0); // Ignore 'unknown'

    router.put('/auditoriums/reorder-buildings', { buildings: orderedItems }, {
        preserveScroll: true,
        onSuccess: () => {
            isReordering.value = false;
        },
        onFinish: () => {
            savingOrder.value = false;
        }
    });
};

// Bulk Actions
const toggleSelection = (id: number) => {
    const index = selectedAuditoriums.value.indexOf(id);
    if (index === -1) {
        selectedAuditoriums.value.push(id);
    } else {
        selectedAuditoriums.value.splice(index, 1);
    }
};

const selectAll = () => {
    if (selectedAuditoriums.value.length === filteredAuditoriums.value.length) {
        selectedAuditoriums.value = [];
    } else {
        selectedAuditoriums.value = filteredAuditoriums.value.map(a => a.id);
    }
};

const clearBulkSelection = () => {
    selectedAuditoriums.value = [];
};

const assignBulkFaculty = () => {
    if (!selectedAuditoriums.value.length) return;
    
    assigningFaculty.value = true;
    router.put('/auditoriums/bulk-assign-faculty', {
        auditorium_ids: selectedAuditoriums.value,
        faculty_id: selectedBulkFacultyId.value || null,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showFacultyDialog.value = false;
            selectedAuditoriums.value = [];
            selectedBulkFacultyId.value = '';
        },
        onFinish: () => {
            assigningFaculty.value = false;
        }
    });
};

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

// --- Smart Polling for Snapshots, Lessons & People Counts ---
const snapshotUrls = reactive<Record<number, string>>({});
const cachedTimestamps = reactive<Record<number, number>>({});
const activeLessons = reactive<Record<number, any>>({});
const peopleCounts = reactive<Record<number, number>>({});
let pollInterval: ReturnType<typeof setInterval> | null = null;
let lessonsPollInterval: ReturnType<typeof setInterval> | null = null;
let peopleCountsPollInterval: ReturnType<typeof setInterval> | null = null;

const pollSnapshots = async () => {
    try {
        const response = await fetch('/cameras/snapshots');
        const data: Record<number, { url: string; timestamp: number } | null> = await response.json();
        
        Object.entries(data).forEach(([cameraIdStr, info]) => {
            const id = parseInt(cameraIdStr);
            if (info && info.timestamp !== cachedTimestamps[id]) {
                snapshotUrls[id] = `${info.url}?t=${info.timestamp}`;
                cachedTimestamps[id] = info.timestamp;
            }
        });
    } catch (error) {
        console.error('Failed to poll snapshots:', error);
    }
};

const pollActiveLessons = async () => {
    try {
        const response = await fetch('/auditoriums/active-lessons');
        const data = await response.json();
        for (const key in activeLessons) delete activeLessons[key];
        Object.assign(activeLessons, data);
    } catch (error) {
        console.error('Failed to poll active lessons:', error);
    }
};

const pollPeopleCounts = async () => {
    try {
        const response = await fetch('/auditoriums/people-counts');
        const data: Record<number, number> = await response.json();
        Object.assign(peopleCounts, data);
    } catch (error) {
        console.error('Failed to poll people counts:', error);
    }
};

onMounted(() => {
    // Initial fetch shortly after loading
    setTimeout(pollSnapshots, 2000);
    setTimeout(pollActiveLessons, 3000);
    setTimeout(pollPeopleCounts, 4000);
    
    // Poll snapshots every 30 seconds
    pollInterval = setInterval(pollSnapshots, 30000);
    
    // Poll lessons every 60 seconds
    lessonsPollInterval = setInterval(pollActiveLessons, 60000);
    
    // Poll people counts every 30 seconds
    peopleCountsPollInterval = setInterval(pollPeopleCounts, 30000);
    
    // Initialize activeLessons from props
    props.auditoriums.forEach(a => {
        if (a.current_lesson) {
            activeLessons[a.code] = a.current_lesson;
        }
    });
    
    // Initialize peopleCounts from props
    props.auditoriums.forEach(a => {
        if (a.camera_id && a.people_count !== null && a.people_count !== undefined) {
            peopleCounts[a.camera_id] = a.people_count;
        }
    });
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
    if (lessonsPollInterval) clearInterval(lessonsPollInterval);
    if (peopleCountsPollInterval) clearInterval(peopleCountsPollInterval);
});
// ---------------------------------------------

const openCameraDialog = (auditorium: Auditorium) => {
    selectedAuditorium.value = auditorium;
    selectedCameraId.value = auditorium.camera_id?.toString() || '';
    cameraSearch.value = '';
    showCameraDialog.value = true;
};

const filteredCameras = computed(() => {
    if (!cameraSearch.value) return props.cameras;
    const q = cameraSearch.value.toLowerCase();
    return props.cameras.filter(c =>
        c.name.toLowerCase().includes(q) || c.ip_address.includes(q)
    );
});

const assignCamera = () => {
    if (!selectedAuditorium.value) return;

    assigningCamera.value = true;
    router.put(`/auditoriums/${selectedAuditorium.value.id}`, {
        camera_id: selectedCameraId.value || null,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showCameraDialog.value = false;
        },
        onFinish: () => {
            assigningCamera.value = false;
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
                        <div v-if="isReordering" class="flex gap-2">
                            <Button @click="toggleReorder" variant="secondary" size="sm" :disabled="savingOrder">
                                Bekor qilish
                            </Button>
                            <Button @click="saveOrder" :disabled="savingOrder" size="sm">
                                <CheckCircle class="mr-2 h-4 w-4" />
                                {{ savingOrder ? 'Saqlanmoqda...' : 'Tartibni saqlash' }}
                            </Button>
                        </div>
                        <div v-else-if="isAssigning" class="flex gap-2">
                            <Button @click="toggleAssigning" variant="secondary" size="sm">
                                <X class="mr-2 h-4 w-4" />
                                Bekor qilish
                            </Button>
                        </div>
                        <div v-else class="flex gap-2">
                            <Button @click="toggleAssigning" variant="outline" size="sm">
                                <GraduationCap class="mr-2 h-4 w-4" />
                                Fakultetga biriktirish
                            </Button>
                            <Button @click="toggleReorder" variant="outline" size="sm">
                                <Layers class="mr-2 h-4 w-4" />
                                Tartiblash
                            </Button>
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
                <div class="flex items-center gap-3 w-full max-w-2xl">
                    <div class="relative w-full md:w-[350px]">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            v-model="search"
                            placeholder="Auditoriya nomi bo'yicha qidirish..."
                            class="pl-9"
                        />
                    </div>
                    <Select v-model="selectedFaculty" class="w-full md:w-[250px]">
                        <SelectTrigger class="w-full md:w-[250px]">
                            <SelectValue placeholder="Barcha fakultetlar" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Barcha fakultetlar</SelectItem>
                            <SelectItem v-for="faculty in faculties" :key="faculty.id" :value="faculty.id.toString()">
                                {{ faculty.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
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
            <Accordion v-else type="multiple" class="space-y-3" ref="accordionRef">
                <AccordionItem
                    v-for="(building, index) in mutableGroups"
                    :key="building.id"
                    :value="building.id.toString()"
                    class="rounded-lg border bg-card shadow-sm overflow-hidden"
                >
                    <AccordionTrigger 
                        class="px-4 py-3 hover:no-underline hover:bg-muted/50 [&[data-state=open]]:border-b transition-all"
                        :class="{'cursor-default': isReordering}"
                    >
                        <div class="flex items-center gap-3 w-full">
                            <div 
                                v-if="isReordering" 
                                class="accordion-drag-handle cursor-grab active:cursor-grabbing p-1.5 -ml-1 text-muted-foreground hover:text-foreground hover:bg-muted rounded"
                                @click.stop
                            >
                                <Layers class="h-5 w-5" />
                            </div>
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                                <Building2 class="h-4 w-4 text-primary" />
                            </div>
                            <div class="flex flex-col items-start gap-0.5 flex-1 text-left">
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
                    <AccordionContent class="px-3 pb-3 pt-2">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            <Card
                                v-for="item in building.auditoriums"
                                :key="item.id"
                                class="group relative flex flex-col justify-between py-0 transition-all hover:shadow-md hover:border-primary/30 overflow-hidden"
                                :class="{ 'opacity-50': !item.active, 'ring-2 ring-primary': selectedAuditoriums.includes(item.id) }"
                            >
                                <div 
                                    v-if="item.camera_snapshot" 
                                    class="w-full aspect-video bg-muted border-b relative group/image"
                                    :class="{'cursor-pointer': !isReordering}"
                                    @click.stop="isAssigning ? toggleSelection(item.id) : (!isReordering ? router.visit(`/auditoriums/${item.id}`) : null)"
                                >
                                    <img :src="item.camera_id && snapshotUrls[item.camera_id] ? snapshotUrls[item.camera_id] : item.camera_snapshot" class="object-cover w-full h-full" alt="Camera Snapshot" />
                                    <div class="absolute inset-0 bg-black/10 transition-colors" :class="!isAssigning && !isReordering ? 'group-hover/image:bg-transparent' : ''"></div>
                                    <div v-if="!isAssigning && !isReordering" class="absolute inset-0 flex items-center justify-center opacity-0 group-hover/image:opacity-100 transition-opacity">
                                        <div class="bg-black/50 text-white rounded-full p-3 backdrop-blur-sm">
                                            <Video class="h-6 w-6" />
                                        </div>
                                    </div>
                                </div>
                                <div 
                                    v-else-if="item.camera_id" 
                                    class="w-full aspect-video bg-muted border-b flex items-center justify-center text-muted-foreground relative"
                                    :class="{'cursor-pointer': !isReordering}"
                                    @click.stop="isAssigning ? toggleSelection(item.id) : (!isReordering ? router.visit(`/auditoriums/${item.id}`) : null)"
                                >
                                    <div class="flex flex-col items-center gap-2 opacity-50 transition-opacity hover:opacity-100">
                                        <Video class="h-8 w-8" />
                                        <span class="text-xs">Ulanmoqda... / Ko'rish</span>
                                    </div>
                                </div>
                                
                                <CardHeader class="pb-1 pt-3 px-3">
                                    <div class="flex items-start justify-between gap-1">
                                        <div class="flex items-center gap-1.5" :class="{'cursor-pointer': isAssigning}" @click.stop="isAssigning && toggleSelection(item.id)">
                                            <CardTitle class="text-[13px] font-semibold leading-tight line-clamp-2 select-none" :title="item.name">
                                                {{ item.name }}
                                            </CardTitle>
                                        </div>
                                        <span
                                            class="mt-0.5 inline-flex h-1.5 w-1.5 shrink-0 rounded-full"
                                            :class="item.active ? 'bg-emerald-500' : 'bg-red-400'"
                                            :title="item.active ? 'Faol' : 'Nofaol'"
                                        />
                                    </div>
                                </CardHeader>
                                <CardContent class="px-3 pb-1.5 pt-0 flex-grow">
                                    <div class="flex flex-col gap-1.5">
                                        <div class="flex items-center gap-2 text-[11px] text-muted-foreground">
                                            <span class="font-mono">{{ item.code }}</span>
                                            <span>•</span>
                                            <div class="flex items-center gap-1">
                                                <Users class="h-3 w-3 opacity-70" />
                                                <span>{{ item.volume }}</span>
                                            </div>
                                            <template v-if="item.camera_id && peopleCounts[item.camera_id] !== undefined">
                                                <span>•</span>
                                                <div class="flex items-center gap-1 text-primary font-medium">
                                                    <Users class="h-3 w-3" />
                                                    <span>{{ peopleCounts[item.camera_id] }} kishi</span>
                                                </div>
                                            </template>
                                        </div>
                                        <div v-if="item.camera" class="flex items-center gap-1 text-[11px] text-emerald-600 font-medium">
                                            <Video class="h-3 w-3" />
                                            <span class="truncate">{{ item.camera.name }}</span>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-1.5 pt-0.5">
                                            <span v-if="item.auditoriumType?.name" class="inline-flex items-center rounded bg-opacity-10 px-1.5 py-0.5 text-[9px] font-medium" :class="getTypeColor(item.auditoriumType.code)">
                                                {{ item.auditoriumType.name }}
                                            </span>
                                            <div v-if="item.faculty" class="flex items-center gap-1 text-[10px] text-muted-foreground bg-muted/50 px-1.5 py-0.5 rounded border border-border/50 truncate max-w-full" :title="item.faculty.name">
                                                <GraduationCap class="h-2.5 w-2.5 text-primary/70 shrink-0" />
                                                <span class="truncate">{{ item.faculty.name }}</span>
                                            </div>
                                        </div>

                                        <div v-if="activeLessons[item.code]" class="mt-2 p-2.5 bg-primary/5 rounded-md border border-primary/20">
                                            <div class="text-[10px] font-bold text-primary mb-1.5 uppercase tracking-wider flex justify-between items-center">
                                                <span>Hozirgi Dars</span>
                                                <div class="flex items-center gap-2">
                                                    <button 
                                                        @click.stop="openFeedbackDialog(item.id, activeLessons[item.code])"
                                                        class="opacity-70 hover:opacity-100 hover:text-emerald-600 hover:border-emerald-500/30 hover:bg-emerald-50/50 dark:hover:bg-emerald-900/20 transition-all bg-background border px-2 py-1 rounded-md flex items-center gap-1.5 cursor-pointer shadow-sm"
                                                        title="Darsni baholash"
                                                    >
                                                        <MessageSquareText class="h-3.5 w-3.5" />
                                                        <span class="text-[10px] font-medium uppercase tracking-wider">Baholash</span>
                                                    </button>
                                                    <span class="relative flex h-2 w-2">
                                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary opacity-75"></span>
                                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-primary"></span>
                                                    </span>
                                                </div>
                                            </div>
                                            <p class="text-xs font-semibold leading-tight line-clamp-2" :title="activeLessons[item.code].subject_name">
                                                {{ activeLessons[item.code].subject_name }}
                                            </p>
                                            <div class="flex items-center gap-1.5 mt-1.5 text-[10px] text-muted-foreground truncate" :title="activeLessons[item.code].employee_name">
                                                <Users class="h-3 w-3 shrink-0" />
                                                <span class="truncate">{{ activeLessons[item.code].employee_name }}</span>
                                            </div>
                                            <div class="flex items-center justify-between mt-1 text-[10px] text-muted-foreground">
                                                <span class="truncate font-medium text-foreground py-0.5 px-1.5 bg-background rounded border" :title="activeLessons[item.code].group_name">
                                                    Guruh: {{ activeLessons[item.code].group_name }}
                                                </span>
                                                <span class="opacity-80">{{ activeLessons[item.code].start_time.substring(0, 5) }} - {{ activeLessons[item.code].end_time.substring(0, 5) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                                <CardFooter class="px-3 pb-3 pt-1.5 flex gap-1.5">
                                    <Button 
                                        v-if="item.camera_id" 
                                        size="sm" 
                                        variant="default" 
                                        class="flex-1 text-[11px] h-7 px-2 overflow-hidden" 
                                        as-child
                                    >
                                        <a :href="`/auditoriums/${item.id}`" class="flex items-center justify-center gap-1">
                                            <Video class="h-3 w-3 shrink-0" />
                                            <span class="truncate">Ko'rish</span>
                                        </a>
                                    </Button>
                                    <Button 
                                        size="sm" 
                                        variant="outline" 
                                        class="flex-1 text-[11px] h-7 px-2 overflow-hidden"
                                        @click="openCameraDialog(item)"
                                    >
                                        <LinkIcon class="h-3 w-3 shrink-0" />
                                        <span class="truncate">{{ item.camera_id ? 'Tahrirlash' : 'Ulash' }}</span>
                                    </Button>
                                </CardFooter>
                            </Card>
                        </div>
                    </AccordionContent>
                </AccordionItem>
            </Accordion>
            
            <!-- Bulk Actions Floating Bar -->
            <div 
                v-if="isAssigning"
                class="fixed bottom-6 w-[calc(100%-2rem)] md:w-auto left-4 md:left-1/2 md:-translate-x-1/2 z-50 flex items-center justify-between md:justify-start gap-4 rounded-full border border-primary/20 bg-background/95 shadow-lg backdrop-blur supports-[backdrop-filter]:bg-background/80 px-4 py-3"
            >
                <div class="flex items-center gap-2 border-r pr-4">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-primary text-xs font-medium text-primary-foreground">
                        {{ selectedAuditoriums.length }}
                    </span>
                    <span class="text-sm font-medium">ta tanlandi</span>
                </div>
                
                <div class="flex items-center gap-2">
                    <Button 
                        variant="default" 
                        size="sm" 
                        class="rounded-full shadow-sm"
                        @click="showFacultyDialog = true"
                        :disabled="selectedAuditoriums.length === 0"
                    >
                        <GraduationCap class="mr-2 h-4 w-4" />
                        Fakultetga biriktirish
                    </Button>
                    
                    <Button 
                        variant="ghost" 
                        size="icon"
                        class="h-8 w-8 rounded-full hover:bg-destructive/10 hover:text-destructive text-muted-foreground transition-colors"
                        @click="clearBulkSelection"
                        title="Tanlovni bekor qilish"
                    >
                        <X class="h-4 w-4" />
                        <span class="sr-only">Bekor qilish</span>
                    </Button>
                </div>
            </div>

            <Dialog v-model:open="showCameraDialog">
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>Kamera biriktirish</DialogTitle>
                        <DialogDescription>
                            {{ selectedAuditorium?.name }} uchun kamera tanlang.
                        </DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-4 py-4">
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-medium">Kamera qidirish</label>
                            <Input
                                v-model="cameraSearch"
                                placeholder="Kamera nomi yoki IP manzilini kiriting..."
                                class="mb-2"
                            />
                            
                            <label class="text-sm font-medium">Natija ({{ filteredCameras.length }} ta)</label>
                            <Select v-model="selectedCameraId">
                                <SelectTrigger>
                                    <SelectValue placeholder="Kamerani tanlang" />
                                </SelectTrigger>
                                <SelectContent class="max-h-[300px]">
                                    <SelectItem value="">
                                        <span class="text-destructive font-medium">Kamerani o'chirish</span>
                                    </SelectItem>
                                    <SelectItem v-for="camera in filteredCameras" :key="camera.id" :value="camera.id.toString()">
                                        {{ camera.name }} ({{ camera.ip_address }})
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" @click="showCameraDialog = false" :disabled="assigningCamera">Bekor qilish</Button>
                        <Button @click="assignCamera" :disabled="assigningCamera">
                            {{ assigningCamera ? 'Saqlanmoqda...' : 'Saqlash' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog v-model:open="showFacultyDialog">
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>Fakultetga biriktirish</DialogTitle>
                        <DialogDescription>
                            Tanlangan <b>{{ selectedAuditoriums.length }}</b> ta auditoriyani fakultetga biriktiring yoki joriy fakultetdan o'chiring.
                        </DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-4 py-4">
                        <div class="grid gap-2">
                            <Select v-model="selectedBulkFacultyId" :disabled="assigningFaculty">
                                <SelectTrigger>
                                    <SelectValue placeholder="Fakultetni tanlang" />
                                </SelectTrigger>
                                <SelectContent class="max-h-[300px]">
                                    <SelectItem value="">
                                        <span class="text-destructive font-medium">Fakultetdan o'chirish (Hech qaysi)</span>
                                    </SelectItem>
                                    <SelectItem v-for="faculty in faculties" :key="faculty.id" :value="faculty.id.toString()">
                                        {{ faculty.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="showFacultyDialog = false" :disabled="assigningFaculty">
                            Bekor qilish
                        </Button>
                        <Button type="submit" @click="assignBulkFaculty" :disabled="assigningFaculty">
                            <RefreshCw v-if="assigningFaculty" class="mr-2 h-4 w-4 animate-spin" />
                            Saqlash
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog v-model:open="showFeedbackDialog">
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>Dars tahlili va mulohaza</DialogTitle>
                        <DialogDescription>
                            {{ feedbackForm.employee_name }} domlaning {{ feedbackForm.lesson_name }} darsi haqida fikringizni yozib qoldiring.
                        </DialogDescription>
                    </DialogHeader>
                    
                    <form @submit.prevent="submitFeedback" class="grid gap-4 py-4">
                        <div class="flex justify-center gap-4 py-2">
                            <button 
                                type="button" 
                                @click="feedbackForm.type = 'good'" 
                                class="flex w-24 flex-col items-center gap-2 rounded-xl border-2 p-3 transition-all cursor-pointer" 
                                :class="feedbackForm.type === 'good' ? 'border-emerald-500 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400' : 'border-border text-muted-foreground hover:bg-muted'"
                            >
                                <ThumbsUp class="h-6 w-6" />
                                <span class="text-xs font-medium">Ijobiy</span>
                            </button>
                            <button 
                                type="button" 
                                @click="feedbackForm.type = 'bad'" 
                                class="flex w-24 flex-col items-center gap-2 rounded-xl border-2 p-3 transition-all cursor-pointer" 
                                :class="feedbackForm.type === 'bad' ? 'border-red-500 bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-400' : 'border-border text-muted-foreground hover:bg-muted'"
                            >
                                <ThumbsDown class="h-6 w-6" />
                                <span class="text-xs font-medium">Salbiy</span>
                            </button>
                        </div>

                        <div class="grid gap-2">
                            <label class="text-sm font-medium">Xabar</label>
                            <textarea
                                v-model="feedbackForm.message"
                                placeholder="(Ixtiyoriy) Dars holati haqida qisqacha yozing..."
                                class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="feedbackForm.processing"
                            ></textarea>
                        </div>
                    </form>
                    
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="showFeedbackDialog = false" :disabled="feedbackForm.processing">
                            Bekor qilish
                        </Button>
                        <Button type="button" @click="submitFeedback" :disabled="feedbackForm.processing">
                            <RefreshCw v-if="feedbackForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                            Jo'natish
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
