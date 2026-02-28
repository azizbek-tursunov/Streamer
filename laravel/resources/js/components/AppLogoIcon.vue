<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <img
        src="/images/univision_logo_transparent.png"
        alt="UniVision"
        :class="className"
        v-bind="$attrs"
        class="object-contain"
    />
</template>
