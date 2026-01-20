<script setup lang="ts">
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Camera } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';

const props = defineProps<{
    open: boolean;
    camera?: Camera | null;
}>();

const emit = defineEmits(['update:open']);

const form = useForm({
    name: '',
    username: '',
    password: '',
    ip_address: '',
    port: 554,
    // stream_path removed (handled by default '/')
    is_active: true,
});

watch(() => props.open, (isOpen) => {
    if (isOpen) {
        if (props.camera) {
            form.name = props.camera.name;
            form.username = props.camera.username ?? '';
            form.password = props.camera.password ?? '';
            form.ip_address = props.camera.ip_address;
            form.port = props.camera.port;
            form.is_active = Boolean(props.camera.is_active);
        } else {
            form.reset();
            form.port = 554;
            form.is_active = true;
        }
        form.clearErrors();
    }
});

const submit = () => {
    if (props.camera) {
        form.put(`/cameras/${props.camera.id}`, {
            onSuccess: () => emit('update:open', false),
        });
    } else {
        form.post('/cameras', {
            onSuccess: () => emit('update:open', false),
        });
    }
};
</script>

<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="sm:max-w-[600px]">
      <DialogHeader>
        <DialogTitle>{{ camera ? 'Edit Camera' : 'Add New Camera' }}</DialogTitle>
        <DialogDescription>
          {{ camera ? 'Update camera details.' : 'Configure your new RTSP camera.' }}
        </DialogDescription>
      </DialogHeader>
      
      <form @submit.prevent="submit" class="grid gap-4 py-4">
        <div class="space-y-2">
            <Label for="name">Camera Name</Label>
            <Input id="name" v-model="form.name" placeholder="Front Gate Camera" required />
            <div v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
                <Label for="ip_address">IP Address</Label>
                <Input id="ip_address" v-model="form.ip_address" placeholder="192.168.1.100" required />
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
        
        <DialogFooter>
            <Button type="button" variant="ghost" @click="$emit('update:open', false)">Cancel</Button>
            <Button type="submit" :disabled="form.processing">
                {{ form.processing ? 'Saving...' : 'Save Camera' }}
            </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
