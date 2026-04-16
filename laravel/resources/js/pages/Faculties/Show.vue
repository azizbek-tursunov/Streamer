<script setup lang="ts">
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
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
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { GraduationCap, Building2, Users, DoorOpen, Trash2, CheckCircle, UserCheck } from 'lucide-vue-next';

const props = defineProps<{
    faculty: {
        id: number;
        name: string;
        code?: string | null;
        hemis_id?: number | null;
        active?: boolean;
        auditoriums_count: number;
        auditoriums: Array<{
            id: number;
            code: number;
            name: string;
            volume: number;
            active: boolean;
            building: { id: number; name: string } | null;
            auditoriumType: { code: string; name: string } | null;
        }>;
    };
    dean: { id: number; name: string; email: string } | null;
    deanCandidates: Array<{ id: number; name: string; email: string }>;
}>();

const page = usePage();
const successMessage = computed(() => (page.props.flash as Record<string, string> | undefined)?.success);

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Ma'lumotnomalar", href: '#' },
    { title: 'Fakultetlar', href: '/faculties' },
    { title: props.faculty.name, href: `/faculties/${props.faculty.id}` },
];

// Dean assignment
const selectedDeanId = ref<string>(props.dean?.id?.toString() ?? 'none');
const savingDean = ref(false);

const assignDean = () => {
    savingDean.value = true;
    router.put(`/faculties/${props.faculty.id}/dean`, {
        dean_id: selectedDeanId.value !== 'none' ? parseInt(selectedDeanId.value) : null,
    }, {
        preserveScroll: true,
        onFinish: () => {
            savingDean.value = false;
        },
    });
};

// Remove auditorium confirmation
const showRemoveDialog = ref(false);
const auditoriumToRemove = ref<{ id: number; name: string } | null>(null);
const removing = ref(false);

const confirmRemove = (auditorium: { id: number; name: string }) => {
    auditoriumToRemove.value = auditorium;
    showRemoveDialog.value = true;
};

