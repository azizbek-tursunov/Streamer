<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage, Link } from '@inertiajs/vue3';
import { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Search, Building2, RefreshCw, CheckCircle, UserCheck } from 'lucide-vue-next';
import { debounce } from 'lodash';

interface FacultyWithDean {
    id: number;
    name: string;
    code?: string | null;
    hemis_id?: number | null;
    active?: boolean;
    auditoriums_count?: number;
    dean?: { id: number; name: string; email: string } | null;
}

const props = defineProps<{
    faculties: FacultyWithDean[];
    filters: {
        search?: string;
    };
    lastSyncedAt: string | null;
}>();

const page = usePage();

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Ma'lumotnomalar", href: '#' },
    { title: 'Fakultetlar', href: '/faculties' },
];

const search = ref(props.filters.search || '');
const syncing = ref(false);

watch(search, debounce((value: string) => {
    router.get('/faculties', { search: value }, { preserveState: true, replace: true });
}, 300));

const syncFromApi = () => {
    syncing.value = true;
    router.post('/faculties/sync', {}, {
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

const successMessage = computed(() => (page.props.flash as Record<string, string> | undefined)?.success);
</script>

<template>
    <Head title="Fakultetlar" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
            <div class="flex flex-col gap-1">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-bold tracking-tight">Fakultetlar</h1>
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
                <p class="text-sm text-muted-foreground">HEMIS tizimidan sinxronlangan fakultetlar</p>
            </div>

            <!-- Success Toast -->
            <div
                v-if="successMessage"
                class="flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-400"
            >
                <CheckCircle class="h-4 w-4 shrink-0" />
                {{ successMessage }}
            </div>

            <div class="flex items-center gap-4">
                <div class="relative w-full max-w-sm">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        v-model="search"
                        placeholder="Nomi bo'yicha qidirish..."
                        class="pl-9"
                    />
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="faculties.length === 0" class="flex flex-col items-center justify-center gap-4 py-16 text-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-muted">
                    <Building2 class="h-8 w-8 text-muted-foreground" />
                </div>
                <div>
                    <p class="font-medium">Fakultetlar topilmadi</p>
                    <p class="text-sm text-muted-foreground mb-4">
                        HEMIS tizimidan ma'lumotlarni sinxronlang
                    </p>
                    <Button @click="syncFromApi" :disabled="syncing">
                        <RefreshCw class="mr-2 h-4 w-4" :class="{ 'animate-spin': syncing }" />
                        {{ syncing ? 'Sinxronlanmoqda...' : 'Sinxronlash' }}
                    </Button>
                </div>
            </div>

            <!-- Table -->
            <div v-else class="border rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 border-b">
                        <tr>
                            <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground w-16">#</th>
                            <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Nomi</th>
                            <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground hidden md:table-cell">Dekan</th>
                            <th class="h-10 px-4 text-center align-middle font-medium text-muted-foreground w-32">Auditoriyalar</th>
                            <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground hidden sm:table-cell">Kod</th>
                            <th class="h-10 px-4 text-center align-middle font-medium text-muted-foreground w-20">Holati</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(faculty, index) in faculties"
                            :key="faculty.id"
                            class="border-b transition-colors hover:bg-muted/50 cursor-pointer"
                            @click="router.visit(`/faculties/${faculty.id}`)"
                        >
                            <td class="p-4 align-middle text-muted-foreground w-16">{{ index + 1 }}</td>
                            <td class="p-4 align-middle font-medium text-primary hover:underline">{{ faculty.name }}</td>
                            <td class="p-4 align-middle hidden md:table-cell">
                                <div v-if="faculty.dean" class="flex items-center gap-1.5 text-sm">
                                    <UserCheck class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                    <span>{{ faculty.dean.name }}</span>
                                </div>
                                <span v-else class="text-xs text-muted-foreground">—</span>
                            </td>
                            <td class="p-4 align-middle text-center">
                                <span class="inline-flex items-center justify-center rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-semibold text-primary">
                                    {{ faculty.auditoriums_count || 0 }} ta
                                </span>
                            </td>
                            <td class="p-4 align-middle text-muted-foreground hidden sm:table-cell">
                                <span v-if="faculty.code" class="font-mono text-xs">{{ faculty.code }}</span>
                                <span v-else class="text-xs">—</span>
                            </td>
                            <td class="p-4 align-middle text-center">
                                <span
                                    class="inline-flex h-2 w-2 rounded-full"
                                    :class="faculty.active !== false ? 'bg-emerald-500' : 'bg-red-400'"
                                    :title="faculty.active !== false ? 'Faol' : 'Nofaol'"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
