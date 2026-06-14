import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import * as authApi from '@/api/auth'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const initialized = ref(false)
  const loading = ref(false)
  const error = ref(null)

  const isAuthenticated = computed(() => Boolean(user.value))

  async function bootstrap() {
    try {
      user.value = await authApi.fetchUser()
    } catch {
      user.value = null
    } finally {
      initialized.value = true
    }
  }

  async function login(credentials) {
    loading.value = true
    error.value = null

    try {
      const data = await authApi.login(credentials)
      user.value = data.user
      return true
    } catch (err) {
      error.value = err.response?.data?.message || 'Не удалось войти.'
      return false
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    await authApi.logout()
    user.value = null
  }

  return {
    user,
    initialized,
    loading,
    error,
    isAuthenticated,
    bootstrap,
    login,
    logout,
  }
})