const removeAuditorium = () => {
    if (!auditoriumToRemove.value) return;
    removing.value = true;
    router.delete(`/faculties/${props.faculty.id}/auditoriums/${auditoriumToRemove.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showRemoveDialog.value = false;
            auditoriumToRemove.value = null;
        },
        onFinish: () => {
            removing.value = false;
        },
    });
};

// Group auditoriums by building
const groupedByBuilding = computed(() => {
    const groups = new Map<string, { name: string; auditoriums: typeof props.faculty.auditoriums }>();
    for (const a of props.faculty.auditoriums) {
        const buildingName = a.building?.name ?? "Bino ko'rsatilmagan";
        if (!groups.has(buildingName)) {
            groups.set(buildingName, { name: buildingName, auditoriums: [] });
        }
        groups.get(buildingName)!.auditoriums.push(a);
    }
    return Array.from(groups.values());
});
</script>

<template>
    <Head :title="faculty.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                        <GraduationCap class="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">{{ faculty.name }}</h1>
                        <div class="flex items-center gap-3 text-sm text-muted-foreground">
                            <span v-if="faculty.code" class="font-mono text-xs">{{ faculty.code }}</span>
                            <span
                                class="inline-flex h-2 w-2 rounded-full"
                                :class="faculty.active !== false ? 'bg-emerald-500' : 'bg-red-400'"
                            />
                            <span>{{ faculty.active !== false ? 'Faol' : 'Nofaol' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Success Toast -->
            <div
                v-if="successMessage"
                class="flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-400"
            >
                <CheckCircle class="h-4 w-4 shrink-0" />
                {{ successMessage }}
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Stats + Dean Assignment -->
                <div class="flex flex-col gap-6">
                    <!-- Stats -->
                    <Card class="py-4">
                        <CardContent class="flex items-center gap-3 pb-0">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                                <DoorOpen class="h-5 w-5 text-primary" />
                            </div>
                            <div>
                                <p class="text-2xl font-bold">{{ faculty.auditoriums_count }}</p>
                                <p class="text-xs text-muted-foreground">Biriktirilgan auditoriyalar</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Dean Assignment -->
                    <Card>
                        <CardHeader class="pb-3">
                            <CardTitle class="flex items-center gap-2 text-base">
                                <UserCheck class="h-4 w-4" />
                                Dekan tayinlash
                            </CardTitle>
                            <CardDescription>
                                Fakultet dekani faqat shu fakultetga biriktirilgan auditoriyalarni ko'radi.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="flex flex-col gap-3">
                                <Select v-model="selectedDeanId">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Dekanni tanlang" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">Tanlanmagan</SelectItem>
                                        <SelectItem v-for="candidate in deanCandidates" :key="candidate.id" :value="candidate.id.toString()">
                                            {{ candidate.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="dean" class="text-xs text-muted-foreground">
                                    Hozirgi dekan: <span class="font-medium text-foreground">{{ dean.name }}</span>
                                    <span class="text-muted-foreground">({{ dean.email }})</span>
                                </div>
                                <Button
                                    @click="assignDean"
                                    :disabled="savingDean"
                                    size="sm"
                                    class="w-full"
                                >
                                    {{ savingDean ? 'Saqlanmoqda...' : 'Saqlash' }}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Auditoriums Table -->
                <div class="lg:col-span-2">
                    <Card>
                        <CardHeader class="pb-3">
                            <CardTitle class="flex items-center gap-2 text-base">
                                <Building2 class="h-4 w-4" />
                                Biriktirilgan auditoriyalar
                            </CardTitle>
                            <CardDescription>
                                Bu fakultetga biriktirilgan auditoriyalar ro'yxati. Auditoriyalarni qo'shish uchun <a href="/auditoriums" class="text-primary hover:underline">Auditoriyalar</a> sahifasidagi "Fakultetga biriktirish" tugmasidan foydalaning.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="faculty.auditoriums.length === 0" class="flex flex-col items-center justify-center gap-3 py-12 text-center text-muted-foreground">
                                <DoorOpen class="h-8 w-8 opacity-30" />
                                <div>
                                    <p class="font-medium">Auditoriya biriktirilmagan</p>
                                    <p class="text-sm">Auditoriyalar sahifasidan biriktiring</p>
                                </div>
                            </div>

                            <div v-else class="space-y-4 max-h-[70vh] overflow-y-auto pr-1">
                                <div v-for="group in groupedByBuilding" :key="group.name">
                                    <h3 class="text-sm font-semibold text-muted-foreground mb-2 flex items-center gap-2">
                                        <Building2 class="h-3.5 w-3.5" />
                                        {{ group.name }}
                                        <span class="text-xs font-normal">({{ group.auditoriums.length }})</span>
                                    </h3>
                                    <div class="border rounded-lg overflow-hidden">
                                        <Table>
                                            <TableHeader>
                                                <TableRow class="bg-muted/50">
                                                    <TableHead class="w-16">Kod</TableHead>
                                                    <TableHead>Nomi</TableHead>
                                                    <TableHead class="w-20 text-center">Sig'im</TableHead>
                                                    <TableHead class="hidden sm:table-cell">Turi</TableHead>
                                                    <TableHead class="w-16 text-center">Holat</TableHead>
                                                    <TableHead class="w-12"></TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                <TableRow v-for="a in group.auditoriums" :key="a.id" class="hover:bg-muted/30">
                                                    <TableCell class="font-mono text-xs text-muted-foreground">{{ a.code }}</TableCell>
                                                    <TableCell class="font-medium">{{ a.name }}</TableCell>
                                                    <TableCell class="text-center">
                                                        <div class="flex items-center justify-center gap-1 text-xs">
                                                            <Users class="h-3 w-3 text-muted-foreground" />
                                                            {{ a.volume }}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell class="hidden sm:table-cell text-xs text-muted-foreground">
                                                        {{ a.auditoriumType?.name || '—' }}
                                                    </TableCell>
                                                    <TableCell class="text-center">
                                                        <span
                                                            class="inline-flex h-2 w-2 rounded-full"
                                                            :class="a.active ? 'bg-emerald-500' : 'bg-red-400'"
                                                        />
                                                    </TableCell>
                                                    <TableCell class="text-center">
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            class="h-7 w-7 text-muted-foreground hover:text-destructive hover:bg-destructive/10"
                                                            @click="confirmRemove(a)"
                                                            title="Fakultetdan olib tashlash"
                                                        >
                                                            <Trash2 class="h-3.5 w-3.5" />
                                                        </Button>
                                                    </TableCell>
                                                </TableRow>
                                            </TableBody>
                                        </Table>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Remove Confirmation Dialog -->
            <Dialog v-model:open="showRemoveDialog">
                <DialogContent class="sm:max-w-[400px]">
                    <DialogHeader>
                        <DialogTitle>Auditoriyani olib tashlash</DialogTitle>
                        <DialogDescription>
                            <b>{{ auditoriumToRemove?.name }}</b> auditoriyasini <b>{{ faculty.name }}</b> fakultetidan olib tashlamoqchimisiz?
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" @click="showRemoveDialog = false" :disabled="removing">
                            Bekor qilish
                        </Button>
                        <Button variant="destructive" @click="removeAuditorium" :disabled="removing">
                            {{ removing ? 'O\'chirilmoqda...' : 'Olib tashlash' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
