<template>
  <section>
    <div class="card">
      <h1>Настройки</h1>
      <p class="muted">
        Укажите ссылку на карточку организации в Яндекс.Картах.
      </p>

      <form @submit.prevent="onSubmitSettings">
        <label class="field">
          <span>Ссылка на организацию</span>
          <input
            v-model="yandexUrl"
            type="url"
            placeholder="https://yandex.ru/maps/org/... или https://yandex.com/maps/org/..."
            required
            :disabled="settingsLoading"
          />
        </label>

        <p v-if="settingsMessage" class="muted">{{ settingsMessage }}</p>
        <p v-if="settingsError" class="error">{{ settingsError }}</p>
        <p v-if="settingsLoadError" class="error">{{ settingsLoadError }}</p>

        <button class="btn" type="submit" :disabled="settingsSaving">
          {{ settingsSaving ? 'Сохранение...' : 'Сохранить' }}
        </button>
      </form>
    </div>

    <div class="card reviews-section">
      <h2>Отзывы организации</h2>

      <div v-if="reviewsLoading" class="muted">Загрузка...</div>

      <p v-else-if="reviewsError" class="error">{{ reviewsError }}</p>

      <template v-else-if="organization">
        <div class="stats">
          <div>
            <div class="stats__label">Название</div>
            <div>{{ organization.name || '—' }}</div>
          </div>
          <div>
            <div class="stats__label">Средний рейтинг</div>
            <div>{{ organization.avg_rating ?? '—' }}</div>
          </div>
          <div>
            <div class="stats__label">Оценок</div>
            <div>{{ organization.ratings_count ?? '—' }}</div>
          </div>
          <div>
            <div class="stats__label">Отзывов</div>
            <div>{{ organization.reviews_count ?? '—' }}</div>
          </div>
        </div>

        <p v-if="!reviews.length" class="muted reviews-empty">
          Отзывов пока нет.
        </p>

        <div v-else class="reviews">
          <article v-for="review in reviews" :key="review.id" class="card review">
            <div class="review__meta">
              <strong>{{ review.author || 'Аноним' }}</strong>
              <span class="muted">{{ formatDate(review.reviewed_at) }}</span>
              <span>★ {{ review.rating ?? '—' }}</span>
            </div>
            <p>{{ review.text || 'Без текста' }}</p>
          </article>
        </div>

        <div v-if="pagination.total > 0" class="pagination">
          <button
            class="btn btn-secondary"
            type="button"
            :disabled="pagination.current_page <= 1 || reviewsLoading"
            @click="loadPage(pagination.current_page - 1)"
          >
            Назад
          </button>
          <span class="muted">
            {{ paginationRange }} · страница {{ pagination.current_page }} из
            {{ pagination.last_page }}
          </span>
          <button
            class="btn btn-secondary"
            type="button"
            :disabled="pagination.current_page >= pagination.last_page || reviewsLoading"
            @click="loadPage(pagination.current_page + 1)"
          >
            Вперёд
          </button>
        </div>
      </template>

      <p v-else class="muted">
        Организация ещё не настроена. Укажите ссылку выше и сохраните настройки.
      </p>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { fetchReviews } from '@/api/organization'
import { fetchSettings, updateSettings } from '@/api/settings'

const PER_PAGE = 10

const yandexUrl = ref('')
const settingsLoading = ref(false)
const settingsSaving = ref(false)
const settingsMessage = ref('')
const settingsError = ref('')
const settingsLoadError = ref('')

const reviewsLoading = ref(false)
const reviewsError = ref('')
const organization = ref(null)
const reviews = ref([])
const pagination = reactive({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: PER_PAGE,
})

const paginationRange = computed(() => {
  if (!pagination.total) {
    return '0 из 0'
  }

  const from = (pagination.current_page - 1) * pagination.per_page + 1
  const to = Math.min(pagination.current_page * pagination.per_page, pagination.total)
  return `${from}–${to} из ${pagination.total}`
})

function formatDate(value) {
  if (!value) return '—'
  return new Date(value).toLocaleDateString('ru-RU')
}

async function loadSettings() {
  settingsLoading.value = true
  settingsLoadError.value = ''

  try {
    const settingsOrganization = await fetchSettings()
    yandexUrl.value = settingsOrganization?.yandex_url || ''
  } catch (err) {
    settingsLoadError.value =
      err.response?.data?.message || 'Не удалось загрузить настройки.'
  } finally {
    settingsLoading.value = false
  }
}

async function onSubmitSettings() {
  settingsSaving.value = true
  settingsMessage.value = ''
  settingsError.value = ''

  try {
    const data = await updateSettings({ yandex_url: yandexUrl.value })
    settingsMessage.value = `${data.message} Загружено отзывов: ${data.reviews_synced ?? 0}.`
    await loadPage(1)
  } catch (err) {
    settingsError.value =
      err.response?.data?.message || 'Не удалось сохранить настройки.'
  } finally {
    settingsSaving.value = false
  }
}

async function loadPage(page = 1) {
  reviewsLoading.value = true
  reviewsError.value = ''

  try {
    const data = await fetchReviews(page, PER_PAGE)
    organization.value = data.organization
    reviews.value = data.reviews.data
    pagination.current_page = data.reviews.current_page
    pagination.last_page = data.reviews.last_page
    pagination.total = data.reviews.total
    pagination.per_page = data.reviews.per_page
  } catch (err) {
    if (err.response?.status === 404) {
      organization.value = null
      reviews.value = []
      pagination.current_page = 1
      pagination.last_page = 1
      pagination.total = 0
    } else {
      reviewsError.value =
        err.response?.data?.message || 'Не удалось загрузить отзывы.'
    }
  } finally {
    reviewsLoading.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadSettings(), loadPage()])
})
</script>

<style scoped>
.reviews-section {
  margin-top: 1rem;
}

.reviews-section h2 {
  margin: 0 0 0.5rem;
  font-size: 1.25rem;
}

.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 1rem;
  margin-top: 1rem;
}

.stats__label {
  color: #6b7280;
  font-size: 0.875rem;
}

.reviews-empty {
  margin-top: 1rem;
}

.reviews {
  display: grid;
  gap: 1rem;
  margin-top: 1rem;
}

.review__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-bottom: 0.5rem;
}

.pagination {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-top: 1rem;
  flex-wrap: wrap;
}
</style>
