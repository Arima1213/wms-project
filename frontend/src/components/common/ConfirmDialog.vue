<template>
  <Modal v-model="isOpen" :title="title" size="sm" :persistent="true">
    <div class="text-center py-2">
      <div :class="iconBgClass" class="w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4">
        <component :is="iconComp" :class="iconColorClass" class="w-6 h-6" />
      </div>
      <p class="text-gray-600 text-sm">{{ message }}</p>
    </div>
    <template #footer>
      <div class="flex justify-end gap-3">
        <button @click="cancel" class="btn btn-outline">{{ cancelText }}</button>
        <button @click="confirm" :class="confirmBtnClass" class="btn">{{ confirmText }}</button>
      </div>
    </template>
  </Modal>
</template>

<script setup>
import { ref, computed } from 'vue'
import Modal from './Modal.vue'
import {
  ExclamationTriangleIcon,
  TrashIcon,
  QuestionMarkCircleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: 'Konfirmasi' },
  message: { type: String, default: 'Apakah Anda yakin?' },
  type: { type: String, default: 'warning' }, // warning, danger, info
  confirmText: { type: String, default: 'Ya, Lanjutkan' },
  cancelText: { type: String, default: 'Batal' },
})

const emit = defineEmits(['update:modelValue', 'confirm', 'cancel'])

const isOpen = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

const iconComp = computed(() => ({
  warning: ExclamationTriangleIcon,
  danger: TrashIcon,
  info: QuestionMarkCircleIcon,
}[props.type] || ExclamationTriangleIcon))

const iconBgClass = computed(() => ({
  warning: 'bg-amber-100',
  danger: 'bg-red-100',
  info: 'bg-blue-100',
}[props.type] || 'bg-amber-100'))

const iconColorClass = computed(() => ({
  warning: 'text-amber-600',
  danger: 'text-red-600',
  info: 'text-blue-600',
}[props.type] || 'text-amber-600'))

const confirmBtnClass = computed(() => ({
  warning: 'btn-primary',
  danger: 'btn-danger',
  info: 'btn-primary',
}[props.type] || 'btn-primary'))

function confirm() {
  emit('confirm')
  isOpen.value = false
}

function cancel() {
  emit('cancel')
  isOpen.value = false
}
</script>
