<script setup lang="ts">
import { ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Camera, BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Plus, Eye, Edit, Trash2, Search } from 'lucide-vue-next';
import CameraDialog from '@/components/CameraDialog.vue';
import Pagination from '@/components/Pagination.vue';
import { debounce } from 'lodash';

const props = defineProps<{
    cameras: {
        data: Camera[];
        links: any[];
    };
    filters: {
        search?: string;
        active?: string;
        branch_id?: string;
        floor_id?: string;
        faculty_id?: string;
    };
    branches: { id: number; name: string }[];
    floors: { id: number; name: string }[];
    faculties: { id: number; name: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Kameralar', href: '/cameras' },
];

const showDialog = ref(false);
const selectedCamera = ref<Camera | null>(null);

const search = ref(props.filters.search || '');
const activeFilter = ref(props.filters.active ?? 'all');
const branchFilter = ref(props.filters.branch_id ?? 'all');
const floorFilter = ref(props.filters.floor_id ?? 'all');
const facultyFilter = ref(props.filters.faculty_id ?? 'all');

watch([search, activeFilter, branchFilter, floorFilter, facultyFilter], debounce(([newSearch, newActive, newBranch, newFloor, newFaculty]: [string, string, string, string, string]) => {
    router.get('/cameras', {
        search: newSearch,
        active: newActive === 'all' ? null : newActive,
        branch_id: newBranch === 'all' ? null : newBranch,
        floor_id: newFloor === 'all' ? null : newFloor,
        faculty_id: newFaculty === 'all' ? null : newFaculty,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300));

const openCreateModal = () => {
    selectedCamera.value = null;
    showDialog.value = true;
};

const openEditModal = (camera: Camera) => {
    selectedCamera.value = camera;
    showDialog.value = true;
};

const deleteCamera = (camera: Camera) => {
    if (confirm('Ushbu kamerani o‘chirishni xohlaysizmi?')) {
        router.delete(`/cameras/${camera.id}`);
    }
};
</script>

<template>
    <Head title="Kameralar" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Kameralar</h1>
                <Button @click="openCreateModal">
                    <Plus class="mr-2 h-4 w-4" />
                    Kamera Qo'shish
                </Button>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <div class="relative w-full max-w-sm">
                    <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input 
                        v-model="search"
                        placeholder="Nomi yoki IP bo'yicha qidirish..." 
                        class="pl-8" 
                    />
                </div>
                <Select v-model="branchFilter">
                    <SelectTrigger class="w-[140px]">
                        <SelectValue placeholder="Filial" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Barcha filiallar</SelectItem>
                        <SelectItem v-for="branch in branches" :key="branch.id" :value="branch.id.toString()">
                            {{ branch.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <Select v-model="floorFilter">
                    <SelectTrigger class="w-[140px]">
                        <SelectValue placeholder="Qavat" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Barcha qavatlar</SelectItem>
                        <SelectItem v-for="floor in floors" :key="floor.id" :value="floor.id.toString()">
                            {{ floor.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <Select v-model="facultyFilter">
                    <SelectTrigger class="w-[140px]">
                        <SelectValue placeholder="Fakultet" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Barcha fakultetlar</SelectItem>
                        <SelectItem v-for="faculty in faculties" :key="faculty.id" :value="faculty.id.toString()">
                            {{ faculty.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <Select v-model="activeFilter">
                    <SelectTrigger class="w-[140px]">
                        <SelectValue placeholder="Holati" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Barchasi</SelectItem>
                        <SelectItem value="true">Faol</SelectItem>
                        <SelectItem value="false">Faol Emas</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div class="border rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 border-b">
                        <tr>
                            <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Nomi</th>
                            <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Kategoriya</th>
                            <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">IP Manzil</th>
                            <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Holati</th>
                            <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Efir</th>
                            <th class="h-10 px-4 text-right align-middle font-medium text-muted-foreground">Amallar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="cameras.data.length === 0">
                            <td colspan="6" class="p-8 text-center text-muted-foreground">
                                Kameralar topilmadi.
                            </td>
                        </tr>
                        <tr v-for="camera in cameras.data" :key="camera.id" class="border-b transition-colors hover:bg-muted/50">
                            <td class="p-4 align-middle font-medium">{{ camera.name }}</td>
                            <td class="p-4 align-middle">
                                <div class="flex flex-col gap-0.5 text-xs">
                                    <span v-if="camera.branch" class="font-medium">{{ camera.branch.name }}</span>
                                    <span v-if="camera.floor || camera.faculty" class="text-muted-foreground">
                                        {{ [camera.floor?.name, camera.faculty?.name].filter(Boolean).join(' • ') }}
                                    </span>
                                    <span v-if="!camera.branch && !camera.floor && !camera.faculty" class="text-muted-foreground">—</span>
                                </div>
                            </td>
                            <td class="p-4 align-middle">{{ camera.ip_address }}:{{ camera.port }}</td>
                            <td class="p-4 align-middle">
                                <Badge :variant="camera.is_active ? 'default' : 'secondary'">
                                    {{ camera.is_active ? 'Faol' : 'Faol Emas' }}
                                </Badge>
                            </td>
                            <td class="p-4 align-middle">
                                <Badge v-if="camera.is_streaming_to_youtube" variant="destructive" class="text-xs animate-pulse">
                                    jonli efir
                                </Badge>
                                <span v-else class="text-muted-foreground">-</span>
                            </td>
                            <td class="p-4 align-middle text-right">
                                <div class="flex justify-end gap-2">
                                    <Button variant="ghost" size="icon" as-child>
                                        <Link :href="`/cameras/${camera.id}`">
                                            <Eye class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button variant="ghost" size="icon" @click="openEditModal(camera)">
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" @click="deleteCamera(camera)">
                                        <Trash2 class="h-4 w-4 text-destructive" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <Pagination :links="cameras.links" />
            </div>

            <CameraDialog 
                v-model:open="showDialog"
                :camera="selectedCamera"
                :branches="branches"
                :floors="floors"
                :faculties="faculties"
            />
        </div>
    </AppLayout>
</template>
