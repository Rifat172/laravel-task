<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  org_id: [String, Number, null],
  yandex_url: String,
})
</script>

<template>
  <AuthenticatedLayout title="Отзывы">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
          <h2 class="text-2xl font-bold mb-6">Отзывы с Яндекс Карт</h2>

          <div v-if="!org_id" class="text-center py-12 text-gray-600">
            <p class="mb-4">Интеграция с Яндекс Картами не настроена</p>
            <Link :href="route('settings.edit')" class="text-blue-600 hover:underline">
              Перейти в настройки →
            </Link>
          </div>

          <div v-else class="space-y-8">
            <!-- Рейтинг и количество — можно вытащить из виджета или оставить статично -->
            <div class="flex justify-between items-center bg-gray-50 p-6 rounded-lg">
              <div>
                <p class="text-lg font-semibold">Яндекс Карты</p>
                <p class="text-sm text-gray-500">Всего отзывов: ?</p>
              </div>
              <div class="text-right">
                <div class="text-4xl font-bold text-yellow-500">? ★★★★★</div>
              </div>
            </div>

            <!-- Вставляем скопированный виджет через v-html -->
            <div v-html="yandexReviewsWidget" class="w-full max-w-[800px] mx-auto"></div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script>
export default {
  computed: {
    yandexReviewsWidget() {
      // Твой скопированный код — вставляем как строку
      // Можно сделать динамическим по props.org_id
      const orgId = this.org_id || '39925247100'; // fallback на твой пример
      return `
        <div style="width:100%;height:800px;overflow:hidden;position:relative;">
          <iframe style="width:100%;height:100%;border:1px solid #e6e6e6;border-radius:8px;box-sizing:border-box" 
            src="https://yandex.ru/maps-reviews-widget/${orgId}?comments">
          </iframe>
          <a href="https://yandex.ru/maps/org/friendly/${orgId}/" target="_blank" 
            style="box-sizing:border-box;text-decoration:none;color:#b3b3b3;font-size:10px;font-family:YS Text,sans-serif;padding:0 20px;position:absolute;bottom:8px;width:100%;text-align:center;left:0;overflow:hidden;text-overflow:ellipsis;display:block;max-height:14px;white-space:nowrap;padding:0 16px;box-sizing:border-box">
            Friendly на карте Челябинска — Яндекс Карты
          </a>
        </div>
      `;
    }
  }
}
</script>