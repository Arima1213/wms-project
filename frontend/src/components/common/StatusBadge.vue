<template>
  <div class="flex items-center gap-2">
    <span :class="dotClass" class="w-2 h-2 rounded-full flex-shrink-0"></span>
    <span :class="[textClass, sizeClass]" class="font-medium capitalize">{{ label || status }}</span>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: { type: String, required: true },
  label: { type: String, default: '' },
  size: { type: String, default: 'sm' },
  colorMap: {
    type: Object,
    default: () => ({
      draft: 'gray',
      pending: 'yellow',
      active: 'green',
      inactive: 'gray',
      received: 'green',
      partial: 'blue',
      picking: 'blue',
      packed: 'indigo',
      shipped: 'purple',
      delivered: 'green',
      approved: 'green',
      rejected: 'red',
      cancelled: 'red',
      completed: 'green',
      in_progress: 'blue',
      in_transit: 'indigo',
      submitted: 'yellow',
      // defaults
      true: 'green',
      false: 'gray',
    })
  },
})

const color = computed(() => props.colorMap[props.status] || 'gray')

const dotClass = computed(() => ({
  gray: 'bg-gray-400',
  yellow: 'bg-amber-400',
  green: 'bg-emerald-400',
  blue: 'bg-blue-400',
  indigo: 'bg-indigo-400',
  purple: 'bg-purple-400',
  red: 'bg-red-400',
}[color.value] || 'bg-gray-400'))

const textClass = computed(() => ({
  gray: 'text-gray-600',
  yellow: 'text-amber-700',
  green: 'text-emerald-700',
  blue: 'text-blue-700',
  indigo: 'text-indigo-700',
  purple: 'text-purple-700',
  red: 'text-red-700',
}[color.value] || 'text-gray-600'))

const sizeClass = computed(() => ({
  xs: 'text-xs',
  sm: 'text-xs',
  md: 'text-sm',
}[props.size] || 'text-xs'))
</script>
