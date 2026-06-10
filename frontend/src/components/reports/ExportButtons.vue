<template>
  <div class="flex gap-2">
    <button @click="$emit('export', { type: params.type, params: { ...params, format: 'xlsx' } })" class="btn btn-sm btn-outline">
      <DocumentArrowDownIcon class="w-4 h-4" />
      Excel
    </button>
    <button @click="$emit('export', { type: params.type, params: { ...params, format: 'pdf' } })" class="btn btn-sm btn-outline">
      <DocumentArrowDownIcon class="w-4 h-4" />
      PDF
    </button>
  </div>
</template>

<script setup>
import { DocumentArrowDownIcon } from '@heroicons/vue/24/outline'

defineProps({
  params: { type: Object, default: () => ({}) },
})

defineEmits(['export'])
</script>
