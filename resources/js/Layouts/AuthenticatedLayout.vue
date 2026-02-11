<script setup>
import { ref } from 'vue'
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'
import { Link, router } from '@inertiajs/vue3'
import LogoArrow from '@/Components/LogoArrow.vue' // ← твой компонент с SVG

const showingNavigationDropdown = ref(false)

// Функция выхода при клике на логотип
const logout = () => {
  router.post(route('logout'), {}, {
    onFinish: () => router.visit(route('home')),
  })
}
</script>

<template>
  <div class="min-h-screen bg-white">
    <!-- Верхняя панель (header) — как в Figma -->
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-10">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="flex justify-end items-center h-16">
          <!-- Логотип в правом верхнем углу (клик → выход) -->
          <div class="absolute top-3 right-4 md:right-6">
            <LogoArrow @click="logout" />
          </div>
        </div>
      </div>
    </nav>

    <!-- Основной контент страницы -->
    <main >
      <slot />
    </main>
  </div>
</template>