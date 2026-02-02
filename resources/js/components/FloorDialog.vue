<script setup lang="ts">
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';

interface Branch {
    id: number;
    name: string;
}

interface Floor {
    id: number;
    name: string;
    branch_id: number | null;
}

const props = defineProps<{
    open: boolean;
    item?: Floor | null;
    branches: Branch[];
}>();

const emit = defineEmits(['update:open']);

const form = useForm({
    name: '',
    branch_id: '' as string,
});

watch(() => props.open, (isOpen) => {
    if (isOpen) {
        if (props.item) {
            form.name = props.item.name;
            form.branch_id = props.item.branch_id ? String(props.item.branch_id) : '';
        } else {
            form.reset();
        }
        form.clearErrors();
    }
});

const submit = () => {
    const data = {
        name: form.name,
        branch_id: form.branch_id ? parseInt(form.branch_id) : null,
    };
    
    if (props.item) {
        form.transform(() => data).put(`/floors/${props.item.id}`, {
            onSuccess: () => emit('update:open', false),
        });
    } else {
        form.transform(() => data).post('/floors', {
            onSuccess: () => emit('update:open', false),
        });
    }
};
</script>

<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="sm:max-w-[425px]">
      <DialogHeader>
        <DialogTitle>{{ item ? "Qavatni Tahrirlash" : "Yangi Qavat Qo'shish" }}</DialogTitle>
        <DialogDescription>
          {{ item ? "Qavat ma'lumotlarini yangilang." : "Yangi qavat yarating." }}
        </DialogDescription>
      </DialogHeader>
      
      <form @submit.prevent="submit" class="grid gap-4 py-4">
        <div class="space-y-2">
            <Label for="branch_id">Filial</Label>
            <Select v-model="form.branch_id">
                <SelectTrigger>
                    <SelectValue placeholder="Filial tanlang" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem 
                        v-for="branch in branches" 
                        :key="branch.id" 
                        :value="String(branch.id)"
                    >
                        {{ branch.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <div v-if="form.errors.branch_id" class="text-sm text-destructive">{{ form.errors.branch_id }}</div>
        </div>
        
        <div class="space-y-2">
            <Label for="name">Qavat Nomi</Label>
            <Input id="name" v-model="form.name" required />
            <div v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</div>
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
