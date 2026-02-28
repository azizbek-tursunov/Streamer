<script setup lang="ts">
import { ref, watch } from 'vue';
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
const streamKey = ref('');
const YOUTUBE_RTMP_PREFIX = 'rtmp://a.rtmp.youtube.com/live2/';

const form = useForm({
    youtube_url: '',
});

watch(() => props.open, (isOpen) => {
    if (isOpen && props.camera) {
        if (props.camera.youtube_url && props.camera.youtube_url.startsWith(YOUTUBE_RTMP_PREFIX)) {
            streamKey.value = props.camera.youtube_url.replace(YOUTUBE_RTMP_PREFIX, '');
        } else {
             // If valid but different prefix, show nothing or maybe everything? 
             // Logic: If user had a custom URL, we might lose it here if we assume prefix.
             // Given the requirements "Make me only enter key", we enforce this prefix.
             // If it doesn't match, we start blank or show what matches.
             // Safe bet: Empty if no match, or suffix if match.
             streamKey.value = ''; 
        }
        form.clearErrors();
    }
});

const submit = () => {
    form.youtube_url = `${YOUTUBE_RTMP_PREFIX}${streamKey.value}`;
    form.put(`/cameras/${props.camera.id}/youtube`, {
        onSuccess: () => emit('update:open', false),
    });
};
</script>

<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="sm:max-w-[425px]">
      <DialogHeader>
        <DialogTitle>YouTube Sozlamalari</DialogTitle>
        <DialogDescription>
            YouTube Stream kalitini kiriting. RTMP havola avtomatik yaratiladi.
        </DialogDescription>
      </DialogHeader>
      
      <form @submit.prevent="submit" class="grid gap-4 py-4">
        <div class="space-y-2">
            <Label for="stream_key">YouTube Stream Kaliti</Label>
            <div class="flex items-center gap-2">
                <span class="text-sm text-muted-foreground whitespace-nowrap bg-muted px-3 py-2 rounded-l-md border border-r-0">rtmp://.../live2/</span>
                <Input id="stream_key" v-model="streamKey" placeholder="abcd-1234-efgh-5678" class="rounded-l-none" />
            </div>
            <p class="text-[0.8rem] text-muted-foreground">YouTube Studio'dan kalitni nusxalang.</p>
            <div v-if="form.errors.youtube_url" class="text-sm text-destructive">{{ form.errors.youtube_url }}</div>
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
