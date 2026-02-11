<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Sidebar from '@/Components/Sidebar.vue' // или '@/Layouts/Sidebar.vue'
import RatingCard from '@/Components/RatingCard.vue'
import ReviewCard from '@/Components/ReviewCard.vue'

const props = defineProps({
  org_id: [String, Number, null],
  reviews_data: Object | null,
  yandex_url: String | null, 
})
console.debug(props);
</script>

<template>
  <AuthenticatedLayout title="Отзывы">
    <div class="flex min-h-screen bg-white">
      <!-- Sidebar -->
      <Sidebar />

      <!-- Основной контент -->
      <main class="flex-1 p-4 md:pt-6 md:pl-6 md:ml-72 bg-white">
        <div class="w-full"> 

          <a :href="yandex_url" target="_blank" class="flex items-center gap-2 border border-gray-200 rounded-xl w-fit px-3 py-1 mb-4 hover:bg-gray-50 transition-colors">
            <svg width="16" height="16" viewBox="0 0 13 16" fill="none" xmlns="http://www.w3.org">
              <path
                d="M6.0209 0C2.69556 0 0 2.69556 0 6.0209C0 7.68297 0.673438 9.1879 1.76262 10.2774C2.8521 11.3675 5.41881 12.9449 5.56934 14.6007C5.5919 14.849 5.77164 15.0523 6.0209 15.0523C6.27017 15.0523 6.4499 14.849 6.47247 14.6007C6.62299 12.9449 9.1897 11.3675 10.2792 10.2774C11.3684 9.1879 12.0418 7.68297 12.0418 6.0209C12.0418 2.69556 9.34625 0 6.0209 0Z"
                fill="#FF4433" />
              <circle cx="6" cy="6" r="2" fill="white" />
            </svg>

            <h2 class="text-sm text-gray-900">Яндекс Карты</h2>
          </a>

          <div v-if="!org_id" class="text-center py-20 text-gray-600 bg-white rounded-2xl shadow">
            <p class="text-xl mb-6">Интеграция с Яндекс Картами не настроена</p>
            <Link :href="route('settings.edit')"
              class="inline-flex items-center px-8 py-4 bg-blue-600 text-white font-medium rounded-xl hover:bg-blue-700">
            Перейти в настройки →
            </Link>
          </div>

          <div v-else-if="!reviews_data" class="text-center py-20 text-gray-500 bg-white rounded-2xl shadow">
            Отзывы загружаются...
          </div>

          <div v-else class="flex flex-col lg:flex-row gap-8">
            <!-- Список отзывов -->
            <div class="flex-1 space-y-6">
              <ReviewCard v-for="(review, index) in reviews_data.reviews" :key="index" :review="review" />
            </div>

            <!-- Карточка рейтинга справа -->
            <aside class="w-full lg:w-80 flex-shrink-0">
              <RatingCard :rating="reviews_data.rating" :reviews-count="reviews_data.reviews_count"
                :ratings-count="reviews_data.ratings_count" :org-id="org_id" />
            </aside>
          </div>
        </div>
      </main>
    </div>
  </AuthenticatedLayout>
</template>