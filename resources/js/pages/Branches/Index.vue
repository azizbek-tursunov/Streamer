<script setup lang="ts">
import { ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { BreadcrumbItem, Branch } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Search, Plus, Edit, Trash2 } from 'lucide-vue-next';
import Pagination from '@/components/Pagination.vue';
import ResourceDialog from '@/components/ResourceDialog.vue';
import { debounce } from 'lodash';

const props = defineProps<{
    branches: {
        data: Branch[];
        links: any[];
    };
    filters: {
        search?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Ma'lumotnomalar", href: '#' },
    { title: 'Filiallar', href: '/branches' },
];

const showDialog = ref(false);
const selectedItem = ref<Branch | null>(null);
const search = ref(props.filters.search || '');

watch(search, debounce((value: string) => {
    router.get('/branches', { search: value }, { preserveState: true, replace: true });
}, 300));

const openCreateModal = () => {
    selectedItem.value = null;
    showDialog.value = true;
};

const openEditModal = (item: Branch) => {
    selectedItem.value = item;
    showDialog.value = true;
};

const deleteItem = (item: Branch) => {
    if (confirm(`"${item.name}" filialini o'chirishni xohlaysizmi?`)) {
        router.delete(`/branches/${item.id}`);
    }
};
</script>

<template>
    <Head title="Filiallar" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Filiallar</h1>
                <Button @click="openCreateModal">
                    <Plus class="mr-2 h-4 w-4" />
                    Filial Qo'shish
                </Button>
            </div>

            <div class="flex items-center gap-4">
                <div class="relative w-full max-w-sm">
                    <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input 
                        v-model="search"
                        placeholder="Nomi bo'yicha qidirish..." 
                        class="pl-8" 
                    />
                </div>
            </div>

            <div class="border rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 border-b">
                        <tr>
                            <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">ID</th>
                            <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Nomi</th>
                            <th class="h-10 px-4 text-right align-middle font-medium text-muted-foreground">Amallar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="branches.data.length === 0">
                            <td colspan="3" class="p-8 text-center text-muted-foreground">
                                Filiallar topilmadi.
                            </td>
                        </tr>
                        <tr v-for="branch in branches.data" :key="branch.id" class="border-b transition-colors hover:bg-muted/50">
                            <td class="p-4 align-middle font-medium w-16">#{{ branch.id }}</td>
                            <td class="p-4 align-middle font-medium">{{ branch.name }}</td>
                            <td class="p-4 align-middle text-right">
                                <div class="flex justify-end gap-2">
                                    <Button variant="ghost" size="icon" @click="openEditModal(branch)">
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" @click="deleteItem(branch)">
                                        <Trash2 class="h-4 w-4 text-destructive" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <Pagination :links="branches.links" />
            </div>

            <ResourceDialog 
                v-model:open="showDialog"
                :item="selectedItem"
                title="Filial"
                url="/branches"
                label="Filial Nomi"
            />
        </div>
    </AppLayout>
</template>
