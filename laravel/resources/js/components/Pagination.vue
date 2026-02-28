<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

defineProps<{
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
}>();
</script>

<template>
    <div v-if="links.length > 3">
        <div class="flex flex-wrap items-center justify-center gap-1 -mb-1">
            <template v-for="(link, key) in links" :key="key">
                <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-2 text-sm text-gray-400 border rounded opacity-50 cursor-not-allowed" v-html="link.label" />
                
                <Button 
                    v-else
                    as-child
                    :variant="link.active ? 'default' : 'outline'"
                    class="mr-1 mb-1"
                    :class="{ 'border-primary': link.active }"
                >
                    <Link :href="link.url" v-html="link.label" preserve-scroll />
                </Button>
            </template>
        </div>
    </div>
</template>
