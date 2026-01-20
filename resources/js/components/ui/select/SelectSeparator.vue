<script setup lang="ts">
import { computed, type HTMLAttributes } from 'vue'
import { SelectSeparator, type SelectSeparatorProps, useForwardProps } from 'radix-vue'
import { cn } from '@/lib/utils'

const props = defineProps<SelectSeparatorProps & { class?: HTMLAttributes['class'] }>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props

  return delegated
})

const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <SelectSeparator v-bind="forwardedProps" :class="cn('-mx-1 my-1 h-px bg-muted', props.class)" />
</template>
