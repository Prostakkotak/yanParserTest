import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import * as authApi from '@/api/auth'
import type { LoginCredentials, User } from '@/types/api'
import { getApiErrorMessage, getApiErrorStatus } from '@/utils/apiError'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const initialized = ref(false)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const isAuthenticated = computed(() => Boolean(user.value))

  async function bootstrap(): Promise<void> {
    try {
      user.value = await authApi.fetchUser()
    } catch (err) {
      user.value = null

      const status = getApiErrorStatus(err)
      if (status && status !== 401 && status !== 419) {
        error.value = getApiErrorMessage(err, 'Не удалось проверить авторизацию.')
      }
    } finally {
      initialized.value = true
    }
  }

  async function login(credentials: LoginCredentials): Promise<boolean> {
    loading.value = true
    error.value = null

    try {
      const data = await authApi.login(credentials)
      user.value = data.user
      return true
    } catch (err) {
      error.value = getApiErrorMessage(err, 'Не удалось войти.')
      return false
    } finally {
      loading.value = false
    }
  }

  async function logout(): Promise<void> {
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
