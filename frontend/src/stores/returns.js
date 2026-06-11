import { defineStore } from 'pinia'
import api from '../services/api'

export const useReturnStore = defineStore('returns', {
  state: () => ({
    items: [],
    current: null,
    loading: false,
    pagination: { currentPage: 1, lastPage: 1, total: 0 }
  }),
  actions: {
    async fetchList(params = {}) {
      this.loading = true
      try {
        const res = await api.get('/v1/returns', { params })
        this.items = res.data.data || []
        this.pagination = {
          currentPage: res.data.meta?.current_page || 1,
          lastPage: res.data.meta?.last_page || 1,
          total: res.data.meta?.total || 0
        }
      } finally {
        this.loading = false
      }
    },
    async fetchDetail(id) {
      this.loading = true
      try {
        const res = await api.get(`/v1/returns/${id}`)
        this.current = res.data.data
        return this.current
      } finally {
        this.loading = false
      }
    },
    async create(data) {
      const res = await api.post('/v1/returns', data)
      await this.fetchList()
      return res.data
    },
    async update(id, data) {
      const res = await api.put(`/v1/returns/${id}`, data)
      await this.fetchList()
      return res.data
    },
    async approve(id) {
      const res = await api.post(`/v1/returns/${id}/approve`)
      await this.fetchList()
      return res.data
    },
    async process(id) {
      const res = await api.post(`/v1/returns/${id}/process`)
      await this.fetchList()
      return res.data
    },
    async reject(id, reason = null) {
      const res = await api.post(`/v1/returns/${id}/reject`, { reason })
      await this.fetchList()
      return res.data
    },
    async cancel(id) {
      const res = await api.post(`/v1/returns/${id}/cancel`)
      await this.fetchList()
      return res.data
    },
    async destroy(id) {
      const res = await api.delete(`/v1/returns/${id}`)
      await this.fetchList()
      return res.data
    },

    clearSelected() {
      this.current = null
    }
  }
})
