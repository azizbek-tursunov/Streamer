<script setup lang="ts">
import { useForm, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { index, store, update } from '@/routes/permissions';

const props = defineProps<{
    permission?: {
        id: number;
        name: string;
    };
}>();

const form = useForm({
    name: props.permission?.name ?? '',
});

const submit = () => {
    if (props.permission) {
        form.put(update(props.permission.id).url);
    } else {
        form.post(store().url);
    }
};
</script>

<template>
    <Head :title="permission ? 'Ruxsatnomani Tahrirlash' : 'Yangi Ruxsatnoma'" />

    <AppLayout :breadcrumbs="[
        { title: 'Xavfsizlik', href: '#' },
        { title: 'Ruxsatnomalar', href: index().url },
        { title: permission ? 'Tahrirlash' : 'Yangi', href: '#' },
    ]">
        <div class="max-w-5xl mx-auto p-4 md:p-6 lg:p-8">
            <form @submit.prevent="submit">
                <div class="grid grid-cols-1 gap-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>{{ permission ? 'Ruxsatnomani Tahrirlash' : 'Yangi Ruxsatnoma' }}</CardTitle>
                            <CardDescription>
                                Ruxsatnoma nomini kiriting.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2">
                                <Label for="name">Ruxsatnoma Nomi</Label>
                                <Input id="name" v-model="form.name" required placeholder="Masalan: manage users" />
                                <div v-if="form.errors.name" class="text-sm text-red-500">{{ form.errors.name }}</div>
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
