<template>
  <div class="mx-auto max-w-4xl px-4 py-3">
    <UCard class="mx-auto mt-8 max-w-[420px]">
      <h1 class="mb-1.5 text-xl font-semibold">Вход</h1>
      <p class="mb-4 text-sm text-gray-500">Используйте учётную запись администратора.</p>

      <form @submit.prevent="onSubmit">
        <UField label="Email">
          <UInput
            v-model="email"
            type="email"
            autocomplete="username"
            required
          />
        </UField>

        <UField label="Пароль">
          <UInput
            v-model="password"
            type="password"
            autocomplete="current-password"
            required
          />
        </UField>

        <p v-if="auth.error" class="mb-3 text-sm text-red-600">{{ auth.error }}</p>

        <UButton type="submit" :loading="auth.loading" :disabled="auth.loading">
          {{ auth.loading ? 'Вход...' : 'Войти' }}
        </UButton>
      </form>
    </UCard>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import UButton from '@/components/ui/UButton.vue'
import UCard from '@/components/ui/UCard.vue'
import UField from '@/components/ui/UField.vue'
import UInput from '@/components/ui/UInput.vue'
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
    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : '/'
    router.push(redirect)
  }
}
</script>
