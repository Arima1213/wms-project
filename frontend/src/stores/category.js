import { defineStore } from 'pinia'
import { categoryAPI } from '../services/api'
import { useNotificationStore } from './notification'

export const useCategoryStore = defineStore('category', {
  state: () => ({
    categories: [],
    loading: false
  }),

  actions: {
    async fetchList() {
      this.loading = true
      try {
        const res = await categoryAPI.list()
        this.categories = Array.isArray(res) ? res : (res.data || [])
      } catch (error) {
        const notify = useNotificationStore()
        notify.error('Gagal memuat kategori')
        throw error
      } finally {
        this.loading = false
      }
    }
  }
})
