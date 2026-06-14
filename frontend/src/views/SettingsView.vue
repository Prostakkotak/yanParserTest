<template>
  <section class="card">
    <h1>Настройки</h1>
    <p class="muted">
      Укажите ссылку на карточку организации в Яндекс.Картах.
    </p>

    <form @submit.prevent="onSubmit">
      <label class="field">
        <span>Ссылка на организацию</span>
        <input
          v-model="yandexUrl"
          type="url"
          placeholder="https://yandex.ru/maps/org/..."
          required
        />
      </label>

      <p v-if="message" class="muted">{{ message }}</p>
      <p v-if="error" class="error">{{ error }}</p>

      <button class="btn" type="submit" :disabled="loading">
        {{ loading ? 'Сохранение...' : 'Сохранить' }}
      </button>
    </form>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { fetchSettings, updateSettings } from '@/api/settings'

const yandexUrl = ref('')
const loading = ref(false)
const message = ref('')
const error = ref('')

onMounted(async () => {
  try {
    const organization = await fetchSettings()
    yandexUrl.value = organization?.yandex_url || ''
  } catch {
    // settings not configured yet
  }
})

async function onSubmit() {
  loading.value = true
  message.value = ''
  error.value = ''

  try {
    const data = await updateSettings({ yandex_url: yandexUrl.value })
    message.value = data.message
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось сохранить настройки.'
  } finally {
    loading.value = false
  }
}
</script>
