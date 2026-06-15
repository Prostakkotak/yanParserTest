<template>
  <section class="space-y-3">
    <UCard>
      <h1 class="mb-1.5 text-xl font-semibold">Настройки</h1>
      <p class="mb-4 text-sm text-gray-500">
        Укажите ссылку на карточку организации в Яндекс.Картах.
      </p>

      <form @submit.prevent="onSubmitSettings">
        <UField label="Ссылка на организацию">
          <UInput
            v-model="yandexUrl"
            type="url"
            placeholder="https://yandex.ru/maps/org/... или https://yandex.com/maps/org/..."
            required
            :disabled="settingsLoading"
          />
        </UField>

        <p v-if="settingsMessage" class="mb-2 text-sm text-gray-500">{{ settingsMessage }}</p>
        <p v-if="settingsError" class="mb-2 text-sm text-red-600">{{ settingsError }}</p>
        <p v-if="settingsLoadError" class="mb-2 text-sm text-red-600">{{ settingsLoadError }}</p>

        <div class="flex flex-wrap gap-2">
          <UButton
            type="submit"
            :loading="settingsSaving"
            :disabled="!canSave || settingsSaving"
          >
            {{ settingsSaving ? 'Сохранение...' : 'Сохранить' }}
          </UButton>
          <UButton
            type="button"
            variant="secondary"
            :loading="settingsReparsing"
            :disabled="!canReparse || settingsReparsing"
            @click="onReparse"
          >
            {{ settingsReparsing ? 'Запуск...' : 'Перепарсить' }}
          </UButton>
        </div>
      </form>
    </UCard>

    <UCard>
      <h2 class="mb-1.5 text-lg font-semibold">Отзывы организации</h2>

      <p v-if="reviewsError" class="text-sm text-red-600">{{ reviewsError }}</p>

      <template v-else-if="showStatsSection">
        <div class="mt-2.5 grid grid-cols-[repeat(auto-fit,minmax(140px,1fr))] gap-x-3 gap-y-2">
          <div v-for="item in statItems" :key="item.label">
            <div class="mb-1 text-[0.8125rem] text-gray-500">{{ item.label }}</div>
            <USkeleton v-if="item.loading" :wide="item.wide" />
            <div v-else>{{ item.value }}</div>
          </div>
        </div>

        <template v-if="organization">
          <div
            v-if="isParsing"
            class="mt-3 rounded-md border border-dashed border-gray-300 bg-gray-50 p-3"
          >
            <p class="text-sm">Загружаем отзывы с Яндекс.Карт…</p>
            <p class="mt-1 text-sm text-gray-500">
              Это может занять до минуты. Страница обновится автоматически.
            </p>
          </div>

          <p v-else-if="syncFailed" class="mt-2.5 text-sm text-red-600">
            {{ organization.sync_error || 'Не удалось загрузить отзывы.' }}
          </p>

          <p v-else-if="!reviews.length" class="mt-2.5 text-sm text-gray-500">
            Отзывов пока нет.
          </p>

          <div v-else class="mt-2.5 grid gap-2">
            <article
              v-for="review in reviews"
              :key="review.id"
              class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2.5"
            >
              <div class="mb-1.5 flex flex-wrap gap-2 text-sm">
                <strong>{{ review.author || 'Аноним' }}</strong>
                <span class="text-gray-500">{{ formatDate(review.reviewed_at) }}</span>
                <span>★ {{ review.rating ?? '—' }}</span>
              </div>
              <p class="text-[0.9375rem]">{{ review.text || 'Без текста' }}</p>
            </article>
          </div>

          <div
            v-if="!isParsing && pagination.total > 0"
            class="mt-3 flex flex-wrap items-center gap-2.5"
          >
            <UButton
              variant="secondary"
              :disabled="pagination.current_page <= 1 || reviewsLoading"
              @click="loadPage(pagination.current_page - 1)"
            >
              Назад
            </UButton>
            <span class="text-sm text-gray-500">
              {{ paginationRange }} · страница {{ pagination.current_page }} из
              {{ pagination.last_page }}
            </span>
            <UButton
              variant="secondary"
              :disabled="pagination.current_page >= pagination.last_page || reviewsLoading"
              @click="loadPage(pagination.current_page + 1)"
            >
              Вперёд
            </UButton>
          </div>
        </template>
      </template>

      <p v-else class="text-sm text-gray-500">
        Организация ещё не настроена. Укажите ссылку выше и сохраните настройки.
      </p>
    </UCard>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import { fetchReviews } from '@/api/organization'
import { fetchSettings, reparseSettings, updateSettings } from '@/api/settings'
import UButton from '@/components/ui/UButton.vue'
import UCard from '@/components/ui/UCard.vue'
import UField from '@/components/ui/UField.vue'
import UInput from '@/components/ui/UInput.vue'
import USkeleton from '@/components/ui/USkeleton.vue'
import type { Organization, Review, SyncStatus } from '@/types/api'
import { getApiErrorMessage, getApiErrorStatus } from '@/utils/apiError'

const PER_PAGE = 50
const POLL_INTERVAL_MS = 3000

const SYNC_PENDING: SyncStatus = 'pending'
const SYNC_PROCESSING: SyncStatus = 'processing'
const SYNC_FAILED: SyncStatus = 'failed'

const STAT_FIELDS = [
  { label: 'Название', key: 'name' as const, wide: true },
  { label: 'Средний рейтинг', key: 'avg_rating' as const, wide: false },
  { label: 'Оценок', key: 'ratings_count' as const, wide: false },
  { label: 'Отзывов', key: 'reviews_count' as const, wide: false },
]

