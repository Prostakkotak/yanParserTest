<template>
  <span
    class="skeleton block h-3.5 rounded"
    :class="wide ? 'skeleton--wide' : 'skeleton--narrow'"
    aria-hidden="true"
  />
</template>

<script setup lang="ts">
defineProps<{
  wide?: boolean
}>()
</script>

<style lang="scss" scoped>
.skeleton {
  background: linear-gradient(90deg, #e5e7eb 25%, #f3f4f6 50%, #e5e7eb 75%);
  background-size: 200% 100%;
  animation: skeleton-shimmer 1.2s ease-in-out infinite;
}

.skeleton--wide {
  width: 85%;
  max-width: 12rem;
}

.skeleton--narrow {
  width: 2.5rem;
}

@keyframes skeleton-shimmer {
  0% {
    background-position: 200% 0;
  }

  100% {
    background-position: -200% 0;
  }
}
</style>
