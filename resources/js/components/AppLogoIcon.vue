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
    <svg
        viewBox="0 0 100 100"
        xmlns="http://www.w3.org/2000/svg"
        :class="className"
        v-bind="$attrs"
    >
        <!-- Left vertical block (Dark Blue) -->
        <path d="M15 25 L35 15 L35 85 L15 75 Z" class="fill-slate-900 dark:fill-slate-100" />
        
        <!-- Top angled ribbon (Light Blue) -->
        <path d="M35 15 L85 50 L65 55 L35 30 Z" class="fill-sky-500" />
        
        <!-- Bottom angled ribbon (Light Blue) -->
        <path d="M35 85 L85 50 L65 45 L35 70 Z" class="fill-sky-500" />
        
        <!-- Inner shadow/depth (Dark Blue) -->
        <path d="M35 30 L65 50 L35 70 Z" class="fill-slate-800 dark:fill-slate-300" />
    </svg>
</template>
