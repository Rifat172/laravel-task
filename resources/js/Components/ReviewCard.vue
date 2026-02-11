<script setup>
defineProps({
    review: Object,
})
</script>

<template>
    <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm hover:shadow-md transition">
        <div class="bg-gray-100/50 rounded-lg p-4">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="font-semibold text-lg text-gray-900">{{ review.author }}</p>
                    <p class="text-sm text-gray-600 mt-1">{{ review.date }}</p>
                </div>
                <div class="text-yellow-500 text-2xl">
                    {{ '★'.repeat(Math.floor(review.rating)) }}<span v-if="review.rating % 1 !== 0"
                        class="text-yellow-300">½</span>{{ '☆'.repeat(5 - Math.ceil(review.rating)) }}
                </div>
            </div>
            <p class="text-gray-700 leading-relaxed">{{ review.text }}</p>
        </div>
    </div>
</template>