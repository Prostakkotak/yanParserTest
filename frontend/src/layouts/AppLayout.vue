<template>
  <div class="min-h-screen">
    <header class="mb-3 border-b border-gray-200 bg-white">
      <div class="mx-auto flex max-w-4xl items-center justify-between gap-3 px-4 py-2.5">
        <strong class="text-base">{{ appName }}</strong>
        <nav>
          <UButton variant="secondary" @click="onLogout">Выйти</UButton>
        </nav>
      </div>
    </header>

    <main class="mx-auto max-w-4xl px-4 py-3">
      <RouterView />
    </main>
  </div>
</template>

<script setup lang="ts">
import { RouterView, useRouter } from 'vue-router'
import UButton from '@/components/ui/UButton.vue'
import { useAuthStore } from '@/stores/auth'

const appName = import.meta.env.VITE_APP_NAME || 'YanParser'
const auth = useAuthStore()
const router = useRouter()

async function onLogout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>
