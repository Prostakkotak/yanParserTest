<template>
  <section>
    <div class="card">
      <h1>Отзывы организации</h1>

      <div v-if="loading" class="muted">Загрузка...</div>

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
      </template>

      <p v-else class="muted">
        Организация ещё не настроена. Перейдите в раздел «Настройки».
      </p>
    </div>

    <div v-if="reviews.length" class="reviews">
      <article v-for="review in reviews" :key="review.id" class="card review">
        <div class="review__meta">
          <strong>{{ review.author || 'Аноним' }}</strong>
          <span class="muted">{{ formatDate(review.reviewed_at) }}</span>
          <span>★ {{ review.rating ?? '—' }}</span>
        </div>
        <p>{{ review.text || 'Без текста' }}</p>
      </article>
    </div>

    <div v-if="pagination.last_page > 1" class="pagination">
      <button
        class="btn btn-secondary"
        type="button"
        :disabled="pagination.current_page <= 1 || loading"
        @click="loadPage(pagination.current_page - 1)"
      >
        Назад
      </button>
      <span class="muted">
        Страница {{ pagination.current_page }} из {{ pagination.last_page }}
      </span>
      <button
        class="btn btn-secondary"
        type="button"
        :disabled="pagination.current_page >= pagination.last_page || loading"
        @click="loadPage(pagination.current_page + 1)"
      >
        Вперёд
      </button>
    </div>
  </section>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { fetchReviews } from '@/api/organization'

const loading = ref(false)
const organization = ref(null)
const reviews = ref([])
const pagination = reactive({
  current_page: 1,
  last_page: 1,
})

function formatDate(value) {
  if (!value) return '—'
  return new Date(value).toLocaleDateString('ru-RU')
}

async function loadPage(page = 1) {
  loading.value = true

  try {
    const data = await fetchReviews(page)
    organization.value = data.organization
    reviews.value = data.reviews.data
    pagination.current_page = data.reviews.current_page
    pagination.last_page = data.reviews.last_page
  } catch (err) {
    if (err.response?.status === 404) {
      organization.value = null
      reviews.value = []
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => loadPage())
</script>

<style scoped>
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
}
</style>
