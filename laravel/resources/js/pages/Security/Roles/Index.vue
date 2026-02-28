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
import { Pencil, Trash2, Plus, Search } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import Pagination from '@/components/Pagination.vue';
import { ref, watch } from 'vue';
import { debounce } from 'lodash';
import { index, create, edit, destroy } from '@/routes/roles';

const props = defineProps<{
    roles: {
        data: Array<{
            id: number;
            name: string;
            permissions: Array<{ name: string }>;
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

const deleteRole = (id: number) => {
    if (confirm('Rolni o\'chirishni tasdiqlaysizmi?')) {
        router.delete(destroy(id).url);
    }
};
</script>

<template>
    <Head title="Rollar" />

    <AppLayout :breadcrumbs="[
        { title: 'Xavfsizlik', href: '#' },
        { title: 'Rollar', href: index().url },
    ]">
        <div class="px-4 py-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        Rollar
                    </h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        Tizim rollari va ularning ruxsatnomalarini boshqarish.
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
                        placeholder="Rol nomi bo'yicha qidirish..." 
                        class="pl-8" 
                    />
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Rol Nomi</TableHead>
                            <TableHead>Ruxsatnomalar</TableHead>
                            <TableHead class="text-right">Amallar</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="roles.data.length === 0">
                            <TableCell colspan="3" class="h-24 text-center">
                                Rollar topilmadi.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="role in roles.data" :key="role.id">
                            <TableCell class="font-medium align-top">
                                <Badge variant="outline">{{ role.name }}</Badge>
                            </TableCell>
                            <TableCell>
                                <div class="flex flex-wrap gap-1">
                                    <Badge 
                                        v-for="perm in role.permissions.slice(0, 5)" 
                                        :key="perm.name" 
                                        variant="secondary"
                                        class="text-xs"
                                    >
                                        {{ perm.name }}
                                    </Badge>
                                    <span v-if="role.permissions.length > 5" class="text-xs text-muted-foreground self-center px-1">
                                        +{{ role.permissions.length - 5 }} ta
                                    </span>
                                    <span v-if="role.permissions.length === 0" class="text-muted-foreground text-xs italic">
                                        Ruxsatnoma yo'q
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell class="text-right align-top">
                                <div class="flex justify-end gap-2">
                                    <Button variant="ghost" size="icon" as-child>
                                        <Link :href="edit(role.id).url">
                                            <Pencil class="w-4 h-4" />
                                        </Link>
                                    </Button>
                                    <Button 
                                        variant="ghost" 
                                        size="icon" 
                                        @click="deleteRole(role.id)"
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
                <Pagination :links="roles.links" />
            </div>
        </div>
    </AppLayout>
</template>
