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
    camera: Camera;
}>();

const emit = defineEmits(['update:open']);

const form = useForm({
    youtube_url: '',
});

watch(() => props.open, (isOpen) => {
    if (isOpen && props.camera) {
        form.youtube_url = props.camera.youtube_url ?? '';
        form.clearErrors();
    }
});

const submit = () => {
    form.put(`/cameras/${props.camera.id}/youtube`, {
        onSuccess: () => emit('update:open', false),
    });
};
</script>

<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="sm:max-w-[425px]">
      <DialogHeader>
        <DialogTitle>YouTube Configuration</DialogTitle>
        <DialogDescription>
            Enter your YouTube RTMP URL or Stream Key to enable restreaming.
        </DialogDescription>
      </DialogHeader>
      
      <form @submit.prevent="submit" class="grid gap-4 py-4">
        <div class="space-y-2">
            <Label for="youtube_url">YouTube Stream URL / Key</Label>
            <Input id="youtube_url" v-model="form.youtube_url" placeholder="rtmp://a.rtmp.youtube.com/live2/..." />
            <p class="text-[0.8rem] text-muted-foreground">Example: rtmp://a.rtmp.youtube.com/live2/YOUR-KEY</p>
            <div v-if="form.errors.youtube_url" class="text-sm text-destructive">{{ form.errors.youtube_url }}</div>
        </div>

        <DialogFooter>
            <Button type="button" variant="ghost" @click="$emit('update:open', false)">Cancel</Button>
            <Button type="submit" :disabled="form.processing">
                {{ form.processing ? 'Save Configuration' : 'Save Configuration' }}
            </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