const yandexUrl = ref('')
const savedYandexUrl = ref('')
const settingsLoading = ref(false)
const settingsSaving = ref(false)
const settingsReparsing = ref(false)
const settingsMessage = ref('')
const settingsError = ref('')
const settingsLoadError = ref('')

const reviewsLoading = ref(false)
const reviewsError = ref('')
const organization = ref<Organization | null>(null)
const reviews = ref<Review[]>([])
const pagination = reactive({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: PER_PAGE,
})

let pollTimer: ReturnType<typeof setInterval> | null = null

const PARSING_STATUSES: SyncStatus[] = [SYNC_PENDING, SYNC_PROCESSING]

const isParsing = computed(() => {
  const status = organization.value?.sync_status
  return status !== undefined && PARSING_STATUSES.includes(status)
})

const syncFailed = computed(() => organization.value?.sync_status === SYNC_FAILED)

const isUrlSaved = computed(
  () =>
    savedYandexUrl.value !== '' &&
    yandexUrl.value.trim() === savedYandexUrl.value.trim(),
)

const canSave = computed(
  () => !settingsLoading.value && !isUrlSaved.value && yandexUrl.value.trim() !== '',
)

const canReparse = computed(
  () =>
    savedYandexUrl.value !== '' &&
    isUrlSaved.value &&
    !isParsing.value &&
    !settingsSaving.value,
)

const showStatsSection = computed(
  () => (reviewsLoading.value && !organization.value) || !!organization.value,
)

const statItems = computed(() => {
  const initialLoading = reviewsLoading.value && !organization.value

  return STAT_FIELDS.map(({ label, key, wide }) => {
    if (initialLoading) {
      return { label, loading: true, wide }
    }

    const raw = organization.value?.[key]
    const loading =
      isParsing.value && (raw === null || raw === undefined || raw === '')

    return {
      label,
      wide,
      loading,
      value: raw ?? '—',
    }
  })
})

const paginationRange = computed(() => {
  if (!pagination.total) {
    return '0 из 0'
  }

  const from = (pagination.current_page - 1) * pagination.per_page + 1
  const to = Math.min(pagination.current_page * pagination.per_page, pagination.total)
  return `${from}–${to} из ${pagination.total}`
})

watch(isParsing, (parsing, wasParsing) => {
  if (wasParsing && !parsing) {
    settingsMessage.value = ''
  }
})

function formatDate(value: string | null | undefined): string {
  if (!value) return '—'
  return new Date(value).toLocaleDateString('ru-RU')
}

function stopPolling(): void {
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
}

function startPolling(): void {
  stopPolling()
  pollTimer = setInterval(() => {
    loadPage(pagination.current_page, { silent: true })
  }, POLL_INTERVAL_MS)
}

function syncPolling(): void {
  if (isParsing.value) {
    startPolling()
  } else {
    stopPolling()
  }
}

function applySavedUrl(url: string): void {
  const normalized = url.trim()
  savedYandexUrl.value = normalized
  yandexUrl.value = normalized
}

async function loadSettings(): Promise<void> {
  settingsLoading.value = true
  settingsLoadError.value = ''

  try {
    const settingsOrganization = await fetchSettings()
    applySavedUrl(settingsOrganization?.yandex_url || '')
  } catch (err) {
    settingsLoadError.value = getApiErrorMessage(err, 'Не удалось загрузить настройки.')
  } finally {
    settingsLoading.value = false
  }
}

async function onSubmitSettings(): Promise<void> {
  if (!canSave.value) {
    return
  }

  settingsSaving.value = true
  settingsMessage.value = ''
  settingsError.value = ''

  try {
    const data = await updateSettings({ yandex_url: yandexUrl.value.trim() })
    settingsMessage.value = data.message
    applySavedUrl(data.organization.yandex_url || yandexUrl.value.trim())
    organization.value = {
      ...(organization.value || {}),
      ...data.organization,
    }
    syncPolling()
    await loadPage(1, { silent: true })
  } catch (err) {
    settingsError.value = getApiErrorMessage(err, 'Не удалось сохранить настройки.')
  } finally {
    settingsSaving.value = false
  }
}

async function onReparse(): Promise<void> {
  if (!canReparse.value) {
    return
  }

  settingsReparsing.value = true
  settingsMessage.value = ''
  settingsError.value = ''

  try {
    const data = await reparseSettings()
    settingsMessage.value = data.message
    organization.value = {
      ...(organization.value || {}),
      ...data.organization,
    }
    syncPolling()
    await loadPage(1, { silent: true })
  } catch (err) {
    settingsError.value = getApiErrorMessage(err, 'Не удалось запустить перепарсинг.')
  } finally {
    settingsReparsing.value = false
  }
}

async function loadPage(page = 1, { silent = false } = {}): Promise<void> {
  if (!silent) {
    reviewsLoading.value = true
  }
  reviewsError.value = ''

  try {
    const data = await fetchReviews(page, PER_PAGE)
    organization.value = data.organization
    reviews.value = isParsing.value ? [] : data.reviews.data
    pagination.current_page = data.reviews.current_page
    pagination.last_page = data.reviews.last_page
    pagination.total = isParsing.value ? 0 : data.reviews.total
    pagination.per_page = data.reviews.per_page
    syncPolling()
  } catch (err) {
    if (getApiErrorStatus(err) === 404) {
      organization.value = null
      reviews.value = []
      pagination.current_page = 1
      pagination.last_page = 1
      pagination.total = 0
      stopPolling()
    } else {
      reviewsError.value = getApiErrorMessage(err, 'Не удалось загрузить отзывы.')
    }
  } finally {
    if (!silent) {
      reviewsLoading.value = false
    }
  }
}

onMounted(async () => {
  await Promise.all([loadSettings(), loadPage()])
})

onUnmounted(() => {
  stopPolling()
})
</script>
