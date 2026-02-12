<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Sidebar from '@/Components/Sidebar.vue'
import RatingCard from '@/Components/RatingCard.vue'
import ReviewCard from '@/Components/ReviewCard.vue'
import { Link } from '@inertiajs/vue3'

import { ref, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
  initial_data: Object | null,
  yandex_url: String | null,
})

const reviews = ref(props.initial_data?.reviews ?? [])
const rating = ref(props.initial_data?.rating ?? [])
const reviewsCount = ref(props.initial_data?.reviews_count ?? null)
const ratingsCount = ref(props.initial_data?.ratings_count ?? null)
const currentPage = ref(props.initial_data?.current_page ?? 1)
const hasMore = ref(props.initial_data?.has_more ?? false)
const loading = ref(false)
const error = ref(null)

const loadMoreTrigger = ref(null)
let observer = null

const loadMore = async () => {
  if (!hasMore.value || loading.value) return

  loading.value = true
  error.value = null

  try {
    const response = await axios.get(route('reviews.load-more'), {
      params: {
        url: props.yandex_url,
        page: Number(currentPage.value) + 1
      },
    })

    const data = response.data

    reviews.value = [...reviews.value, ...data.reviews]
    currentPage.value = data.current_page
    hasMore.value = data.has_more
  } catch (err) {
    error.value = 'Не удалось загрузить отзывы'
    console.error(err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (!hasMore.value) return

  observer = new IntersectionObserver(
    entries => {
      if (entries[0].isIntersecting) {
        loadMore()
      }
    },
    { threshold: 0.1 }
  )

  if (loadMoreTrigger.value) {
    observer.observe(loadMoreTrigger.value)
  }
})

onUnmounted(() => {
  if (observer) observer.disconnect()
})
</script>

<template>
  <AuthenticatedLayout title="Отзывы">
    <div class="flex min-h-screen bg-white">
      <!-- Sidebar -->
      <Sidebar />

      <!-- Основной контент -->
      <main class="flex-1 p-4 md:pt-6 md:pl-6 md:ml-72 bg-white">
        <div class="w-full">

          <a :href="yandex_url" target="_blank"
            class="flex items-center gap-2 border border-gray-200 rounded-xl w-fit px-3 py-1 mb-4 hover:bg-gray-50 transition-colors">
            <svg width="16" height="16" viewBox="0 0 13 16" fill="none" xmlns="http://www.w3.org">
              <path
                d="M6.0209 0C2.69556 0 0 2.69556 0 6.0209C0 7.68297 0.673438 9.1879 1.76262 10.2774C2.8521 11.3675 5.41881 12.9449 5.56934 14.6007C5.5919 14.849 5.77164 15.0523 6.0209 15.0523C6.27017 15.0523 6.4499 14.849 6.47247 14.6007C6.62299 12.9449 9.1897 11.3675 10.2792 10.2774C11.3684 9.1879 12.0418 7.68297 12.0418 6.0209C12.0418 2.69556 9.34625 0 6.0209 0Z"
                fill="#FF4433" />
              <circle cx="6" cy="6" r="2" fill="white" />
            </svg>

            <h2 class="text-sm text-gray-900">Яндекс Карты</h2>
          </a>
          <div v-if="!initial_data && !yandex_url" class="text-center py-20 text-gray-600 bg-white rounded-2xl shadow">
            <p class="text-xl mb-6">Интеграция с Яндекс Картами не настроена</p>
            <Link :href="route('settings.edit')"
              class="inline-flex items-center px-8 py-4 bg-blue-600 text-white font-medium rounded-xl hover:bg-blue-700">
              Перейти в настройки →
            </Link>
          </div>

          <div v-else-if="!initial_data" class="text-center py-20 text-gray-500 bg-white rounded-2xl shadow">
            Отзывы загружаются...
          </div>

          <div v-else class="flex flex-col lg:flex-row gap-8">
            <!-- Список отзывов -->
            <div class="flex-1 space-y-6">
              <ReviewCard v-for="(review, index) in reviews" :key="index" :review="review" />
            </div>

            <div v-if="hasMore || loading" ref="loadMoreTrigger" class="py-8 text-center">
              <div v-if="loading"
                class="animate-spin inline-block w-8 h-8 border-4 border-blue-500 rounded-full border-t-transparent">
              </div>
              <div v-else-if="error" class="text-red-600">{{ error }}</div>
            </div>

            <div v-if="!hasMore && reviews.length > 0" class="text-center py-6 text-gray-500">
              Все отзывы загружены
            </div>

            <!-- Карточка рейтинга справа -->
            <aside class="w-full lg:w-80 flex-shrink-0">
              <RatingCard :rating="rating" :reviews-count="reviewsCount" :ratings-count="ratingsCount" />
            </aside>
          </div>
        </div>
      </main>
    </div>
  </AuthenticatedLayout>
</template>