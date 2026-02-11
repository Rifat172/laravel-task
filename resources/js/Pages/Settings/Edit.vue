<script setup>
import { useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Sidebar from '@/Components/Sidebar.vue'
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
    <div class="flex min-h-screen bg-white">
      <Sidebar />

      <main class="flex-1 p-6 md:p-10 md:ml-72">
        <div class="w-full">
          <div class="p-0">
            <h2 class="text-2xl font-bold mb-4 text-gray-900">Подключить Яндекс</h2>

            <p class="mb-4 text-gray-600 text-sm">
              Укажите ссылку на Яндекс, пример
              <span
                class="text-gray-400 ml-1">https://yandex.ru/maps/org/samoye_populyarnoye_kafe/1010501395/reviews/</span>
            </p>

            <form @submit.prevent="submit" class="space-y-4">
              <div>
                <TextInput id="yandex_maps_url" type="url" class="block w-full border-gray-300 rounded-lg"
                  v-model="form.yandex_maps_url" />
              </div>

              <div class="flex justify-start">
                <PrimaryButton class="bg-[#4895ef] hover:bg-[#3f87e6] border-none shadow-none"
                  style="background-color: #4895ef !important;">
                  Сохранить
                </PrimaryButton>
              </div>
            </form>
          </div>
        </div>
      </main>

    </div>
  </AuthenticatedLayout>
</template>