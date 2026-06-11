import { defineStore } from 'pinia'
import api from '../services/api'

export const useReturnStore = defineStore('return', {
  state: () => ({
    items: [],
    item: null,
    loading: false,
    pagination: { current: 1, last: 1 },
    filters: { status: '', type: '', warehouse_id: '' },
  }),

  actions: {
    async fetchReturns(params = {}) {
      this.loading = true
      try {
        const query = { ...this.filters, ...params }
        const res = await api.get('/returns', { params: query })
        this.items = res.data.data || res.data
        if (res.data.meta) {
          this.pagination = {
            current: res.data.meta.current_page,
            last: res.data.meta.last_page,
          }
        }
      } finally {
        this.loading = false
      }
    },

    async fetchReturn(id) {
      this.loading = true
      try {
        const res = await api.get(`/returns/${id}`)
        this.item = res.data.data || res.data
        return this.item
      } finally {
        this.loading = false
      }
    },

    async createReturn(data) {
      const res = await api.post('/returns', data)
      return res.data.data || res.data
    },

    async updateReturn(id, data) {
      const res = await api.put(`/returns/${id}`, data)
      return res.data.data || res.data
    },

    async deleteReturn(id) {
      await api.delete(`/returns/${id}`)
    },

    async submitReturn(id) {
      const res = await api.post(`/returns/${id}/submit`)
      return res.data.data || res.data
    },

    async approveReturn(id) {
      const res = await api.post(`/returns/${id}/approve`)
      return res.data.data || res.data
    },

    async processReturn(id) {
      const res = await api.post(`/returns/${id}/process`)
      return res.data.data || res.data
    },

    async rejectReturn(id, reason = '') {
      const res = await api.post(`/returns/${id}/reject`, { reason })
      return res.data.data || res.data
    },

    async cancelReturn(id) {
      const res = await api.post(`/returns/${id}/cancel`)
      return res.data.data || res.data
    },
  },
})
