<template>
  <teleport to="body">
    <div class="fixed top-4 right-4 z-[100] space-y-3 pointer-events-none" style="max-width: 400px;">
      <transition-group name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          :class="toastClass(toast.type)"
          class="pointer-events-auto flex items-start gap-3 p-4 rounded-xl shadow-lg border backdrop-blur-sm transition-all duration-300"
        >
          <div :class="iconClass(toast.type)" class="flex-shrink-0 w-5 h-5 mt-0.5">
            <component :is="iconComponent(toast.type)" class="w-5 h-5" />
          </div>
          <div class="flex-1 min-w-0">
            <p v-if="toast.title" class="text-sm font-semibold">{{ toast.title }}</p>
            <p class="text-sm opacity-90">{{ toast.message }}</p>
          </div>
          <button
            @click="notify.removeToast(toast.id)"
            class="flex-shrink-0 opacity-50 hover:opacity-100 transition-opacity"
          >
            <XMarkIcon class="w-4 h-4" />
          </button>
        </div>
      </transition-group>
    </div>
  </teleport>
</template>

<script setup>
import { computed } from 'vue'
import { useNotificationStore } from '../../stores/notification'
import {
  CheckCircleIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  XCircleIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'

const notify = useNotificationStore()
const toasts = computed(() => notify.toasts)

function toastClass(type) {
  return {
    success: 'bg-emerald-50/95 border-emerald-200 text-emerald-800',
    error: 'bg-red-50/95 border-red-200 text-red-800',
    warning: 'bg-amber-50/95 border-amber-200 text-amber-800',
    info: 'bg-blue-50/95 border-blue-200 text-blue-800',
  }[type] || 'bg-white/95 border-gray-200 text-gray-800'
}

function iconClass(type) {
  return {
    success: 'text-emerald-500',
    error: 'text-red-500',
    warning: 'text-amber-500',
    info: 'text-blue-500',
  }[type] || 'text-gray-500'
}

function iconComponent(type) {
  return {
    success: CheckCircleIcon,
    error: XCircleIcon,
    warning: ExclamationTriangleIcon,
    info: InformationCircleIcon,
  }[type] || InformationCircleIcon
}
</script>

<style scoped>
.toast-enter-active {
  animation: slideIn 0.3s ease-out;
}
.toast-leave-active {
  animation: slideOut 0.3s ease-in;
}
.toast-move {
  transition: transform 0.3s ease;
}
@keyframes slideIn {
  from { transform: translateX(100%); opacity: 0; }
  to { transform: translateX(0); opacity: 1; }
}
@keyframes slideOut {
  from { transform: translateX(0); opacity: 1; }
  to { transform: translateX(100%); opacity: 0; }
}
</style>
