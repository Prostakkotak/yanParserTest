<template>
  <div class="layout">
    <header class="layout__header">
      <div class="container layout__header-inner">
        <strong>{{ appName }}</strong>
        <nav class="layout__nav">
          <button class="btn btn-secondary" type="button" @click="onLogout">
            Выйти
          </button>
        </nav>
      </div>
    </header>

    <main class="container">
      <RouterView />
    </main>
  </div>
</template>

<script setup>
import { RouterView, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const appName = import.meta.env.VITE_APP_NAME || 'YanParser'
const auth = useAuthStore()
const router = useRouter()

async function onLogout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<style scoped>
.layout__header {
  background: #fff;
  border-bottom: 1px solid #e5e7eb;
  margin-bottom: 1.5rem;
}

.layout__header-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.layout__nav {
  display: flex;
  align-items: center;
  gap: 1rem;
}
</style>
