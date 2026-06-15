<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    class="inline-flex items-center justify-center rounded-md px-3 py-1.5 text-sm font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-60"
    :class="variantClasses"
  >
    <slot />
  </button>
</template>

<script setup lang="ts">
import { computed } from 'vue'

type ButtonVariant = 'primary' | 'secondary'
type ButtonType = 'button' | 'submit' | 'reset'

const props = withDefaults(
  defineProps<{
    type?: ButtonType
    variant?: ButtonVariant
    disabled?: boolean
    loading?: boolean
  }>(),
  {
    type: 'button',
    variant: 'primary',
    disabled: false,
    loading: false,
  },
)

const variantClasses = computed(() =>
  props.variant === 'secondary'
    ? 'bg-gray-200 text-gray-900 hover:bg-gray-300'
    : 'bg-blue-600 text-white hover:bg-blue-700',
)
</script>
