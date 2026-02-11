<script setup>
import { useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'

const props = defineProps({
  user: Object,
  success: String,
})

const form = useForm({
  yandex_maps_url: props.user.yandex_maps_url || '',
})

const submit = () => {
  form.post(route('settings.update'), {
    onSuccess: () => form.reset(),
  })
}
</script>

<template>
  <AuthenticatedLayout title="Настройка">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            <h2 class="text-2xl font-bold mb-6">Подключить Яндекс</h2>

            <p class="mb-4">
              Укажите ссылку на карточку организации в Яндекс.Картах, пример:
              <br />
              <code>https://yandex.ru/maps/org/samoye_populyarnoye_kafe/1010501395/reviews/</code>
            </p>

            <form @submit.prevent="submit">
              <div class="mb-4">
                <InputLabel for="yandex_maps_url" value="Ссылка на Яндекс" />
                <TextInput id="yandex_maps_url" type="url" class="mt-1 block w-full" v-model="form.yandex_maps_url"
                  placeholder="https://yandex.ru/maps/org/..." />
                <p v-if="form.errors.yandex_maps_url" class="text-red-600 text-sm mt-1">
                  {{ form.errors.yandex_maps_url }}
                </p>
              </div>

              <PrimaryButton type="submit" :disabled="form.processing">
                Сохранить
              </PrimaryButton>
            </form>

            <p v-if="success" class="mt-4 text-green-600">
              {{ success }}
            </p>
            <p v-if="user.yandex_org_id" class="mt-2 text-sm text-gray-700">
              Извлечённый ID: <strong>{{ user.yandex_org_id }}</strong>
            </p>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>