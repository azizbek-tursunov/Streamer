<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Pencil, Trash2, Plus, Search, Shield } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import Pagination from '@/components/Pagination.vue';
import { ref, watch } from 'vue';
import { debounce } from 'lodash';
import { index, create, edit, destroy } from '@/routes/permissions';

const props = defineProps<{
    permissions: {
        data: Array<{
            id: number;
            name: string;
        }>;
        links: any[]; 
    };
    filters: {
        search?: string;
    };
}>();

const search = ref(props.filters.search || '');

watch(search, debounce((value: string) => {
    router.get(index().url, { search: value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300));

const deletePermission = (id: number) => {
    if (confirm('Ruxsatnomani o\'chirishni tasdiqlaysizmi?')) {
        router.delete(destroy(id).url);
    }
};
</script>

<template>
    <Head title="Ruxsatnomalar" />

    <AppLayout :breadcrumbs="[
        { title: 'Xavfsizlik', href: '#' },
        { title: 'Ruxsatnomalar', href: index().url },
    ]">
        <div class="px-4 py-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        Ruxsatnomalar
                    </h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        Tizim ruxsatnomalarini boshqarish.
                    </p>
                </div>
                <Button as-child>
                    <Link :href="create().url">
                        <Plus class="w-4 h-4 mr-2" />
                        Yangi qo'shish
                    </Link>
                </Button>
            </div>

            <div class="flex items-center gap-4 mb-6">
                <div class="relative w-full max-w-sm">
                    <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input 
                        v-model="search"
                        placeholder="Ruxsatnoma nomi bo'yicha qidirish..." 
                        class="pl-8" 
                    />
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Ruxsatnoma Nomi</TableHead>
                            <TableHead class="text-right">Amallar</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                         <TableRow v-if="permissions.data.length === 0">
                            <TableCell colspan="2" class="h-24 text-center">
                                Ruxsatnomalar topilmadi.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="permission in permissions.data" :key="permission.id">
                            <TableCell class="font-medium">
                                <div class="flex items-center gap-2">
                                    <Shield class="w-4 h-4 text-muted-foreground" />
                                    <span>{{ permission.name }}</span>
                                </div>
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Button variant="ghost" size="icon" as-child>
                                        <Link :href="edit(permission.id).url">
                                            <Pencil class="w-4 h-4" />
                                        </Link>
                                    </Button>
                                    <Button 
                                        variant="ghost" 
                                        size="icon" 
                                        @click="deletePermission(permission.id)"
                                        class="text-destructive hover:text-destructive"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <div class="mt-4">
                <Pagination :links="permissions.links" />
            </div>
        </div>
    </AppLayout>
</template>
