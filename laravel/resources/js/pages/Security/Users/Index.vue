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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Pencil, Trash2, Plus, Search, RefreshCw } from 'lucide-vue-next';
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
        links: any[];
    };
    roles: Array<{ id: number; name: string }>;
    filters: {
        search?: string;
        role?: string;
    };
}>();

const search = ref(props.filters.search || '');
const selectedRole = ref(props.filters.role || 'all');

watch([search, selectedRole], debounce(([newSearch, newRole]: [string, string]) => {
    const params: any = {};
    if (newSearch) params.search = newSearch;
    if (newRole && newRole !== 'all') params.role = newRole;

    router.get(index().url, params, {
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

const isSyncing = ref(false);

const syncFromHemis = () => {
    isSyncing.value = true;
    router.post('/users/sync', {}, {
        preserveScroll: true,
        onFinish: () => { isSyncing.value = false; }
    });
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
                <div class="flex gap-2 items-center">
                    <Button variant="outline" @click="syncFromHemis" :disabled="isSyncing">
                        <RefreshCw class="w-4 h-4 mr-2" :class="{ 'animate-spin': isSyncing }" />
                        {{ isSyncing ? 'Sinxronlanmoqda...' : 'HEMISdan sinxronlash' }}
                    </Button>
                    <Button as-child>
                        <Link :href="create().url">
                            <Plus class="w-4 h-4 mr-2" />
                            Yangi qo'shish
                        </Link>
                    </Button>
                </div>
            </div>

            <div class="flex items-center gap-4 mb-6">
                <!-- Search Filter -->
                <div class="relative w-full max-w-sm">
                    <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input 
                        v-model="search"
                        placeholder="Ism yoki Email bo'yicha qidirish..." 
                        class="pl-8" 
                    />
                </div>

                <!-- Role Filter -->
                <div class="w-64">
                    <Select v-model="selectedRole">
                        <SelectTrigger>
                            <SelectValue placeholder="Rollar bo'yicha filtrlash" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">
                                Barcha rollar
                            </SelectItem>
                            <SelectItem v-for="role in roles" :key="role.id" :value="role.name">
                                {{ role.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
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
