<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Camera, Branch, Floor, Faculty, BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const props = defineProps<{
    camera?: Camera;
    branches: Branch[];
    floors: Floor[];
    faculties: Faculty[];
}>();

const form = useForm({
    name: props.camera?.name ?? '',
    username: props.camera?.username ?? '',
    password: props.camera?.password ?? '',
    ip_address: props.camera?.ip_address ?? '',
    port: props.camera?.port ?? 554,
    stream_path: props.camera?.stream_path ?? '/',
    youtube_url: props.camera?.youtube_url ?? '',
    is_active: props.camera?.is_active ?? true,
    branch_id: props.camera?.branch_id?.toString() ?? '',
    floor_id: props.camera?.floor_id?.toString() ?? '',
    faculty_id: props.camera?.faculty_id?.toString() ?? '',
});

const submit = () => {
    // Convert string IDs back to numbers or null
    const data = {
        ...form.data(),
        branch_id: form.branch_id ? parseInt(form.branch_id) : null,
        floor_id: form.floor_id ? parseInt(form.floor_id) : null,
        faculty_id: form.faculty_id ? parseInt(form.faculty_id) : null,
    };
    
    if (props.camera) {
        form.transform(() => data).put(`/cameras/${props.camera.id}`);
    } else {
        form.transform(() => data).post('/cameras');
    }
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Kameralar', href: '/cameras' },
    { title: props.camera ? 'Tahrirlash' : 'Yangi kamera', href: '#' },
];
</script>

<template>
    <Head :title="camera ? 'Kamerani tahrirlash' : 'Yangi kamera'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 items-center justify-center">
            <Card class="w-full max-w-2xl">
                <CardHeader>
                    <CardTitle>{{ camera ? 'Kamerani tahrirlash' : 'Yangi kamera qo\'shish' }}</CardTitle>
                    <CardDescription>
                        RTSP kameraning ulanish ma'lumotlarini kiriting.
                    </CardDescription>
                </CardHeader>
                <form @submit.prevent="submit">
                    <CardContent class="grid gap-4">
                        <!-- Category Section -->
                        <div class="grid grid-cols-3 gap-4 pb-4 border-b">
                            <div class="space-y-2">
                                <Label for="branch_id">Filial *</Label>
                                <Select v-model="form.branch_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Tanlang..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="branch in branches" :key="branch.id" :value="branch.id.toString()">
                                            {{ branch.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.branch_id" class="text-sm text-destructive">{{ form.errors.branch_id }}</div>
                            </div>
                            <div class="space-y-2">
                                <Label for="floor_id">Qavat</Label>
                                <Select v-model="form.floor_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Tanlang..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">— Tanlanmagan —</SelectItem>
                                        <SelectItem v-for="floor in floors" :key="floor.id" :value="floor.id.toString()">
                                            {{ floor.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.floor_id" class="text-sm text-destructive">{{ form.errors.floor_id }}</div>
                            </div>
                            <div class="space-y-2">
                                <Label for="faculty_id">Fakultet</Label>
                                <Select v-model="form.faculty_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Tanlang..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">— Tanlanmagan —</SelectItem>
                                        <SelectItem v-for="faculty in faculties" :key="faculty.id" :value="faculty.id.toString()">
                                            {{ faculty.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.faculty_id" class="text-sm text-destructive">{{ form.errors.faculty_id }}</div>
                            </div>
                        </div>

                         <div class="space-y-2">
                            <Label for="name">Kamera nomi</Label>
                            <Input id="name" v-model="form.name" placeholder="Bosh kirish kamerasi" required />
                            <div v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="ip_address">IP manzil</Label>
                                <Input id="ip_address" v-model="form.ip_address" placeholder="192.168.1.126" required />
                                <div v-if="form.errors.ip_address" class="text-sm text-destructive">{{ form.errors.ip_address }}</div>
                            </div>
                             <div class="space-y-2">
                                <Label for="port">Port</Label>
                                <Input id="port" type="number" v-model="form.port" placeholder="554" required />
                                <div v-if="form.errors.port" class="text-sm text-destructive">{{ form.errors.port }}</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                             <div class="space-y-2">
                                <Label for="username">Foydalanuvchi nomi (ixtiyoriy)</Label>
                                <Input id="username" v-model="form.username" placeholder="admin" />
                                <div v-if="form.errors.username" class="text-sm text-destructive">{{ form.errors.username }}</div>
                            </div>
                             <div class="space-y-2">
                                <Label for="password">Parol (ixtiyoriy)</Label>
                                <Input id="password" type="password" v-model="form.password" placeholder="••••••" />
                                <div v-if="form.errors.password" class="text-sm text-destructive">{{ form.errors.password }}</div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="stream_path">Stream yo'li</Label>
                            <Input id="stream_path" v-model="form.stream_path" placeholder="/" />
                            <p class="text-[0.8rem] text-muted-foreground">Portdan keyingi yo'l (masalan: /stream1 yoki /live). Hech narsa bo'lmasa / qoldiring.</p>
                            <div v-if="form.errors.stream_path" class="text-sm text-destructive">{{ form.errors.stream_path }}</div>
                        </div>

                        <div class="space-y-2 pt-2 border-t">
                            <Label for="youtube_url">YouTube Stream kaliti / URL</Label>
                            <Input id="youtube_url" v-model="form.youtube_url" placeholder="rtmp://a.rtmp.youtube.com/live2/YOUR-STREAM-KEY" />
                            <p class="text-[0.8rem] text-muted-foreground">Ixtiyoriy: YouTubega qayta uzatish uchun sozlang.</p>
                            <div v-if="form.errors.youtube_url" class="text-sm text-destructive">{{ form.errors.youtube_url }}</div>
                        </div>
                
                        <div class="flex items-center space-x-2 pt-2">
                            <Checkbox id="is_active" :checked="form.is_active" @update:checked="form.is_active = $event" />
                            <Label for="is_active">Faol</Label>
                        </div>
                    </CardContent>
                    <CardFooter class="flex justify-between">
                        <Button variant="ghost" as-child>
                            <Link href="/cameras">Bekor qilish</Link>
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Saqlanmoqda...' : 'Saqlash' }}
                        </Button>
                    </CardFooter>
                </form>
            </Card>
        </div>
    </AppLayout>
</template>
