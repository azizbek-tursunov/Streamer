<script setup lang="ts">
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Camera, Branch, Floor, Faculty } from '@/types';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
    branches: Branch[];
    floors: Floor[];
    faculties: Faculty[];
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
    branch_id: '',
    floor_id: '',
    faculty_id: '',
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
            form.branch_id = props.camera.branch_id?.toString() ?? '';
            form.floor_id = props.camera.floor_id?.toString() ?? '';
            form.faculty_id = props.camera.faculty_id?.toString() ?? '';
        } else {
            form.reset();
            form.port = 554;
            form.is_active = true;
        }
        form.clearErrors();
    }
});

const submit = () => {
    const data = {
        ...form.data(),
        branch_id: form.branch_id ? parseInt(form.branch_id) : null,
        floor_id: form.floor_id ? parseInt(form.floor_id) : null,
        faculty_id: form.faculty_id ? parseInt(form.faculty_id) : null,
    };

    if (props.camera) {
        form.transform(() => data).put(`/cameras/${props.camera.id}`, {
            onSuccess: () => emit('update:open', false),
        });
    } else {
        form.transform(() => data).post('/cameras', {
            onSuccess: () => emit('update:open', false),
        });
    }
};
</script>

<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="sm:max-w-[600px]">
      <DialogHeader>
        <DialogTitle>{{ camera ? 'Kamerani Tahrirlash' : 'Yangi Kamera Qo\'shish' }}</DialogTitle>
        <DialogDescription>
          {{ camera ? 'Kamera ma\'lumotlarini yangilang.' : 'Yangi RTSP kamerani sozlang.' }}
        </DialogDescription>
      </DialogHeader>
      
      <form @submit.prevent="submit" class="grid gap-4 py-4">
        <!-- Category Section -->
        <div class="grid grid-cols-3 gap-4 pb-4 border-b">
            <div class="space-y-2">
                <Label for="branch_id">Filial *</Label>
                <Select v-model="form.branch_id">
                    <SelectTrigger>
                        <SelectValue placeholder="Tanlang" />
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
                        <SelectValue placeholder="Tanlang" />
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
                        <SelectValue placeholder="Tanlang" />
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
            <Label for="name">Kamera Nomi</Label>
            <Input id="name" v-model="form.name" placeholder="Masalan: Kirish Darvozasi" required />
            <div v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
                <Label for="ip_address">IP Manzil</Label>
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
                <Label for="username">Foydalanuvchi (Ixtiyoriy)</Label>
                <Input id="username" v-model="form.username" placeholder="admin" />
                <div v-if="form.errors.username" class="text-sm text-destructive">{{ form.errors.username }}</div>
            </div>
             <div class="space-y-2">
                <Label for="password">Parol (Ixtiyoriy)</Label>
                <Input id="password" type="password" v-model="form.password" placeholder="••••••" />
                <div v-if="form.errors.password" class="text-sm text-destructive">{{ form.errors.password }}</div>
            </div>
        </div>
        
        <DialogFooter>
            <Button type="button" variant="ghost" @click="$emit('update:open', false)">Bekor Qilish</Button>
            <Button type="submit" :disabled="form.processing">
                {{ form.processing ? 'Saqlanmoqda...' : 'Saqlash' }}
            </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
