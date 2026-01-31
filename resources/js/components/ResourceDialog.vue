<script setup lang="ts">
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
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
    item?: { id: number; name: string } | null;
    title: string;
    url: string;
    label?: string;
    description?: string;
}>();

const emit = defineEmits(['update:open']);

const form = useForm({
    name: '',
});

watch(() => props.open, (isOpen) => {
    if (isOpen) {
        if (props.item) {
            form.name = props.item.name;
        } else {
            form.reset();
        }
        form.clearErrors();
    }
});

const submit = () => {
    if (props.item) {
        form.put(`${props.url}/${props.item.id}`, {
            onSuccess: () => emit('update:open', false),
        });
    } else {
        form.post(props.url, {
            onSuccess: () => emit('update:open', false),
        });
    }
};
</script>

<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="sm:max-w-[425px]">
      <DialogHeader>
        <DialogTitle>{{ item ? `${title}ni Tahrirlash` : `Yangi ${title} Qo'shish` }}</DialogTitle>
        <DialogDescription>
          {{ description || (item ? `${title} ma'lumotlarini yangilang.` : `Yangi ${title} yarating.`) }}
        </DialogDescription>
      </DialogHeader>
      
      <form @submit.prevent="submit" class="grid gap-4 py-4">
        <div class="space-y-2">
            <Label for="name">{{ label || 'Nomi' }}</Label>
            <Input id="name" v-model="form.name" required autofocus />
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
