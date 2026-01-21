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
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Cameras', href: '/cameras' },
];

const showDialog = ref(false);
const selectedCamera = ref<Camera | null>(null);

const search = ref(props.filters.search || '');
const activeFilter = ref(props.filters.active ?? 'all');

watch([search, activeFilter], debounce(([newSearch, newActive]: [string, string]) => {
    router.get('/cameras', {
        search: newSearch,
        active: newActive === 'all' ? null : newActive,
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
    if (confirm('Are you sure you want to delete this camera?')) {
        router.delete(`/cameras/${camera.id}`);
    }
};
</script>

<template>
    <Head title="Cameras" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Cameras</h1>
                <Button @click="openCreateModal">
                    <Plus class="mr-2 h-4 w-4" />
                    Add Camera
                </Button>
            </div>

            <div class="flex items-center gap-4">
                <div class="relative w-full max-w-sm">
                    <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input 
                        v-model="search"
                        placeholder="Search cameras by name or IP..." 
                        class="pl-8" 
                    />
                </div>
                <Select v-model="activeFilter">
                    <SelectTrigger class="w-[180px]">
                        <SelectValue placeholder="Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Status</SelectItem>
                        <SelectItem value="true">Active</SelectItem>
                        <SelectItem value="false">Inactive</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div class="border rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 border-b">
                        <tr>
                            <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Name</th>
                            <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">IP Address</th>
                            <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Status</th>
                            <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Streaming</th>
                            <th class="h-10 px-4 text-right align-middle font-medium text-muted-foreground">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="cameras.data.length === 0">
                            <td colspan="5" class="p-8 text-center text-muted-foreground">
                                No cameras found.
                            </td>
                        </tr>
                        <tr v-for="camera in cameras.data" :key="camera.id" class="border-b transition-colors hover:bg-muted/50">
                            <td class="p-4 align-middle font-medium">{{ camera.name }}</td>
                            <td class="p-4 align-middle">{{ camera.ip_address }}:{{ camera.port }}</td>
                            <td class="p-4 align-middle">
                                <Badge :variant="camera.is_active ? 'default' : 'secondary'">
                                    {{ camera.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                            </td>
                            <td class="p-4 align-middle">
                                <Badge v-if="camera.is_streaming_to_youtube" variant="destructive" class="text-xs animate-pulse">
                                    LIVE
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
            />
        </div>
    </AppLayout>
</template>
