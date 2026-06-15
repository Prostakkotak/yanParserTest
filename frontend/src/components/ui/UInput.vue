<template>
  <input
    class="rounded-md border border-gray-300 px-2.5 py-1.5 text-[0.9375rem] outline-none transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:opacity-60"
    :value="modelValue"
    v-bind="$attrs"
    @input="onInput"
  />
</template>

<script setup lang="ts">
defineProps<{
  modelValue?: string | number
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

function onInput(event: Event) {
  emit('update:modelValue', (event.target as HTMLInputElement).value)
}
</script>
