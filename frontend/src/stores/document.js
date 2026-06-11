import { defineStore } from 'pinia'
import { documentAPI } from '../services/api'

export const useDocumentStore = defineStore('document', {
  state: () => ({
    items: [],
    loading: false,
    pagination: { current_page: 1, last_page: 1, total: 0, per_page: 20 }
  }),
  actions: {
    async fetchList(params = {}) {
      this.loading = true
      try {
        const res = await documentAPI.list(params)
        this.items = res.data || []
        this.pagination = {
          current_page: res.current_page || 1,
          last_page: res.last_page || 1,
          total: res.total || 0,
          per_page: res.per_page || 20
        }
      } catch (e) {
        this.items = []
      } finally {
        this.loading = false
      }
    },
    async upload(formData) {
      await documentAPI.upload(formData)
      await this.fetchList()
    },
    async remove(id) {
      await documentAPI.delete(id)
      await this.fetchList()
    }
  }
})
