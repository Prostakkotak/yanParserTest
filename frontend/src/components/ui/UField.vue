<template>
  <label class="mb-3 flex flex-col gap-1">
    <span class="text-sm">{{ label }}</span>
    <slot />
  </label>
</template>

<script setup lang="ts">
defineProps<{
  label: string
}>()
</script>
