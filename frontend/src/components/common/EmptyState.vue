<template>
  <div class="text-center py-12">
    <component :is="icon" class="w-12 h-12 mx-auto text-gray-300 mb-4" />
    <h3 class="text-lg font-medium text-gray-600 mb-1">{{ title }}</h3>
    <p class="text-sm text-gray-400 mb-6 max-w-sm mx-auto">{{ description }}</p>
    <slot name="action">
      <button v-if="actionLabel" @click="$emit('action')" class="btn btn-primary">
        {{ actionLabel }}
      </button>
    </slot>
  </div>
</template>

<script setup>
import { InboxIcon } from '@heroicons/vue/24/outline'

defineProps({
  icon: { type: [Object, Function], default: () => InboxIcon },
  title: { type: String, default: 'Tidak ada data' },
  description: { type: String, default: 'Data yang Anda cari tidak ditemukan.' },
  actionLabel: { type: String, default: '' },
})

defineEmits(['action'])
</script>
