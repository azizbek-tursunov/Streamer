<script setup lang="ts">
import { useForm, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { index, store, update } from '@/routes/roles';

const props = defineProps<{
    role?: {
        id: number;
        name: string;
    };
    permissions: Array<{ id: number; name: string }>;
    rolePermissions?: number[];
}>();

const form = useForm<{
    name: string;
    permissions: number[];
}>({
    name: props.role?.name ?? '',
    permissions: props.rolePermissions ?? [],
});


const submit = () => {
    if (props.role) {
        form.put(update(props.role.id).url);
    } else {
        form.post(store().url);
    }
};
</script>

<template>
    <Head :title="role ? 'Rolni Tahrirlash' : 'Yangi Rol'" />

    <AppLayout :breadcrumbs="[
        { title: 'Xavfsizlik', href: '#' },
        { title: 'Rollar', href: index().url },
        { title: role ? 'Tahrirlash' : 'Yangi', href: '#' },
    ]">
        <div class="max-w-5xl mx-auto p-4 md:p-6 lg:p-8">
            <form @submit.prevent="submit">
                <div class="grid grid-cols-1 gap-6">
                    <!-- Role Details -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Rol Ma'lumotlari</CardTitle>
                            <CardDescription>
                                Rol nomini kiriting.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2">
                                <Label for="name">Rol Nomi</Label>
                                <Input id="name" v-model="form.name" required placeholder="Masalan: Manager" />
                                <div v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Permissions -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Ruxsatnomalar</CardTitle>
                            <CardDescription>
                                Ushbu rolga qaysi ruxsatnomalar tegishli ekanligini belgilang.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 border p-4 rounded-md max-h-[500px] overflow-y-auto">
                                <div v-for="permission in permissions" :key="permission.id" class="flex items-center space-x-2">
                                    <Checkbox 
                                        :id="`perm-${permission.id}`" 
                                        :model-value="form.permissions.includes(permission.id)"
                                        @update:model-value="(checked: boolean | 'indeterminate') => {
                                            if (checked) form.permissions.push(permission.id);
                                            else form.permissions = form.permissions.filter(p => p !== permission.id);
                                        }"
                                    />
                                    <Label :for="`perm-${permission.id}`" class="font-normal cursor-pointer text-sm flex-1">{{ permission.name }}</Label>
                                </div>
                            </div>
                        </CardContent>
                    </Card>


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
