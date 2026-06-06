import { ref } from 'vue'

export function useApi() {
  const loading = ref(false)
  const error = ref(null)
  const data = ref(null)

  async function execute(asyncFn) {
    loading.value = true
    error.value = null
    try {
      const result = await asyncFn()
      data.value = result
      return result
    } catch (e) {
      error.value = e.response?.data?.message || e.message || 'Terjadi kesalahan'
      throw e
    } finally {
      loading.value = false
    }
  }

  return { loading, error, data, execute }
}
