import { ref, watch } from 'vue'

export function useDebounce(initialValue = '', delay = 300) {
  const value = ref(initialValue)
  const debouncedValue = ref(initialValue)
  let timeout = null

  watch(value, (newVal) => {
    clearTimeout(timeout)
    timeout = setTimeout(() => {
      debouncedValue.value = newVal
    }, delay)
  })

  return { value, debouncedValue }
}
