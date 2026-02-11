<script setup>
import { computed } from 'vue'

const props = defineProps({
    rating: [Array, Number, null],
    reviewsCount: [Number, String, null],
    ratingsCount: [Number, String, null],
    orgId: [String, Number, null],
})

const displayRating = computed(() => {
    if (Array.isArray(props.rating)) {
        return parseFloat(props.rating.join('.')) || null
    }
    return props.rating || null
})

const fullStars = computed(() => Math.floor(displayRating.value ?? 0))
const hasHalfStar = computed(() => (displayRating.value ?? 0) % 1 >= 0.5)
const emptyStars = computed(() => 5 - fullStars.value - (hasHalfStar.value ? 1 : 0))
</script>

<template>
    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm w-full lg:w-80 flex-shrink-0">
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="text-4xl md:text-4xl font-extrabold text-gray-900">
                        {{ displayRating !== null ? displayRating.toFixed(1) : '?' }}
                    </div>
                    <div class="flex items-center gap-0.5 text-yellow-400 text-3xl md:text-4xl">
                        <span v-for="i in fullStars" :key="'full-' + i">★</span>
                        <span v-if="hasHalfStar">½</span>
                        <span v-for="i in emptyStars" :key="'empty-' + i" class="text-gray-300 opacity-50">★</span>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200 opacity-50 w-full"></div>

            <div class="flex flex-col">
                <p class="text-sm text-gray-600 whitespace-nowrap">
                    Всего отзывов: {{ reviewsCount || '?' }}
                </p>
            </div>
        </div>
    </div>
</template>