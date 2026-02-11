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
import { index, create, edit, destroy } from '@/routes/users';

const props = defineProps<{
    users: {
        data: Array<{
            id: number;
            name: string;
            email: string;
            roles: Array<{ name: string }>;
        }>;
        links: any[]; // Use proper Pagination type if available
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

const deleteUser = (id: number) => {
    if (confirm('Foydalanuvchini o\'chirishni tasdiqlaysizmi?')) {
        router.delete(destroy(id).url);
    }
};
</script>

<template>
    <Head title="Foydalanuvchilar" />

    <AppLayout :breadcrumbs="[
        { title: 'Xavfsizlik', href: '#' },
        { title: 'Foydalanuvchilar', href: index().url },
    ]">
        <div class="px-4 py-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                        Foydalanuvchilar
                    </h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        Tizim foydalanuvchilarini boshqarish.
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
                        placeholder="Ism yoki Email bo'yicha qidirish..." 
                        class="pl-8" 
                    />
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Ism</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Rollar</TableHead>
                            <TableHead class="text-right">Amallar</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="users.data.length === 0">
                            <TableCell colspan="4" class="h-24 text-center">
                                Foydalanuvchilar topilmadi.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="user in users.data" :key="user.id">
                            <TableCell class="font-medium">
                                {{ user.name }}
                            </TableCell>
                            <TableCell>{{ user.email }}</TableCell>
                            <TableCell>
                                <div class="flex flex-wrap gap-1">
                                    <Badge 
                                        v-for="role in user.roles" 
                                        :key="role.name" 
                                        variant="secondary"
                                        class="text-xs"
                                    >
                                        {{ role.name }}
                                    </Badge>
                                    <span v-if="user.roles.length === 0" class="text-muted-foreground text-xs italic">
                                        Rol biriktirilmagan
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Button variant="ghost" size="icon" as-child>
                                        <Link :href="edit(user.id).url">
                                            <Pencil class="w-4 h-4" />
                                        </Link>
                                    </Button>
                                    <Button 
                                        variant="ghost" 
                                        size="icon" 
                                        @click="deleteUser(user.id)"
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
                <Pagination :links="users.links" />
            </div>
        </div>
    </AppLayout>
</template>
