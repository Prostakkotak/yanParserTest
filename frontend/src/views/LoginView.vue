<template>
  <div class="container">
    <div class="card login">
      <h1>Вход</h1>
      <p class="muted">Используйте учётную запись администратора.</p>

      <form @submit.prevent="onSubmit">
        <label class="field">
          <span>Email</span>
          <input v-model="email" type="email" autocomplete="username" required />
        </label>

        <label class="field">
          <span>Пароль</span>
          <input v-model="password" type="password" autocomplete="current-password" required />
        </label>

        <p v-if="auth.error" class="error">{{ auth.error }}</p>

        <button class="btn" type="submit" :disabled="auth.loading">
          {{ auth.loading ? 'Вход...' : 'Войти' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const email = ref('')
const password = ref('')

async function onSubmit() {
  const ok = await auth.login({
    email: email.value,
    password: password.value,
  })

  if (ok) {
    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : '/reviews'
    router.push(redirect)
  }
}
</script>

<style scoped>
.login {
  max-width: 420px;
  margin: 4rem auto;
}
</style>
