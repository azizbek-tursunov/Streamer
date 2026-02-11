<script setup lang="ts">
import { useForm, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { index, store, update } from '@/routes/users';

const props = defineProps<{
    user?: {
        id: number;
        name: string;
        email: string;
    };
    roles: Array<{ id: number; name: string }>;
    userRoles?: number[];
}>();

const form = useForm<{
    name: string;
    email: string; // Add email and password
    password: string;
    password_confirmation: string;
    roles: number[];
}>({
    name: props.user?.name ?? '',
    email: props.user?.email ?? '',
    password: '',
    password_confirmation: '',
    roles: props.userRoles ?? [],
});

const submit = () => {
    if (props.user) {
        form.put(update(props.user.id).url);
    } else {
        form.post(store().url);
    }
};
</script>

<template>
    <Head :title="user ? 'Foydalanuvchini Tahrirlash' : 'Yangi Foydalanuvchi'" />

    <AppLayout :breadcrumbs="[
        { title: 'Xavfsizlik', href: '#' },
        { title: 'Foydalanuvchilar', href: index().url },
        { title: user ? 'Tahrirlash' : 'Yangi', href: '#' },
    ]">
        <div class="max-w-5xl mx-auto p-4 md:p-6 lg:p-8">
            <form @submit.prevent="submit">
                <div class="grid grid-cols-1 gap-6">
                    <!-- Account Information -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Hisob Ma'lumotlari</CardTitle>
                            <CardDescription>
                                Foydalanuvchining asosiy ma'lumotlarini kiriting.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <Label for="name">To'liq Ism</Label>
                                <Input id="name" v-model="form.name" required placeholder="Masalan: Azizbek Tursunov" />
                                <div v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</div>
                            </div>

                            <div class="space-y-2">
                                <Label for="email">Email</Label>
                                <Input id="email" type="email" v-model="form.email" required placeholder="user@example.com" />
                                <div v-if="form.errors.email" class="text-sm text-red-500">{{ form.errors.email }}</div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Security -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Xavfsizlik</CardTitle>
                            <CardDescription>
                                Parol sozlamalari. {{ user ? "O'zgartirish uchun to'ldiring, aks holda bo'sh qoldiring." : "Yangi foydalanuvchi uchun parol majburiy." }}
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="grid grid-cols-1 md:grid-cols-2 gap-6">
                             <div class="space-y-2">
                                <Label for="password">Parol</Label>
                                <Input id="password" type="password" v-model="form.password" :required="!user" />
                                <div v-if="form.errors.password" class="text-sm text-red-500">{{ form.errors.password }}</div>
                            </div>

                            <div class="space-y-2">
                                <Label for="password_confirmation">Parolni tasdiqlang</Label>
                                <Input id="password_confirmation" type="password" v-model="form.password_confirmation" :required="!user" />
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Roles -->
                     <Card>
                        <CardHeader>
                            <CardTitle>Rollar</CardTitle>
                            <CardDescription>
                                Foydalanuvchiga tegishli rollarni biriktiring.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div v-for="role in roles" :key="role.id" class="flex items-center space-x-2 border p-3 rounded-md hover:bg-muted/50 transition-colors">
                                    <Checkbox 
                                        :id="`role-${role.id}`" 
                                        :model-value="form.roles.includes(role.id)"
                                        @update:model-value="(checked: boolean | 'indeterminate') => {
                                            if (checked) form.roles.push(role.id);
                                            else form.roles = form.roles.filter(r => r !== role.id);
                                        }"
                                    />
                                    <Label :for="`role-${role.id}`" class="font-normal cursor-pointer flex-1">{{ role.name }}</Label>
                                </div>
                            </div>
                             <div v-if="form.errors.roles" class="text-sm text-red-500 mt-2">{{ form.errors.roles }}</div>
                        </CardContent>
                    </Card>

                    <!-- Actions -->
                    <div class="flex justify-end gap-4">
                        <Button type="button" variant="outline" as-child>
                            <Link :href="index().url">Bekor qilish</Link>
                        </Button>
                        <Button type="submit" :disabled="form.processing" size="lg">
                           Saqlash
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
