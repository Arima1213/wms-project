import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useNotificationStore = defineStore('notification', () => {
  const toasts = ref([])
  let idCounter = 0

  function addToast({ type = 'info', title, message, duration = 5000 }) {
    const id = ++idCounter
    toasts.value.push({ id, type, title, message, duration })
    if (duration > 0) {
      setTimeout(() => removeToast(id), duration)
    }
    return id
  }

  function removeToast(id) {
    const idx = toasts.value.findIndex(t => t.id === id)
    if (idx > -1) toasts.value.splice(idx, 1)
  }

  function success(message, title = 'Berhasil') {
    return addToast({ type: 'success', title, message })
  }

  function error(message, title = 'Error') {
    return addToast({ type: 'error', title, message, duration: 8000 })
  }

  function warning(message, title = 'Peringatan') {
    return addToast({ type: 'warning', title, message })
  }

  function info(message, title = 'Info') {
    return addToast({ type: 'info', title, message })
  }

  return { toasts, addToast, removeToast, success, error, warning, info }
})
