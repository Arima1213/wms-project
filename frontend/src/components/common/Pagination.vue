<template>
  <div class="flex items-center gap-4 text-sm">
    <span class="text-gray-500">
      {{ from }}–{{ to }} dari {{ total }}
    </span>
    <div class="flex items-center gap-1">
      <button
        @click="goTo(1)"
        :disabled="currentPage <= 1"
        class="px-2 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
      >
        <ChevronDoubleLeftIcon class="w-4 h-4" />
      </button>
      <button
        @click="goTo(currentPage - 1)"
        :disabled="currentPage <= 1"
        class="px-2 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
      >
        <ChevronLeftIcon class="w-4 h-4" />
      </button>

      <template v-for="page in visiblePages" :key="page">
        <span v-if="page === '...'" class="px-2 py-1 text-gray-400">...</span>
        <button
          v-else
          @click="goTo(page)"
          :class="page === currentPage
            ? 'bg-blue-600 text-white border-blue-600'
            : 'border-gray-200 text-gray-700 hover:bg-gray-50'"
          class="px-3 py-1.5 rounded-lg border font-medium transition-colors min-w-[36px]"
        >
          {{ page }}
        </button>
      </template>

      <button
        @click="goTo(currentPage + 1)"
        :disabled="currentPage >= lastPage"
        class="px-2 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
      >
        <ChevronRightIcon class="w-4 h-4" />
      </button>
      <button
        @click="goTo(lastPage)"
        :disabled="currentPage >= lastPage"
        class="px-2 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
      >
        <ChevronDoubleRightIcon class="w-4 h-4" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import {
  ChevronLeftIcon,
  ChevronRightIcon,
  ChevronDoubleLeftIcon,
  ChevronDoubleRightIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  currentPage: { type: Number, default: 1 },
  lastPage: { type: Number, default: 1 },
  total: { type: Number, default: 0 },
  from: { type: Number, default: 0 },
  to: { type: Number, default: 0 },
})

const emit = defineEmits(['page-change'])

const visiblePages = computed(() => {
  const pages = []
  const total = props.lastPage
  const current = props.currentPage

  if (total <= 7) {
    for (let i = 1; i <= total; i++) pages.push(i)
  } else {
    pages.push(1)
    if (current > 3) pages.push('...')
    for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i++) {
      pages.push(i)
    }
    if (current < total - 2) pages.push('...')
    pages.push(total)
  }

  return pages
})

function goTo(page) {
  if (page >= 1 && page <= props.lastPage && page !== props.currentPage) {
    emit('page-change', page)
  }
}
</script>
