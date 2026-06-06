<template>
  <div class="flex items-center gap-2 p-1 text-sm text-gray-500">
    <template v-for="(crumb, idx) in crumbs" :key="idx">
      <ChevronRightIcon v-if="idx > 0" class="w-3 h-3 text-gray-300 flex-shrink-0" />
      <router-link
        v-if="crumb.to && idx < crumbs.length - 1"
        :to="crumb.to"
        class="hover:text-blue-600 transition-colors"
      >
        {{ crumb.label }}
      </router-link>
      <span v-else class="text-gray-800 font-medium">{{ crumb.label }}</span>
    </template>
  </div>
</template>

<script setup>
import { ChevronRightIcon } from '@heroicons/vue/24/outline'

defineProps({
  crumbs: {
    type: Array,
    default: () => [],
    // e.g. [{ label: 'Dashboard', to: '/' }, { label: 'Gudang' }]
  },
})
</script>
