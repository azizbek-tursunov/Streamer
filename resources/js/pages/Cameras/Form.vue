<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Camera, BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card';

const props = defineProps<{
    camera?: Camera;
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
});

const submit = () => {
    if (props.camera) {
        form.put(`/cameras/${props.camera.id}`);
    } else {
        form.post('/cameras');
    }
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Cameras', href: '/cameras' },
    { title: props.camera ? 'Edit Camera' : 'Add Camera', href: '#' },
];
</script>

<template>
    <Head :title="camera ? 'Edit Camera' : 'Add Camera'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4 items-center justify-center">
            <Card class="w-full max-w-2xl">
                <CardHeader>
                    <CardTitle>{{ camera ? 'Edit Camera' : 'Add New Camera' }}</CardTitle>
                    <CardDescription>
                        Configure your RTSP camera by providing its connection details.
                    </CardDescription>
                </CardHeader>
                <form @submit.prevent="submit">
                    <CardContent class="grid gap-4">
                         <div class="space-y-2">
                            <Label for="name">Camera Name</Label>
                            <Input id="name" v-model="form.name" placeholder="Front Gate Camera" required />
                            <div v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="ip_address">IP Address</Label>
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
                                <Label for="username">Username (Optional)</Label>
                                <Input id="username" v-model="form.username" placeholder="admin" />
                                <div v-if="form.errors.username" class="text-sm text-destructive">{{ form.errors.username }}</div>
                            </div>
                             <div class="space-y-2">
                                <Label for="password">Password (Optional)</Label>
                                <Input id="password" type="password" v-model="form.password" placeholder="••••••" />
                                <div v-if="form.errors.password" class="text-sm text-destructive">{{ form.errors.password }}</div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="stream_path">Stream Path</Label>
                            <Input id="stream_path" v-model="form.stream_path" placeholder="/" />
                            <p class="text-[0.8rem] text-muted-foreground">The path after the port (e.g. /stream1 or /live). Leave as / if none.</p>
                            <div v-if="form.errors.stream_path" class="text-sm text-destructive">{{ form.errors.stream_path }}</div>
                        </div>

                        <div class="space-y-2 pt-2 border-t">
                            <Label for="youtube_url">YouTube Stream Key / URL</Label>
                            <Input id="youtube_url" v-model="form.youtube_url" placeholder="rtmp://a.rtmp.youtube.com/live2/YOUR-STREAM-KEY" />
                            <p class="text-[0.8rem] text-muted-foreground">Optional: Configure this to enable restreaming to YouTube.</p>
                            <div v-if="form.errors.youtube_url" class="text-sm text-destructive">{{ form.errors.youtube_url }}</div>
                        </div>
                
                        <div class="flex items-center space-x-2 pt-2">
                            <Checkbox id="is_active" :checked="form.is_active" @update:checked="form.is_active = $event" />
                            <Label for="is_active">Active</Label>
                        </div>
                    </CardContent>
                    <CardFooter class="flex justify-between">
                        <Button variant="ghost" as-child>
                            <Link href="/cameras">Cancel</Link>
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Saving...' : 'Save Camera' }}
                        </Button>
                    </CardFooter>
                </form>
            </Card>
        </div>
    </AppLayout>
</template>
